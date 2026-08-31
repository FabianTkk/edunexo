<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class DocenteEvaluacionesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    private function verificarPertinencia(int $cmd_id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM curso_materia_docente WHERE id = ? AND docente_id = ?");
        $stmt->execute([$cmd_id, $_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    }

    public function index() {
        $cmd_id = (int)($_GET['cmd_id'] ?? 0);
        
        if ($cmd_id === 0 || !$this->verificarPertinencia($cmd_id)) {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $db = Database::getConnection();

        // Get info about the course and subject
        $stmtInfo = $db->prepare("
            SELECT c.nombre AS curso, m.nombre AS materia 
            FROM curso_materia_docente cmd
            JOIN cursos c ON c.id = cmd.curso_id
            JOIN materias m ON m.id = cmd.materia_id
            WHERE cmd.id = ?
        ");
        $stmtInfo->execute([$cmd_id]);
        $info = $stmtInfo->fetch();

        // Get evaluations
        $stmtEval = $db->prepare("
            SELECT e.*, t.nombre as tipo_nombre 
            FROM evaluaciones e 
            JOIN tipo_evaluacion t ON e.tipo_evaluacion_id = t.id 
            WHERE e.curso_materia_docente_id = ? 
            ORDER BY e.fecha DESC, e.created_at DESC
        ");
        $stmtEval->execute([$cmd_id]);
        $evaluaciones = $stmtEval->fetchAll();

        // Get evaluation types for the form
        $tipos = $db->query("SELECT * FROM tipo_evaluacion WHERE activo = 1 ORDER BY nombre")->fetchAll();

        require __DIR__ . '/../views/docente/evaluaciones.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $cmd_id = (int)($_POST['cmd_id'] ?? 0);
        
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$this->verificarPertinencia($cmd_id)) {
            $_SESSION['error'] = 'Token CSRF inválido o acceso denegado.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $tipo_id = (int)($_POST['tipo_evaluacion_id'] ?? 0);
        $titulo = SecurityHelper::sanitize($_POST['titulo'] ?? '');
        $fecha = $_POST['fecha'] ?? '';
        $puntaje = (float)($_POST['puntaje_maximo'] ?? 0);
        $descripcion = SecurityHelper::sanitize($_POST['descripcion'] ?? '');

        if ($tipo_id === 0 || empty($titulo) || empty($fecha) || $puntaje <= 0) {
            $_SESSION['error'] = 'Debe completar los campos obligatorios correctamente.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO evaluaciones (curso_materia_docente_id, tipo_evaluacion_id, titulo, fecha, puntaje_maximo, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$cmd_id, $tipo_id, $titulo, $fecha, $puntaje, $descripcion])) {
            $_SESSION['success'] = 'Evaluación creada correctamente.';
        } else {
            $_SESSION['error'] = 'Error al crear la evaluación.';
        }

        header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $cmd_id = (int)($_POST['cmd_id'] ?? 0);
        
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$this->verificarPertinencia($cmd_id)) {
            $_SESSION['error'] = 'Acceso denegado o token inválido.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $tipo_id = (int)($_POST['tipo_evaluacion_id'] ?? 0);
        $titulo = SecurityHelper::sanitize($_POST['titulo'] ?? '');
        $fecha = $_POST['fecha'] ?? '';
        $puntaje = (float)($_POST['puntaje_maximo'] ?? 0);
        $descripcion = SecurityHelper::sanitize($_POST['descripcion'] ?? '');

        if ($id === 0 || $tipo_id === 0 || empty($titulo) || empty($fecha) || $puntaje <= 0) {
            $_SESSION['error'] = 'Datos inválidos para actualizar.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE evaluaciones SET tipo_evaluacion_id = ?, titulo = ?, fecha = ?, puntaje_maximo = ?, descripcion = ? WHERE id = ? AND curso_materia_docente_id = ?");
        
        if ($stmt->execute([$tipo_id, $titulo, $fecha, $puntaje, $descripcion, $id, $cmd_id])) {
            $_SESSION['success'] = 'Evaluación actualizada correctamente.';
        } else {
            $_SESSION['error'] = 'Error al actualizar la evaluación.';
        }

        header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
        exit;
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $cmd_id = (int)($_POST['cmd_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$this->verificarPertinencia($cmd_id)) {
            $_SESSION['error'] = 'Acceso denegado.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $db = Database::getConnection();
        
        // Verificar que pertenezca al cmd_id actual
        $stmt = $db->prepare("DELETE FROM evaluaciones WHERE id = ? AND curso_materia_docente_id = ?");
        if ($stmt->execute([$id, $cmd_id])) {
            $_SESSION['success'] = 'Evaluación eliminada correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo eliminar la evaluación (puede que tenga notas asociadas).';
        }

        header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
        exit;
    }
}
