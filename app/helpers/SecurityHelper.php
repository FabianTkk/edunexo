<?php
namespace App\Helpers;

class SecurityHelper {

    // ─── CSRF ──────────────────────────────────────────────────────────────

    /**
     * Genera (o reutiliza) un token CSRF en sesión.
     */
    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida el token CSRF recibido por POST.
     */
    public static function validateCsrfToken(string $token): bool {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        // Rotar el token tras uso
        unset($_SESSION['csrf_token']);
        return $valid;
    }

    // ─── RATE LIMITING ─────────────────────────────────────────────────────

    /**
     * Verifica si la IP ha superado el límite de intentos de login.
     * Max 5 intentos en 15 minutos.
     * @return bool  true = permitido, false = bloqueado
     */
    public static function checkRateLimit(string $action = 'login'): bool {
        $ip        = self::getClientIp();
        $key       = 'rate_' . $action . '_' . md5($ip);
        $maxTries  = 5;
        $window    = 900; // 15 minutos

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }

        $data = &$_SESSION[$key];

        // Reiniciar ventana si ya expiró
        if ((time() - $data['first']) > $window) {
            $data = ['count' => 0, 'first' => time()];
        }

        if ($data['count'] >= $maxTries) {
            return false; // bloqueado
        }

        return true;
    }

    /**
     * Registra un intento fallido para la IP actual.
     */
    public static function registerFailedAttempt(string $action = 'login'): void {
        $ip  = self::getClientIp();
        $key = 'rate_' . $action . '_' . md5($ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }
        $_SESSION[$key]['count']++;
    }

    /**
     * Limpia los intentos fallidos (tras login exitoso).
     */
    public static function clearAttempts(string $action = 'login'): void {
        $ip  = self::getClientIp();
        $key = 'rate_' . $action . '_' . md5($ip);
        unset($_SESSION[$key]);
    }

    /**
     * Retorna los segundos restantes de bloqueo.
     */
    public static function getLockoutSeconds(string $action = 'login'): int {
        $ip    = self::getClientIp();
        $key   = 'rate_' . $action . '_' . md5($ip);
        $window = 900;

        if (!isset($_SESSION[$key])) return 0;

        $data = $_SESSION[$key];
        $elapsed = time() - $data['first'];
        $remaining = $window - $elapsed;
        return max(0, (int)$remaining);
    }

    // ─── HEADERS HTTP ──────────────────────────────────────────────────────

    /**
     * Establece headers de seguridad HTTP.
     */
    public static function setSecurityHeaders(): void {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;");
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    }

    // ─── SESSION ───────────────────────────────────────────────────────────

    /**
     * Verifica si la sesión no ha expirado (30 min de inactividad).
     * @return bool  true = sesión válida
     */
    public static function checkSessionTimeout(int $timeoutMinutes = 30): bool {
        $timeout = $timeoutMinutes * 60;

        if (isset($_SESSION['last_activity'])) {
            if ((time() - $_SESSION['last_activity']) > $timeout) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    // ─── VALIDACIÓN ────────────────────────────────────────────────────────

    /**
     * Valida que la contraseña cumpla requisitos mínimos.
     * Mínimo 8 chars, 1 mayúscula, 1 número.
     */
    public static function validatePassword(string $password): array {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número.';
        }
        return $errors;
    }

    /**
     * Sanitiza un string de entrada.
     */
    public static function sanitize(string $input): string {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    // ─── UTILS ─────────────────────────────────────────────────────────────

    private static function getClientIp(): string {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
?>
