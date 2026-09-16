<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class DocenteGestionController {
    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
            header('Location: /edunexo/login'); exit;
        }
    }

    private function asignaciones(): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT cmd.id, cmd.curso_id, c.nombre AS curso, m.nombre AS materia FROM curso_materia_docente cmd JOIN cursos c ON c.id=cmd.curso_id JOIN materias m ON m.id=cmd.materia_id WHERE cmd.docente_id=? AND cmd.activo=1 ORDER BY c.nombre,m.nombre");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll();
    }
    private function asignacion(int $cmdId): array|false {
        foreach ($this->asignaciones() as $asignacion) if ((int)$asignacion['id'] === $cmdId) return $asignacion;
        return false;
    }
    private function volver(string $ruta): void { header('Location: /edunexo/' . $ruta); exit; }

    public function estudiantes(): void {
        $asignaciones = $this->asignaciones();
        $cmdId = (int)($_GET['cmd_id'] ?? ($asignaciones[0]['id'] ?? 0));
        $asignacion = $this->asignacion($cmdId);
        if (!$asignacion) $this->volver('docente/materias');
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'] ?? '') ? $_GET['fecha'] : date('Y-m-d');
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT e.id,e.ci,e.nombre_completo,a.presente,a.justificada,a.observacion FROM estudiantes e LEFT JOIN asistencias a ON a.estudiante_id=e.id AND a.curso_materia_docente_id=? AND a.fecha=? WHERE e.curso_id=? AND e.estado='activo' ORDER BY e.nombre_completo");
        $stmt->execute([$cmdId, $fecha, $asignacion['curso_id']]); $estudiantes = $stmt->fetchAll();
        require __DIR__ . '/../views/docente/estudiantes.php';
    }
    public function guardarAsistencia(): void {
        $cmdId=(int)($_POST['cmd_id']??0); $asignacion=$this->asignacion($cmdId);
        $fecha=$_POST['fecha']??''; $registros=$_POST['asistencia']??[];
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$asignacion || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha) || !is_array($registros)) { $_SESSION['error']='Datos de asistencia inválidos.'; $this->volver('docente/estudiantes'); }
        $db=Database::getConnection(); $permitidos=$db->prepare("SELECT id FROM estudiantes WHERE id=? AND curso_id=? AND estado='activo'");
        $upsert=$db->prepare("INSERT INTO asistencias (estudiante_id,curso_materia_docente_id,fecha,presente,justificada,observacion) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE presente=VALUES(presente),justificada=VALUES(justificada),observacion=VALUES(observacion)");
        $db->beginTransaction(); try { foreach($registros as $id=>$dato) { $permitidos->execute([(int)$id,$asignacion['curso_id']]); if(!$permitidos->fetch()) throw new \RuntimeException('Estudiante inválido.'); $presente=isset($dato['presente'])?1:0; $justificada=$presente?0:(isset($dato['justificada'])?1:0); $upsert->execute([(int)$id,$cmdId,$fecha,$presente,$justificada,SecurityHelper::sanitize($dato['observacion']??'')]); } $db->commit(); $_SESSION['success']='Asistencia guardada.'; } catch(\Throwable $e) { $db->rollBack(); $_SESSION['error']='No se pudo guardar la asistencia.'; }
        $this->volver('docente/estudiantes?cmd_id='.$cmdId.'&fecha='.$fecha);
    }
    public function reportes(): void {
        $db=Database::getConnection(); $uid=(int)$_SESSION['user_id'];
        $est=$db->prepare("SELECT DISTINCT e.id,e.nombre_completo,e.curso FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE cmd.docente_id=? AND cmd.activo=1 AND e.estado='activo' ORDER BY e.nombre_completo"); $est->execute([$uid]); $estudiantes=$est->fetchAll();
        $stmt=$db->prepare("SELECT r.*,e.nombre_completo,s.id AS solicitud_pendiente FROM reportes r JOIN estudiantes e ON e.id=r.estudiante_id LEFT JOIN solicitudes_cambio_reportes s ON s.reporte_id=r.id AND s.docente_id=r.usuario_id AND s.estado='pendiente' WHERE r.usuario_id=? ORDER BY r.periodo_semana DESC,r.created_at DESC"); $stmt->execute([$uid]); $reportes=$stmt->fetchAll();
        $reporteEdicion = null;
        $editarId = (int)($_GET['editar'] ?? 0);
        if ($editarId) {
            $editar = $db->prepare("SELECT r.* FROM reportes r WHERE r.id=? AND r.usuario_id=? AND r.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
            $editar->execute([$editarId, $uid]);
            $reporteEdicion = $editar->fetch();
            if (!$reporteEdicion) { $_SESSION['error'] = 'Ese reporte ya no está disponible para edición directa.'; $this->volver('docente/reportes'); }
        }
        require __DIR__ . '/../views/docente/reportes.php';
    }
    public function guardarReporte(): void {
        $uid=(int)$_SESSION['user_id']; $estId=(int)($_POST['estudiante_id']??0); $fecha=$_POST['periodo_semana']??'';
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) { $_SESSION['error']='Datos del reporte inválidos.'; $this->volver('docente/reportes'); }
        $db=Database::getConnection(); $ok=$db->prepare("SELECT 1 FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE e.id=? AND e.estado='activo' AND cmd.docente_id=? AND cmd.activo=1"); $ok->execute([$estId,$uid]); if(!$ok->fetch()) { $_SESSION['error']='No tenés acceso a ese estudiante.'; $this->volver('docente/reportes'); }
        $cal=in_array($_POST['calificacion_general']??'', ['Logrado','En Proceso','Aun no logrado','No evaluado'],true)?$_POST['calificacion_general']:'No evaluado'; $com=in_array($_POST['comportamiento']??'', ['Excelente','Bueno','Regular','Requiere Atencion'],true)?$_POST['comportamiento']:'Bueno';
        $db->prepare("INSERT INTO reportes (estudiante_id,usuario_id,periodo_semana,calificacion_general,dias_ausente,tareas_incompletas,comportamiento,incidentes_disciplinarios) VALUES (?,?,?,?,?,?,?,?)")->execute([$estId,$uid,$fecha,$cal,max(0,(int)($_POST['dias_ausente']??0)),max(0,(int)($_POST['tareas_incompletas']??0)),$com,SecurityHelper::sanitize($_POST['incidentes']??'')]); $_SESSION['success']='Reporte creado.'; $this->volver('docente/reportes');
    }
    public function actualizarReporte(): void {
        $uid=(int)$_SESSION['user_id']; $id=(int)($_POST['reporte_id']??0); $estId=(int)($_POST['estudiante_id']??0); $fecha=$_POST['periodo_semana']??'';
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$id || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)) { $_SESSION['error']='Datos del reporte inválidos.'; $this->volver('docente/reportes'); }
        $db=Database::getConnection(); $ok=$db->prepare("SELECT 1 FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE e.id=? AND e.estado='activo' AND cmd.docente_id=? AND cmd.activo=1"); $ok->execute([$estId,$uid]); if(!$ok->fetch()) { $_SESSION['error']='No tenés acceso a ese estudiante.'; $this->volver('docente/reportes'); }
        $cal=in_array($_POST['calificacion_general']??'', ['Logrado','En Proceso','Aun no logrado','No evaluado'],true)?$_POST['calificacion_general']:'No evaluado'; $com=in_array($_POST['comportamiento']??'', ['Excelente','Bueno','Regular','Requiere Atencion'],true)?$_POST['comportamiento']:'Bueno';
        $stmt=$db->prepare("UPDATE reportes SET estudiante_id=?,periodo_semana=?,calificacion_general=?,dias_ausente=?,tareas_incompletas=?,comportamiento=?,incidentes_disciplinarios=? WHERE id=? AND usuario_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        $stmt->execute([$estId,$fecha,$cal,max(0,(int)($_POST['dias_ausente']??0)),max(0,(int)($_POST['tareas_incompletas']??0)),$com,SecurityHelper::sanitize($_POST['incidentes']??''),$id,$uid]);
        $_SESSION[$stmt->rowCount() ? 'success' : 'error'] = $stmt->rowCount() ? 'Reporte actualizado.' : 'El plazo de 48 horas venció o el reporte no existe.'; $this->volver('docente/reportes');
    }
    public function eliminarReporte(): void {
        $uid=(int)$_SESSION['user_id']; $id=(int)($_POST['reporte_id']??0);
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$id) { $_SESSION['error']='Solicitud inválida.'; $this->volver('docente/reportes'); }
        $stmt=Database::getConnection()->prepare("DELETE FROM reportes WHERE id=? AND usuario_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)"); $stmt->execute([$id,$uid]);
        $_SESSION[$stmt->rowCount() ? 'success' : 'error'] = $stmt->rowCount() ? 'Reporte eliminado.' : 'El plazo de 48 horas venció o el reporte no existe.'; $this->volver('docente/reportes');
    }
    public function solicitarCambioReporte(): void {
        $uid=(int)$_SESSION['user_id']; $id=(int)($_POST['reporte_id']??0);
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$id) { $_SESSION['error']='Solicitud inválida.'; $this->volver('docente/reportes'); }
        $motivo=SecurityHelper::sanitize($_POST['motivo']??'Solicitud de modificación fuera del plazo de 48 horas.');
        $stmt=Database::getConnection()->prepare("INSERT INTO solicitudes_cambio_reportes (reporte_id,docente_id,motivo) SELECT r.id,r.usuario_id,? FROM reportes r WHERE r.id=? AND r.usuario_id=? AND r.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR) AND NOT EXISTS (SELECT 1 FROM solicitudes_cambio_reportes s WHERE s.reporte_id=r.id AND s.docente_id=r.usuario_id AND s.estado='pendiente')"); $stmt->execute([$motivo ?: 'Solicitud de modificación fuera del plazo de 48 horas.',$id,$uid]);
        $_SESSION[$stmt->rowCount() ? 'success' : 'error'] = $stmt->rowCount() ? 'Solicitud enviada al administrador.' : 'Ya existe una solicitud pendiente o el reporte aún permite edición directa.'; $this->volver('docente/reportes');
    }
    public function enviosWhatsApp(): void {
        $db = Database::getConnection();
        $uid = (int)$_SESSION['user_id'];
        $pendientes = $db->prepare("SELECT r.id, e.nombre_completo, e.curso, r.periodo_semana, t.nombre_completo AS tutor, t.telefono
            FROM reportes r
            JOIN estudiantes e ON e.id = r.estudiante_id
            JOIN tutores_estudiantes te ON te.estudiante_id = e.id
            JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
            LEFT JOIN envios_wa wa ON wa.reporte_id = r.id AND wa.destinatario_telefono = t.telefono
            WHERE r.usuario_id = ? AND wa.id IS NULL
            ORDER BY r.periodo_semana DESC, e.nombre_completo");
        $pendientes->execute([$uid]);
        $pendientes = $pendientes->fetchAll();

        $historial = $db->prepare("SELECT wa.estado, wa.destinatario_telefono, wa.fecha_hora_envio, e.nombre_completo, e.curso, r.periodo_semana
            FROM envios_wa wa
            JOIN reportes r ON r.id = wa.reporte_id
            JOIN estudiantes e ON e.id = r.estudiante_id
            WHERE r.usuario_id = ?
            ORDER BY wa.fecha_hora_envio DESC LIMIT 12");
        $historial->execute([$uid]);
        $historial = $historial->fetchAll();
        require __DIR__ . '/../views/docente/envios_wa.php';
    }
    public function prepararEnviosWhatsApp(): void {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) { $_SESSION['error'] = 'Token inválido.'; $this->volver('docente/envios-wa'); }
        $db = Database::getConnection();
        $uid = (int)$_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO envios_wa (reporte_id, destinatario_telefono, estado)
            SELECT r.id, t.telefono, 'pendiente'
            FROM reportes r
            JOIN estudiantes e ON e.id = r.estudiante_id
            JOIN tutores_estudiantes te ON te.estudiante_id = e.id
            JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
            LEFT JOIN envios_wa wa ON wa.reporte_id = r.id AND wa.destinatario_telefono = t.telefono
            WHERE r.usuario_id = ? AND wa.id IS NULL");
        $stmt->execute([$uid]);
        $_SESSION['success'] = $stmt->rowCount() . ' envío(s) preparado(s) para WhatsApp.';
        $this->volver('docente/envios-wa');
    }
    public function calendario(): void { $db=Database::getConnection(); $stmt=$db->prepare("SELECT e.fecha,e.titulo,'Evaluación' AS tipo,m.nombre AS materia FROM evaluaciones e JOIN curso_materia_docente cmd ON cmd.id=e.curso_materia_docente_id JOIN materias m ON m.id=cmd.materia_id WHERE cmd.docente_id=? UNION ALL SELECT a.fecha_aviso,a.titulo,'Aviso',m.nombre FROM avisos a JOIN curso_materia_docente cmd ON cmd.id=a.curso_materia_docente_id JOIN materias m ON m.id=cmd.materia_id WHERE cmd.docente_id=? ORDER BY fecha DESC"); $stmt->execute([$_SESSION['user_id'],$_SESSION['user_id']]); $eventos=$stmt->fetchAll(); require __DIR__ . '/../views/docente/calendario.php'; }
    public function mensajes(): void { $asignaciones=$this->asignaciones(); $db=Database::getConnection(); $stmt=$db->prepare("SELECT a.*,m.nombre AS materia,c.nombre AS curso FROM avisos a JOIN curso_materia_docente cmd ON cmd.id=a.curso_materia_docente_id JOIN materias m ON m.id=cmd.materia_id JOIN cursos c ON c.id=cmd.curso_id WHERE cmd.docente_id=? ORDER BY a.fecha_aviso DESC"); $stmt->execute([$_SESSION['user_id']]); $avisos=$stmt->fetchAll(); require __DIR__ . '/../views/docente/mensajes.php'; }
    public function guardarMensaje(): void { $cmdId=(int)($_POST['cmd_id']??0); if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'')||!$this->asignacion($cmdId)){$_SESSION['error']='Aviso inválido.';$this->volver('docente/mensajes');} $titulo=SecurityHelper::sanitize($_POST['titulo']??'');$fecha=$_POST['fecha_aviso']??'';if(!$titulo||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)){$_SESSION['error']='Completá título y fecha.';$this->volver('docente/mensajes');} Database::getConnection()->prepare("INSERT INTO avisos (curso_materia_docente_id,titulo,descripcion,fecha_aviso,aplica_a_todos) VALUES (?,?,?,?,1)")->execute([$cmdId,$titulo,SecurityHelper::sanitize($_POST['descripcion']??''),$fecha]);$_SESSION['success']='Aviso publicado para el curso.';$this->volver('docente/mensajes'); }
    public function perfil(): void { $db=Database::getConnection();$stmt=$db->prepare('SELECT nombre,username,email FROM usuarios WHERE id=?');$stmt->execute([$_SESSION['user_id']]);$perfil=$stmt->fetch();require __DIR__ . '/../views/docente/perfil.php'; }
    public function guardarPerfil(): void { if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'')){$_SESSION['error']='Token inválido.';$this->volver('docente/perfil');}$nombre=SecurityHelper::sanitize($_POST['nombre']??'');$email=SecurityHelper::sanitize($_POST['email']??'');if(!$nombre){$_SESSION['error']='El nombre es obligatorio.';$this->volver('docente/perfil');}$db=Database::getConnection();$db->prepare('UPDATE usuarios SET nombre=?,email=? WHERE id=?')->execute([$nombre,$email,$_SESSION['user_id']]);$_SESSION['nombre']=$nombre;$_SESSION['success']='Perfil actualizado.';$this->volver('docente/perfil'); }
}
