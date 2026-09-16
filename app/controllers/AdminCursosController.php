<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminCursosController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM cursos ORDER BY nombre");
        $cursos = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/cursos.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');
        $turno = $_POST['turno'] ?? 'manana';
        if (!in_array($turno, ['manana', 'tarde', 'noche'], true)) {
            $_SESSION['error'] = 'El turno seleccionado no es válido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        if (empty($nombre)) {
            $_SESSION['error'] = 'El nombre del curso es obligatorio.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $db = Database::getConnection();

        // Check if unique
        $stmt = $db->prepare("SELECT id FROM cursos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe un curso con ese nombre.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO cursos (nombre, turno) VALUES (?, ?)");
        if ($stmt->execute([$nombre, $turno])) {
            $_SESSION['success'] = 'Curso creado correctamente.';
        } else {
            $_SESSION['error'] = 'Error al crear el curso.';
        }

        header('Location: /edunexo/admin/cursos');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');
        $turno = $_POST['turno'] ?? 'manana';
        if (!in_array($turno, ['manana', 'tarde', 'noche'], true)) {
            $_SESSION['error'] = 'El turno seleccionado no es válido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        if (empty($nombre) || $id === 0) {
            $_SESSION['error'] = 'Datos inválidos para actualizar.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $db = Database::getConnection();

        // Check unique excluding current
        $stmt = $db->prepare("SELECT id FROM cursos WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe otro curso con ese nombre.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $stmt = $db->prepare("UPDATE cursos SET nombre = ?, turno = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $turno, $id])) {
            $_SESSION['success'] = 'Curso actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'Error al actualizar el curso.';
        }

        header('Location: /edunexo/admin/cursos');
        exit;
    }

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            $_SESSION['error'] = 'ID de curso inválido.';
            header('Location: /edunexo/admin/cursos');
            exit;
        }

        $db = Database::getConnection();
        
        // Fetch current status
        $stmt = $db->prepare("SELECT activo FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $curso = $stmt->fetch();
        
        if ($curso) {
            $newStatus = $curso['activo'] ? 0 : 1;
            $stmtUpdate = $db->prepare("UPDATE cursos SET activo = ? WHERE id = ?");
            if ($stmtUpdate->execute([$newStatus, $id])) {
                $_SESSION['success'] = 'Estado del curso actualizado.';
            } else {
                $_SESSION['error'] = 'Error al actualizar estado.';
            }
        } else {
            $_SESSION['error'] = 'Curso no encontrado.';
        }

        header('Location: /edunexo/admin/cursos');
        exit;
    }
}
