<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\SecurityHelper;

class AuthController {

    public function index(): void {
        SecurityHelper::setSecurityHeaders();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void {
        SecurityHelper::setSecurityHeaders();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Token de seguridad invalido. Recarga la pagina.';
            header('Location: /edunexo/login');
            exit;
        }

        if (!SecurityHelper::checkRateLimit('login')) {
            $seconds = SecurityHelper::getLockoutSeconds('login');
            $minutes = ceil($seconds / 60);
            $_SESSION['error'] = "Demasiados intentos fallidos. Intenta de nuevo en {$minutes} minuto(s).";
            header('Location: /edunexo/login');
            exit;
        }

        $username = SecurityHelper::sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            SecurityHelper::registerFailedAttempt('login');
            $_SESSION['error'] = 'Usuario y contrasena son obligatorios.';
            header('Location: /edunexo/login');
            exit;
        }

        $userModel = new Usuario();
        $user = $userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            SecurityHelper::clearAttempts('login');

            // Guardar datos antes de regenerar
            $userId  = $user['id'];
            $nombre  = $user['nombre'];
            $usuario = $user['username'];
            $rol     = $user['rol'];

            // Regenerar ID sin destruir la sesion vieja
            // Usar true rompe el SPA porque el browser aun tiene la cookie vieja
            // en la primera peticion fetch inmediata post-login
            session_regenerate_id(false);

            $_SESSION['user_id']       = $userId;
            $_SESSION['nombre']        = $nombre;
            $_SESSION['usuario']       = $usuario;
            $_SESSION['rol']           = $rol;
            $_SESSION['last_activity'] = time();

            header('Location: /edunexo/dashboard');
            exit;
        }

        SecurityHelper::registerFailedAttempt('login');
        $_SESSION['error'] = 'Credenciales incorrectas.';
        header('Location: /edunexo/login');
        exit;
    }

    public function showRegister(): void {
        SecurityHelper::setSecurityHeaders();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void {
        SecurityHelper::setSecurityHeaders();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::validateCsrfToken($csrfToken)) {
            $_SESSION['reg_error'] = 'Token de seguridad invalido. Recarga la pagina.';
            header('Location: /edunexo/register');
            exit;
        }

        if (!SecurityHelper::checkRateLimit('register')) {
            $_SESSION['reg_error'] = 'Demasiados intentos. Por favor espera unos minutos.';
            header('Location: /edunexo/register');
            exit;
        }

        $nombre   = SecurityHelper::sanitize($_POST['nombre']   ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $usuario  = SecurityHelper::sanitize($_POST['usuario']  ?? '');
        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $rol      = 'docente';

        $errors = [];

        if (empty($nombre) || strlen($nombre) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es valido.';
        }
        if (empty($usuario) || strlen($usuario) < 3 || strlen($usuario) > 30) {
            $errors[] = 'El usuario debe tener entre 3 y 30 caracteres.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
            $errors[] = 'El usuario solo puede contener letras, numeros y guiones bajos.';
        }

        $passErrors = SecurityHelper::validatePassword($password);
        $errors = array_merge($errors, $passErrors);

        if ($password !== $confirm) {
            $errors[] = 'Las contrasenas no coinciden.';
        }

        if (!empty($errors)) {
            SecurityHelper::registerFailedAttempt('register');
            $_SESSION['reg_error'] = implode('<br>', $errors);
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        $userModel = new Usuario();

        if ($userModel->usernameExists($usuario)) {
            $_SESSION['reg_error'] = 'Ese nombre de usuario ya esta en uso.';
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        if ($userModel->emailExists($email)) {
            $_SESSION['reg_error'] = 'Ese email ya esta registrado.';
            $_SESSION['reg_old']   = compact('nombre', 'email', 'usuario');
            header('Location: /edunexo/register');
            exit;
        }

        $userId = $userModel->createUser([
            'nombre'   => $nombre,
            'email'    => $email,
            'usuario'  => $usuario,
            'password' => $password,
            'rol'      => $rol,
        ]);

        if ($userId) {
            SecurityHelper::clearAttempts('register');
            $_SESSION['success'] = 'Cuenta creada exitosamente. Ya podes iniciar sesion.';
            header('Location: /edunexo/login');
            exit;
        }

        $_SESSION['reg_error'] = 'Error al crear la cuenta. Por favor intenta de nuevo.';
        header('Location: /edunexo/register');
        exit;
    }

    public function logout(): void {
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
