<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminMateriasController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM materias ORDER BY nombre");
        $materias = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/materias.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/materias');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $_SESSION['error'] = 'El nombre de la materia es obligatorio.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $db = Database::getConnection();

        // Check if unique
        $stmt = $db->prepare("SELECT id FROM materias WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe una materia con ese nombre.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO materias (nombre) VALUES (?)");
        if ($stmt->execute([$nombre])) {
            $_SESSION['success'] = 'Materia creada correctamente.';
        } else {
            $_SESSION['error'] = 'Error al crear la materia.';
        }

        header('Location: /edunexo/admin/materias');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/materias');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');

        if (empty($nombre) || $id === 0) {
            $_SESSION['error'] = 'Datos inválidos para actualizar.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $db = Database::getConnection();

        // Check unique excluding current
        $stmt = $db->prepare("SELECT id FROM materias WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe otra materia con ese nombre.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $stmt = $db->prepare("UPDATE materias SET nombre = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $id])) {
            $_SESSION['success'] = 'Materia actualizada correctamente.';
        } else {
            $_SESSION['error'] = 'Error al actualizar la materia.';
        }

        header('Location: /edunexo/admin/materias');
        exit;
    }

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/materias');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            $_SESSION['error'] = 'ID de materia inválido.';
            header('Location: /edunexo/admin/materias');
            exit;
        }

        $db = Database::getConnection();
        
        // Fetch current status
        $stmt = $db->prepare("SELECT activo FROM materias WHERE id = ?");
        $stmt->execute([$id]);
        $materia = $stmt->fetch();
        
        if ($materia) {
            $newStatus = $materia['activo'] ? 0 : 1;
            $stmtUpdate = $db->prepare("UPDATE materias SET activo = ? WHERE id = ?");
            if ($stmtUpdate->execute([$newStatus, $id])) {
                $_SESSION['success'] = 'Estado de la materia actualizado.';
            } else {
                $_SESSION['error'] = 'Error al actualizar estado.';
            }
        } else {
            $_SESSION['error'] = 'Materia no encontrada.';
        }

        header('Location: /edunexo/admin/materias');
        exit;
    }
}
