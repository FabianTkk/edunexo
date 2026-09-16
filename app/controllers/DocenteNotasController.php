<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class DocenteNotasController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    private function getEvaluationWithAccess(int $eval_id) {
        $db = Database::getConnection();
        // Verificar que la evaluacion exista y el docente tenga acceso al CMD
        $stmt = $db->prepare("
            SELECT e.*, cmd.curso_id, cmd.materia_id, c.nombre as curso_nombre, m.nombre as materia_nombre
            FROM evaluaciones e
            JOIN curso_materia_docente cmd ON e.curso_materia_docente_id = cmd.id
            JOIN cursos c ON cmd.curso_id = c.id
            JOIN materias m ON cmd.materia_id = m.id
            WHERE e.id = ? AND cmd.docente_id = ?
        ");
        $stmt->execute([$eval_id, $_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function index() {
        $eval_id = (int)($_GET['eval_id'] ?? 0);
        
        $evaluacion = $this->getEvaluationWithAccess($eval_id);
        
        if (!$evaluacion) {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $db = Database::getConnection();

        // Obtener alumnos del curso y hacer LEFT JOIN con notas
        $stmt = $db->prepare("
            SELECT est.id AS estudiante_id, est.nombre_completo, est.ci,
                   n.puntaje_obtenido, n.observacion
            FROM estudiantes est
            LEFT JOIN notas n ON n.estudiante_id = est.id AND n.evaluacion_id = ?
            WHERE est.curso_id = ? AND est.estado = 'activo'
            ORDER BY est.nombre_completo
        ");
        $stmt->execute([$eval_id, $evaluacion['curso_id']]);
        $estudiantes = $stmt->fetchAll();

        require __DIR__ . '/../views/docente/notas.php';
    }

    public function bulkStore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/docente/materias');
            exit;
        }

        $eval_id = (int)($_POST['evaluacion_id'] ?? 0);
        $cmd_id = (int)($_POST['cmd_id'] ?? 0);

        $evaluacion = $this->getEvaluationWithAccess($eval_id);
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$evaluacion) {
            $_SESSION['error'] = 'Acceso denegado o token CSRF inválido.';
            header("Location: /edunexo/docente/evaluaciones?cmd_id=$cmd_id");
            exit;
        }

        $notas = $_POST['notas'] ?? []; // Expected structure: notas[estudiante_id]['puntaje'] and notas[estudiante_id]['observacion']

        if (empty($notas)) {
            $_SESSION['error'] = 'No se recibieron notas para guardar.';
            header("Location: /edunexo/docente/notas?eval_id=$eval_id");
            exit;
        }

        $db = Database::getConnection();
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO notas (evaluacion_id, estudiante_id, puntaje_obtenido, observacion) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE puntaje_obtenido = VALUES(puntaje_obtenido), observacion = VALUES(observacion)
            ");

            foreach ($notas as $est_id => $data) {
                $estudianteId = filter_var($est_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($estudianteId === false || !is_array($data)) {
                    throw new \InvalidArgumentException('Se recibieron datos de estudiante inválidos.');
                }

                $stmtEstudiante = $db->prepare(
                    'SELECT id FROM estudiantes WHERE id = ? AND curso_id = ? AND estado = \'activo\''
                );
                $stmtEstudiante->execute([$estudianteId, $evaluacion['curso_id']]);
                if (!$stmtEstudiante->fetch()) {
                    throw new \InvalidArgumentException('Uno de los estudiantes no pertenece al curso de esta evaluación.');
                }

                $puntaje = trim($data['puntaje'] ?? '');
                if ($puntaje !== '' && (!is_numeric($puntaje) || (float)$puntaje < 0 || (float)$puntaje > (float)$evaluacion['puntaje_maximo'])) {
                    throw new \InvalidArgumentException('Cada puntaje debe estar entre 0 y el máximo de la evaluación.');
                }
                $puntaje_val = ($puntaje === '') ? null : (float)$puntaje;
                
                $obs = SecurityHelper::sanitize($data['observacion'] ?? '');

                $stmt->execute([$eval_id, $estudianteId, $puntaje_val, $obs]);
            }

            $db->commit();
            $_SESSION['success'] = 'Las notas se han guardado correctamente.';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al guardar las notas: ' . $e->getMessage();
        }

        header("Location: /edunexo/docente/notas?eval_id=$eval_id");
        exit;
    }
}
