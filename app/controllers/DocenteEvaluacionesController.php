<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class DocenteEvaluacionesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', ['docente', 'admin'])) {
            header('Location: /edunexo/login');
            exit;
        }
    }

    private function verificarPertinencia(int $cmd_id): bool {
        if (($_SESSION['rol'] ?? '') === 'admin') {
            return true;
        }
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

        // Obtener datos del curso y materia
        $stmtInfo = $db->prepare("
            SELECT cmd.id, c.nombre AS curso, c.turno, m.nombre AS materia, u.nombre AS docente_nombre
            FROM curso_materia_docente cmd
            JOIN cursos c ON c.id = cmd.curso_id
            JOIN materias m ON m.id = cmd.materia_id
            JOIN usuarios u ON u.id = cmd.docente_id
            WHERE cmd.id = ?
        ");
        $stmtInfo->execute([$cmd_id]);
        $info = $stmtInfo->fetch();

        if (!$info) {
            $_SESSION['error'] = 'Materia no encontrada.';
            header('Location: /edunexo/docente/materias');
            exit;
        }

        // Obtener evaluaciones asociadas
        $stmtEval = $db->prepare("
            SELECT e.*, t.nombre AS tipo_nombre, t.peso_porcentaje 
            FROM evaluaciones e 
            JOIN tipo_evaluacion t ON e.tipo_evaluacion_id = t.id 
            WHERE e.curso_materia_docente_id = ? 
            ORDER BY e.fecha DESC, e.id DESC
        ");
        $stmtEval->execute([$cmd_id]);
        $evaluaciones = $stmtEval->fetchAll();

        // Tipos de evaluación activos para el formulario
        $tipos = $db->query("SELECT * FROM tipo_evaluacion WHERE activo = 1 ORDER BY nombre")->fetchAll();

        $csrfToken = SecurityHelper::generateCsrfToken();

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

        $tipo_id     = (int)($_POST['tipo_evaluacion_id'] ?? 0);
        $titulo      = SecurityHelper::sanitize($_POST['titulo'] ?? '');
        $fecha       = trim($_POST['fecha'] ?? '');
        $puntaje     = (float)($_POST['puntaje_maximo'] ?? 0);
        $descripcion = SecurityHelper::sanitize($_POST['descripcion'] ?? '');

        if ($tipo_id === 0 || empty($titulo) || empty($fecha) || $puntaje <= 0) {
            $_SESSION['error'] = 'Debe completar todos los campos obligatorios (tipo, título, fecha válida y puntaje mayor a 0).';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO evaluaciones (curso_materia_docente_id, tipo_evaluacion_id, titulo, fecha, puntaje_maximo, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$cmd_id, $tipo_id, $titulo, $fecha, $puntaje, $descripcion])) {
            $_SESSION['success'] = 'Evaluación creada correctamente.';
        } else {
            $_SESSION['error'] = 'Error al registrar la evaluación en la base de datos.';
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
            $_SESSION['error'] = 'Token CSRF inválido o acceso denegado.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $id          = (int)($_POST['id'] ?? 0);
        $tipo_id     = (int)($_POST['tipo_evaluacion_id'] ?? 0);
        $titulo      = SecurityHelper::sanitize($_POST['titulo'] ?? '');
        $fecha       = trim($_POST['fecha'] ?? '');
        $puntaje     = (float)($_POST['puntaje_maximo'] ?? 0);
        $descripcion = SecurityHelper::sanitize($_POST['descripcion'] ?? '');

        if ($id === 0 || $tipo_id === 0 || empty($titulo) || empty($fecha) || $puntaje <= 0) {
            $_SESSION['error'] = 'Datos inválidos para actualizar la evaluación.';
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
        $id     = (int)($_POST['id'] ?? 0);

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$this->verificarPertinencia($cmd_id)) {
            $_SESSION['error'] = 'Acceso denegado o token CSRF inválido.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $db = Database::getConnection();
        
        try {
            // Eliminar evaluación verificando la asignación correspondiente
            $stmt = $db->prepare("DELETE FROM evaluaciones WHERE id = ? AND curso_materia_docente_id = ?");
            if ($stmt->execute([$id, $cmd_id])) {
                $_SESSION['success'] = 'Evaluación eliminada correctamente.';
            } else {
                $_SESSION['error'] = 'No se pudo eliminar la evaluación.';
            }
        } catch (\PDOException $e) {
            // Código 23000 = restricción de clave foránea (tiene notas cargadas)
            if ($e->getCode() == '23000') {
                $_SESSION['error'] = 'No se puede eliminar esta evaluación porque ya tiene calificaciones cargadas para los estudiantes.';
            } else {
                $_SESSION['error'] = 'Error al intentar eliminar la evaluación: ' . $e->getMessage();
            }
        }

        header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
        exit;
    }
}
