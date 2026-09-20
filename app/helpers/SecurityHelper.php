<?php
namespace App\Helpers;

class SecurityHelper {

    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        // No rotar el token — el SPA router necesita el mismo token
        // durante toda la sesion para que multiples fetches funcionen
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function checkRateLimit(string $action = 'login'): bool {
        $ip       = self::getClientIp();
        $key      = 'rate_' . $action . '_' . md5($ip);
        $maxTries = 5;
        $window   = 900;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }

        $data = &$_SESSION[$key];

        if ((time() - $data['first']) > $window) {
            $data = ['count' => 0, 'first' => time()];
        }

        if ($data['count'] >= $maxTries) {
            return false;
        }

        return true;
    }

    public static function registerFailedAttempt(string $action = 'login'): void {
        $ip  = self::getClientIp();
        $key = 'rate_' . $action . '_' . md5($ip);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }
        $_SESSION[$key]['count']++;
    }

    public static function clearAttempts(string $action = 'login'): void {
        $ip  = self::getClientIp();
        $key = 'rate_' . $action . '_' . md5($ip);
        unset($_SESSION[$key]);
    }

    public static function getLockoutSeconds(string $action = 'login'): int {
        $ip     = self::getClientIp();
        $key    = 'rate_' . $action . '_' . md5($ip);
        $window = 900;

        if (!isset($_SESSION[$key])) return 0;

        $data      = $_SESSION[$key];
        $elapsed   = time() - $data['first'];
        $remaining = $window - $elapsed;
        return max(0, (int)$remaining);
    }

    public static function setSecurityHeaders(): void {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:;");
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    }

    public static function checkSessionTimeout(int $timeoutMinutes = 120): bool {
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

    public static function validatePassword(string $password): array {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contrasena debe contener al menos una mayuscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contrasena debe contener al menos un numero.';
        }
        return $errors;
    }

    /**
     * Limpia un texto de entrada para GUARDARLO: recorta espacios y quita caracteres de control.
     * NO escapa HTML. El escapado se hace siempre al MOSTRAR (e() o htmlspecialchars), porque
     * escapar al guardar rompe apostrofes/comillas/& al editar, en WhatsApp y en JavaScript.
     */
    public static function sanitize(string $input): string {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim($input)) ?? '';
    }

    /** Escapa un valor para imprimirlo en HTML (texto o atributos). */
    public static function e($valor): string {
        return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Valor listo para usar como argumento de JavaScript dentro de un atributo HTML:
     * onclick="editar(<?= SecurityHelper::jsArg($nombre) ?>)". Sirve para comillas, apostrofes,
     * barras invertidas, saltos de linea y </script>.
     */
    public static function jsArg($valor): string {
        $json = json_encode((string)$valor, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        return htmlspecialchars($json === false ? '""' : $json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

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
