<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminUsuariosController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM usuarios ORDER BY nombre");
        $usuarios = $stmt->fetchAll();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/usuarios.php';
    }

    public function store() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $nombre   = SecurityHelper::sanitize($_POST['nombre']   ?? '');
        $username = SecurityHelper::sanitize($_POST['username'] ?? '');
        $email    = SecurityHelper::sanitize($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';
        $rol      = in_array($_POST['rol'] ?? '', ['admin','docente']) ? $_POST['rol'] : 'docente';

        if (empty($nombre) || empty($username) || empty($password)) {
            $_SESSION['error'] = 'Nombre, usuario y contrasena son obligatorios.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe un usuario con ese nombre de usuario.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, username, email, password, rol) VALUES (?,?,?,?,?)");
        $stmt->execute([$nombre, $username, $email, $hash, $rol]);

        $_SESSION['success'] = 'Usuario creado correctamente.';
        header('Location: /edunexo/admin/usuarios'); exit;
    }

    public function update() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $id       = (int)($_POST['id'] ?? 0);
        $nombre   = SecurityHelper::sanitize($_POST['nombre']   ?? '');
        $username = SecurityHelper::sanitize($_POST['username'] ?? '');
        $email    = SecurityHelper::sanitize($_POST['email']    ?? '');
        $rol      = in_array($_POST['rol'] ?? '', ['admin','docente']) ? $_POST['rol'] : 'docente';
        $password = $_POST['password'] ?? '';

        if ($id === 0 || empty($nombre) || empty($username)) {
            $_SESSION['error'] = 'Datos invalidos.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ese nombre de usuario ya esta en uso por otro usuario.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE usuarios SET nombre=?, username=?, email=?, rol=?, password=? WHERE id=?");
            $stmt->execute([$nombre, $username, $email, $rol, $hash, $id]);
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre=?, username=?, email=?, rol=? WHERE id=?");
            $stmt->execute([$nombre, $username, $email, $rol, $id]);
        }

        $_SESSION['success'] = 'Usuario actualizado correctamente.';
        header('Location: /edunexo/admin/usuarios'); exit;
    }

    public function toggle() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            $_SESSION['error'] = 'ID invalido.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'No podes desactivar tu propia cuenta.';
            header('Location: /edunexo/admin/usuarios'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT activo FROM usuarios WHERE id=?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u) {
            $db->prepare("UPDATE usuarios SET activo=? WHERE id=?")->execute([$u['activo'] ? 0 : 1, $id]);
            $_SESSION['success'] = 'Estado del usuario actualizado.';
        }
        header('Location: /edunexo/admin/usuarios'); exit;
    }
}
