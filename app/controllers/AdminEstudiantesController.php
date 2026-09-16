<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminEstudiantesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();

        $estudiantes = $db->query("
            SELECT e.*, c.nombre AS curso_nombre, t.nombre_completo AS tutor_nombre, te.tutor_id
            FROM estudiantes e
            LEFT JOIN cursos c ON c.id = e.curso_id
            LEFT JOIN tutores_estudiantes te ON te.id = (
                SELECT te2.id
                FROM tutores_estudiantes te2
                WHERE te2.estudiante_id = e.id
                ORDER BY te2.id
                LIMIT 1
            )
            LEFT JOIN tutores t ON t.id = te.tutor_id
            ORDER BY e.nombre_completo
        ")->fetchAll();

        $cursos  = $db->query("SELECT * FROM cursos WHERE activo=1 ORDER BY nombre")->fetchAll();
        $tutores = $db->query("SELECT * FROM tutores WHERE activo=1 ORDER BY nombre_completo")->fetchAll();
        $csrfToken = SecurityHelper::generateCsrfToken();

        require __DIR__ . '/../views/admin/estudiantes.php';
    }

    public function store() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $ci       = SecurityHelper::sanitize($_POST['ci']             ?? '');
        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $tutor_id = (int)($_POST['tutor_id'] ?? 0);

        if (empty($ci) || empty($nombre) || $curso_id === 0) {
            $_SESSION['error'] = 'CI, nombre y curso son obligatorios.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM estudiantes WHERE ci=?");
        $stmt->execute([$ci]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe un estudiante con ese CI.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $stmt = $db->prepare("INSERT INTO estudiantes (ci, nombre_completo, curso_id, curso) VALUES (?,?,?,?)");
        $cursoNombre = $db->prepare("SELECT nombre FROM cursos WHERE id=?");
        $cursoNombre->execute([$curso_id]);
        $cn = $cursoNombre->fetchColumn() ?: '';
        $stmt->execute([$ci, $nombre, $curso_id, $cn]);
        $newId = $db->lastInsertId();

        if ($tutor_id > 0) {
            $db->prepare("INSERT IGNORE INTO tutores_estudiantes (tutor_id, estudiante_id) VALUES (?,?)")
               ->execute([$tutor_id, $newId]);
        }

        $_SESSION['success'] = 'Estudiante creado correctamente.';
        header('Location: /edunexo/admin/estudiantes'); exit;
    }

    public function update() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $id       = (int)($_POST['id']             ?? 0);
        $ci       = SecurityHelper::sanitize($_POST['ci']             ?? '');
        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $tutor_id = (int)($_POST['tutor_id'] ?? 0);

        if ($id === 0 || empty($ci) || empty($nombre) || $curso_id === 0) {
            $_SESSION['error'] = 'Datos invalidos.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM estudiantes WHERE ci=? AND id!=?");
        $stmt->execute([$ci, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ese CI ya pertenece a otro estudiante.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $cursoNombre = $db->prepare("SELECT nombre FROM cursos WHERE id=?");
        $cursoNombre->execute([$curso_id]);
        $cn = $cursoNombre->fetchColumn() ?: '';

        $db->prepare("UPDATE estudiantes SET ci=?, nombre_completo=?, curso_id=?, curso=? WHERE id=?")
           ->execute([$ci, $nombre, $curso_id, $cn, $id]);

        $db->prepare("DELETE FROM tutores_estudiantes WHERE estudiante_id=?")->execute([$id]);
        if ($tutor_id > 0) {
            $db->prepare("INSERT INTO tutores_estudiantes (tutor_id, estudiante_id) VALUES (?,?)")
               ->execute([$tutor_id, $id]);
        }

        $_SESSION['success'] = 'Estudiante actualizado correctamente.';
        header('Location: /edunexo/admin/estudiantes'); exit;
    }

    public function toggle() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) { header('Location: /edunexo/admin/estudiantes'); exit; }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT estado FROM estudiantes WHERE id=?");
        $stmt->execute([$id]);
        $e = $stmt->fetch();
        if ($e) {
            $nuevo = $e['estado'] === 'activo' ? 'inactivo' : 'activo';
            $db->prepare("UPDATE estudiantes SET estado=? WHERE id=?")->execute([$nuevo, $id]);
            $_SESSION['success'] = 'Estado del estudiante actualizado.';
        }
        header('Location: /edunexo/admin/estudiantes'); exit;
    }
}
