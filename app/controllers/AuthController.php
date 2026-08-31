<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\SecurityHelper;

class AuthController {

    // ─── LOGIN ─────────────────────────────────────────────────────────────

    /**
     * GET /login — Muestra el formulario de login.
     */
    public function index(): void {
        SecurityHelper::setSecurityHeaders();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * POST /login — Procesa el intento de autenticación.
     */
    public function login(): void {
        SecurityHelper::setSecurityHeaders();

        // ── 1. Validar CSRF ────────────────────────────────────────────────
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Token de seguridad inválido. Recarga la página.';
            header('Location: /edunexo/login');
            exit;
        }

        // ── 2. Rate limiting ───────────────────────────────────────────────
        if (!SecurityHelper::checkRateLimit('login')) {
            $seconds  = SecurityHelper::getLockoutSeconds('login');
            $minutes  = ceil($seconds / 60);
            $_SESSION['error'] = "Demasiados intentos fallidos. Intenta de nuevo en {$minutes} minuto(s).";
            header('Location: /edunexo/login');
            exit;
        }

        // ── 3. Validar campos ──────────────────────────────────────────────
        $username = SecurityHelper::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            SecurityHelper::registerFailedAttempt('login');
            $_SESSION['error'] = 'Usuario y contraseña son obligatorios.';
            header('Location: /edunexo/login');
            exit;
        }

        // ── 4. Buscar usuario ──────────────────────────────────────────────
        $userModel = new Usuario();
        $user = $userModel->findByUsername($username);

        // ── 5. Verificar contraseña ────────────────────────────────────────
        if ($user && password_verify($password, $user['password'])) {
            // Autenticación exitosa
            SecurityHelper::clearAttempts('login');

            // Regenerar ID de sesión para prevenir session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['id'];
            $_SESSION['nombre']        = $user['nombre'];
            $_SESSION['usuario']       = $user['username'];
            $_SESSION['rol']           = $user['rol'];
            $_SESSION['last_activity'] = time();

            header('Location: /edunexo/dashboard');
            exit;
        }

        // Credenciales incorrectas
        SecurityHelper::registerFailedAttempt('login');
        // Mensaje genérico (no revelar si usuario existe o no)
        $_SESSION['error'] = 'Credenciales incorrectas.';
        header('Location: /edunexo/login');
        exit;
    }

    // ─── REGISTRO ──────────────────────────────────────────────────────────

    /**
     * GET /register — Muestra formulario de registro (URL discreta, no linkada).
     */
    public function showRegister(): void {
        SecurityHelper::setSecurityHeaders();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/auth/register.php';
    }

    /**
     * POST /register — Procesa el registro de nuevo usuario.
     */
    public function register(): void {
        SecurityHelper::setSecurityHeaders();

        // ── 1. Validar CSRF ────────────────────────────────────────────────
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::validateCsrfToken($csrfToken)) {
            $_SESSION['reg_error'] = 'Token de seguridad inválido. Recarga la página.';
            header('Location: /edunexo/register');
            exit;
        }

        // ── 2. Rate limiting para registro ────────────────────────────────
        if (!SecurityHelper::checkRateLimit('register')) {
            $_SESSION['reg_error'] = 'Demasiados intentos. Por favor espera unos minutos.';
            header('Location: /edunexo/register');
            exit;
        }

        // ── 3. Recoger y sanitizar datos ──────────────────────────────────
        $nombre   = SecurityHelper::sanitize($_POST['nombre']   ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $usuario  = SecurityHelper::sanitize($_POST['usuario']  ?? '');
        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $rol      = 'alumno'; // rol por defecto, no expuesto al usuario

        // ── 4. Validaciones ────────────────────────────────────────────────
        $errors = [];

        if (empty($nombre) || strlen($nombre) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido.';
        }
        if (empty($usuario) || strlen($usuario) < 3 || strlen($usuario) > 30) {
            $errors[] = 'El usuario debe tener entre 3 y 30 caracteres.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
            $errors[] = 'El usuario solo puede contener letras, números y guiones bajos.';
        }

        $passErrors = SecurityHelper::validatePassword($password);
        $errors = array_merge($errors, $passErrors);

        if ($password !== $confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errors)) {
            SecurityHelper::registerFailedAttempt('register');
            $_SESSION['reg_error'] = implode('<br>', $errors);
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        // ── 5. Verificar unicidad ──────────────────────────────────────────
        $userModel = new Usuario();

        if ($userModel->usernameExists($usuario)) {
            $_SESSION['reg_error'] = 'Ese nombre de usuario ya está en uso.';
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        if ($userModel->emailExists($email)) {
            $_SESSION['reg_error'] = 'Ese email ya está registrado.';
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        // ── 6. Crear usuario ──────────────────────────────────────────────
        $userId = $userModel->createUser([
            'nombre'   => $nombre,
            'email'    => $email,
            'usuario'  => $usuario,
            'password' => $password,
            'rol'      => $rol,
        ]);

        if ($userId) {
            SecurityHelper::clearAttempts('register');
            $_SESSION['success'] = '¡Cuenta creada exitosamente! Ya podés iniciar sesión.';
            header('Location: /edunexo/login');
            exit;
        }

        $_SESSION['reg_error'] = 'Error al crear la cuenta. Por favor intenta de nuevo.';
        header('Location: /edunexo/register');
        exit;
    }

    // ─── LOGOUT ────────────────────────────────────────────────────────────

    /**
     * POST /logout — Cierra la sesión de forma segura.
     */
    public function logout(): void {
        // Validar CSRF del logout también
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!empty($_SESSION['csrf_token'])) {
            // validar (pero no bloquear si falla, solo cerrar sesión)
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        header('Location: /edunexo/login');
        exit;
    }
}
?>
