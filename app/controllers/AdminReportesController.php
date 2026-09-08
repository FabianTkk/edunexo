<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminReportesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $cursos = $db->query("
            SELECT c.*, COUNT(DISTINCT r.id) AS total_reportes
            FROM cursos c
            LEFT JOIN estudiantes e ON e.curso_id = c.id
            LEFT JOIN reportes r ON r.estudiante_id = e.id
            WHERE c.activo = 1
            GROUP BY c.id
            ORDER BY c.nombre
        ")->fetchAll();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/reportes.php';
    }

    public function porCurso() {
        $db = Database::getConnection();
        $curso_id  = (int)($_GET['curso_id']  ?? 0);
        $filtro    = SecurityHelper::sanitize($_GET['filtro'] ?? 'semana');
        $fecha_ref = SecurityHelper::sanitize($_GET['fecha']  ?? date('Y-m-d'));

        if ($filtro === 'semana') {
            $inicio = date('Y-m-d', strtotime('monday this week', strtotime($fecha_ref)));
            $fin    = date('Y-m-d', strtotime('sunday this week', strtotime($fecha_ref)));
        } else {
            $inicio = date('Y-m-01', strtotime($fecha_ref));
            $fin    = date('Y-m-t',  strtotime($fecha_ref));
        }

        $stmt = $db->prepare("
            SELECT r.*, e.nombre_completo, e.ci,
                   u.nombre AS docente_nombre
            FROM reportes r
            JOIN estudiantes e ON e.id = r.estudiante_id
            LEFT JOIN usuarios u ON u.id = r.usuario_id
            WHERE e.curso_id = ?
              AND r.periodo_semana BETWEEN ? AND ?
            ORDER BY e.nombre_completo, r.periodo_semana DESC
        ");
        $stmt->execute([$curso_id, $inicio, $fin]);
        $reportes = $stmt->fetchAll();

        $curso = $db->prepare("SELECT * FROM cursos WHERE id=?");
        $curso->execute([$curso_id]);
        $curso = $curso->fetch();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/reportes_detalle.php';
    }

    public function porCI() {
        $db  = Database::getConnection();
        $ci  = SecurityHelper::sanitize($_GET['ci'] ?? '');

        $stmt = $db->prepare("
            SELECT r.*, e.nombre_completo, e.ci,
                   u.nombre AS docente_nombre,
                   n.puntaje_obtenido, n.observacion AS nota_obs,
                   ev.titulo AS eval_titulo, m.nombre AS materia_nombre,
                   te.nombre AS tipo_nombre
            FROM reportes r
            JOIN estudiantes e ON e.id = r.estudiante_id
            LEFT JOIN usuarios u ON u.id = r.usuario_id
            LEFT JOIN notas n ON n.evaluacion_id IN (
                SELECT ev2.id FROM evaluaciones ev2
                JOIN curso_materia_docente cmd ON cmd.id = ev2.curso_materia_docente_id
                WHERE cmd.curso_id = (SELECT curso_id FROM estudiantes WHERE ci=? LIMIT 1)
                  AND ev2.fecha BETWEEN DATE_SUB(r.periodo_semana, INTERVAL 6 DAY) AND r.periodo_semana
            ) AND n.estudiante_id = e.id
            LEFT JOIN evaluaciones ev ON ev.id = n.evaluacion_id
            LEFT JOIN curso_materia_docente cmd2 ON cmd2.id = ev.curso_materia_docente_id
            LEFT JOIN materias m ON m.id = cmd2.materia_id
            LEFT JOIN tipo_evaluacion te ON te.id = ev.tipo_evaluacion_id
            WHERE e.ci = ?
            ORDER BY r.periodo_semana DESC
        ");
        $stmt->execute([$ci, $ci]);
        $reportes = $stmt->fetchAll();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/reportes_ci.php';
    }
}
