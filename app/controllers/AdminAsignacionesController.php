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
        
        // Solo las materias habilitadas para cada curso.
        $asignacionesRaw = $db->query("
            SELECT cmd.id, cmd.curso_id, cmd.materia_id, cmd.docente_id,
                   m.nombre AS materia_nombre, u.nombre AS docente_nombre
            FROM curso_materia_docente cmd
            JOIN materias m ON m.id = cmd.materia_id
            LEFT JOIN usuarios u ON u.id = cmd.docente_id
            WHERE cmd.activo = 1
            ORDER BY m.nombre
        ")->fetchAll();
        
        // Map assignments by curso_id and materia_id
        $asignaciones = [];
        foreach ($asignacionesRaw as $row) {
            $asignaciones[$row['curso_id']][$row['materia_id']] = $row;
        }

        require __DIR__ . '/../views/admin/asignaciones.php';
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

        if ($curso_id === 0 || $materia_id === 0 || $docente_id === 0) {
            $_SESSION['error'] = 'Debe seleccionar una materia y un docente válidos.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $db = Database::getConnection();

        try {
            $validar = $db->prepare("
                SELECT
                    EXISTS(SELECT 1 FROM cursos WHERE id = ? AND activo = 1) AS curso_valido,
                    EXISTS(SELECT 1 FROM materias WHERE id = ? AND activo = 1) AS materia_valida,
                    EXISTS(SELECT 1 FROM usuarios WHERE id = ? AND rol = 'docente' AND activo = 1) AS docente_valido
            ");
            $validar->execute([$curso_id, $materia_id, $docente_id]);
            $entidades = $validar->fetch();
            if (!$entidades['curso_valido'] || !$entidades['materia_valida'] || !$entidades['docente_valido']) {
                throw new \InvalidArgumentException('El curso, la materia o el docente ya no están disponibles.');
            }

            if ($cmd_id > 0) {
                $existe = $db->prepare('SELECT id FROM curso_materia_docente WHERE id = ? AND curso_id = ? AND materia_id = ? AND activo = 1');
                $existe->execute([$cmd_id, $curso_id, $materia_id]);
                if (!$existe->fetch()) {
                    throw new \RuntimeException('La asignación no existe o ya fue quitada.');
                }
                $stmt = $db->prepare("UPDATE curso_materia_docente SET docente_id = ? WHERE id = ? AND curso_id = ? AND materia_id = ? AND activo = 1");
                $stmt->execute([$docente_id, $cmd_id, $curso_id, $materia_id]);
            } else {
                $stmt = $db->prepare("INSERT INTO curso_materia_docente (curso_id, materia_id, docente_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE docente_id = VALUES(docente_id), activo = 1");
                $stmt->execute([$curso_id, $materia_id, $docente_id]);
            }

            $_SESSION['success'] = 'Asignación guardada correctamente.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al guardar la asignación.';
        }

        header('Location: /edunexo/admin/asignaciones');
        exit;
    }

    public function remove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Solicitud no válida o token CSRF inválido.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $cmdId = (int)($_POST['cmd_id'] ?? 0);
        if ($cmdId === 0) {
            $_SESSION['error'] = 'Asignación inválida.';
            header('Location: /edunexo/admin/asignaciones');
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE curso_materia_docente SET activo = 0 WHERE id = ? AND activo = 1');
        $stmt->execute([$cmdId]);

        $_SESSION[$stmt->rowCount() ? 'success' : 'error'] = $stmt->rowCount()
            ? 'La materia fue quitada del curso. Se conserva su historial académico.'
            : 'La asignación no existe o ya fue quitada.';
        header('Location: /edunexo/admin/asignaciones');
        exit;
    }
}
