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

        $solicitudesPendientes = $db->query("
            SELECT s.id, s.motivo, s.created_at, r.id AS reporte_id, r.periodo_semana,
                   e.nombre_completo AS estudiante, u.nombre AS docente
            FROM solicitudes_cambio_reportes s
            JOIN reportes r ON r.id = s.reporte_id
            JOIN estudiantes e ON e.id = r.estudiante_id
            JOIN usuarios u ON u.id = s.docente_id
            WHERE s.estado = 'pendiente'
            ORDER BY s.created_at ASC
        ")->fetchAll();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/reportes.php';
    }

    public function gestionar() {
        $id = (int)($_GET['reporte_id'] ?? 0);
        if (!$id) { header('Location: /edunexo/admin/reportes'); exit; }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT r.*, e.nombre_completo, e.curso, u.nombre AS docente_nombre
            FROM reportes r JOIN estudiantes e ON e.id=r.estudiante_id LEFT JOIN usuarios u ON u.id=r.usuario_id WHERE r.id=?");
        $stmt->execute([$id]); $reporte = $stmt->fetch();
        if (!$reporte) { $_SESSION['error']='El reporte ya no existe.'; header('Location: /edunexo/admin/reportes'); exit; }
        $estudiantes = $db->query("SELECT id,nombre_completo,curso FROM estudiantes WHERE estado='activo' ORDER BY nombre_completo")->fetchAll();
        $solicitudId = (int)($_GET['solicitud_id'] ?? 0);
        $solicitud = null;
        if ($solicitudId) { $s=$db->prepare("SELECT * FROM solicitudes_cambio_reportes WHERE id=? AND reporte_id=? AND estado='pendiente'"); $s->execute([$solicitudId,$id]); $solicitud=$s->fetch(); }
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/reportes_gestionar.php';
    }

    public function actualizar() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) { $_SESSION['error']='Token inválido.'; header('Location: /edunexo/admin/reportes'); exit; }
        $id=(int)($_POST['reporte_id']??0); $estId=(int)($_POST['estudiante_id']??0); $fecha=$_POST['periodo_semana']??'';
        $cal=in_array($_POST['calificacion_general']??'', ['Logrado','En Proceso','Aun no logrado','No evaluado'],true)?$_POST['calificacion_general']:'No evaluado';
        $com=in_array($_POST['comportamiento']??'', ['Excelente','Bueno','Regular','Requiere Atencion'],true)?$_POST['comportamiento']:'Bueno';
        if(!$id || !$estId || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) { $_SESSION['error']='Datos del reporte inválidos.'; header('Location: /edunexo/admin/reportes'); exit; }
        $db=Database::getConnection(); $existe=$db->prepare("SELECT id FROM estudiantes WHERE id=?"); $existe->execute([$estId]); if(!$existe->fetch()) { $_SESSION['error']='Estudiante inválido.'; header('Location: /edunexo/admin/reportes'); exit; }
        $stmt=$db->prepare("UPDATE reportes SET estudiante_id=?,periodo_semana=?,calificacion_general=?,dias_ausente=?,tareas_incompletas=?,comportamiento=?,incidentes_disciplinarios=? WHERE id=?");
        $stmt->execute([$estId,$fecha,$cal,max(0,(int)($_POST['dias_ausente']??0)),max(0,(int)($_POST['tareas_incompletas']??0)),$com,SecurityHelper::sanitize($_POST['incidentes']??''),$id]);
        $solicitudId=(int)($_POST['solicitud_id']??0); if($solicitudId) { $db->prepare("UPDATE solicitudes_cambio_reportes SET estado='atendida',admin_id=?,atendida_at=NOW() WHERE id=? AND reporte_id=? AND estado='pendiente'")->execute([$_SESSION['user_id'],$solicitudId,$id]); }
        $_SESSION['success']='Reporte actualizado correctamente.'; header('Location: /edunexo/admin/reportes'); exit;
    }

    public function eliminar() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) { $_SESSION['error']='Token inválido.'; header('Location: /edunexo/admin/reportes'); exit; }
        $id=(int)($_POST['reporte_id']??0); if(!$id) { $_SESSION['error']='Reporte inválido.'; header('Location: /edunexo/admin/reportes'); exit; }
        $stmt=Database::getConnection()->prepare("DELETE FROM reportes WHERE id=?"); $stmt->execute([$id]);
        $_SESSION[$stmt->rowCount() ? 'success' : 'error']=$stmt->rowCount()?'Reporte eliminado.':'El reporte ya no existe.'; header('Location: /edunexo/admin/reportes'); exit;
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
