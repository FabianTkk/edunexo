<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminTutoresController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $tutores = $db->query("SELECT * FROM tutores ORDER BY nombre_completo")->fetchAll();

        foreach ($tutores as &$t) {
            $stmt = $db->prepare("
                SELECT e.nombre_completo, e.ci
                FROM tutores_estudiantes te
                JOIN estudiantes e ON e.id = te.estudiante_id
                WHERE te.tutor_id = ?
                ORDER BY e.nombre_completo
            ");
            $stmt->execute([$t['id']]);
            $t['estudiantes'] = $stmt->fetchAll();
        }
        unset($t);

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/tutores.php';
    }

    public function store() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $telefono = SecurityHelper::sanitize($_POST['telefono']        ?? '');

        if (empty($nombre) || empty($telefono)) {
            $_SESSION['error'] = 'Nombre y telefono son obligatorios.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        if (!preg_match('/^595\d{9}$/', $telefono)) {
            $_SESSION['error'] = 'El telefono debe tener el formato 595XXXXXXXXX (12 digitos).';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $db = Database::getConnection();
        $db->prepare("INSERT INTO tutores (nombre_completo, telefono) VALUES (?,?)")
           ->execute([$nombre, $telefono]);

        $_SESSION['success'] = 'Tutor creado correctamente.';
        header('Location: /edunexo/admin/tutores'); exit;
    }

    public function update() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $id       = (int)($_POST['id']             ?? 0);
        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $telefono = SecurityHelper::sanitize($_POST['telefono']        ?? '');

        if ($id === 0 || empty($nombre) || empty($telefono)) {
            $_SESSION['error'] = 'Datos invalidos.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        if (!preg_match('/^595\d{9}$/', $telefono)) {
            $_SESSION['error'] = 'El telefono debe tener el formato 595XXXXXXXXX (12 digitos).';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $db = Database::getConnection();
        $db->prepare("UPDATE tutores SET nombre_completo=?, telefono=? WHERE id=?")
           ->execute([$nombre, $telefono, $id]);

        $_SESSION['success'] = 'Tutor actualizado correctamente.';
        header('Location: /edunexo/admin/tutores'); exit;
    }

    public function toggle() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            $_SESSION['error'] = 'ID invalido.';
            header('Location: /edunexo/admin/tutores'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT activo FROM tutores WHERE id=?");
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        if ($t) {
            $nuevo = $t['activo'] ? 0 : 1;
            $db->prepare("UPDATE tutores SET activo=? WHERE id=?")->execute([$nuevo, $id]);
            $_SESSION['success'] = 'Estado del tutor actualizado.';
        } else {
            $_SESSION['error'] = 'Tutor no encontrado.';
        }
        header('Location: /edunexo/admin/tutores'); exit;
    }
}
