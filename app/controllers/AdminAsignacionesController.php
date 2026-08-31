<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminAsignacionesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        
        $cursos = $db->query("SELECT id, nombre, turno FROM cursos WHERE activo = 1 ORDER BY nombre")->fetchAll();
        $materias = $db->query("SELECT id, nombre FROM materias WHERE activo = 1 ORDER BY nombre")->fetchAll();
        $docentes = $db->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND activo = 1 ORDER BY nombre")->fetchAll();
        
        // Fetch all existing assignments
        $asignacionesRaw = $db->query("SELECT id, curso_id, materia_id, docente_id FROM curso_materia_docente WHERE activo = 1")->fetchAll();
        
        // Map assignments by curso_id and materia_id
        $asignaciones = [];
        foreach ($asignacionesRaw as $row) {
            $asignaciones[$row['curso_id']][$row['materia_id']] = $row;
        }

        require __DIR__ . '/../views/admin/asignaciones.php';
    }

    public function bulk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $docente_id = (int)($_POST['docente_id'] ?? 0);

        if ($curso_id === 0 || $docente_id === 0) {
            $_SESSION['error'] = 'Debe seleccionar un curso y un docente.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $db = Database::getConnection();
        $materias = $db->query("SELECT id FROM materias WHERE activo = 1")->fetchAll();

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO curso_materia_docente (curso_id, materia_id, docente_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE docente_id = VALUES(docente_id), activo = 1");
            foreach ($materias as $m) {
                $stmt->execute([$curso_id, $m['id'], $docente_id]);
            }
            $db->commit();
            $_SESSION['success'] = 'Asignación masiva completada correctamente.';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al realizar la asignación masiva.';
        }

        header('Location: /edunexo/admin/asignaciones');
        exit;
    }

    public function single() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $cmd_id = (int)($_POST['cmd_id'] ?? 0);
        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $materia_id = (int)($_POST['materia_id'] ?? 0);
        $docente_id = (int)($_POST['docente_id'] ?? 0);

        if ($docente_id === 0) {
            $_SESSION['error'] = 'Debe seleccionar un docente válido.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $db = Database::getConnection();

        try {
            if ($cmd_id > 0) {
                // UPDATE that single row
                $stmt = $db->prepare("UPDATE curso_materia_docente SET docente_id = ?, activo = 1 WHERE id = ?");
                $stmt->execute([$docente_id, $cmd_id]);
            } else if ($curso_id > 0 && $materia_id > 0) {
                // INSERT new row if not exists
                $stmt = $db->prepare("INSERT INTO curso_materia_docente (curso_id, materia_id, docente_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE docente_id = VALUES(docente_id), activo = 1");
                $stmt->execute([$curso_id, $materia_id, $docente_id]);
            } else {
                $_SESSION['error'] = 'Datos insuficientes para la asignación.';
                header('Location: /edunexo/admin/asignaciones');
                exit;
            }

            $_SESSION['success'] = 'Asignación guardada correctamente.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al guardar la asignación.';
        }

        header('Location: /edunexo/admin/asignaciones');
        exit;
    }
}
