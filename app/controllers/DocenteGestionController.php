<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class DocenteGestionController {
    private ?array $cacheAsignaciones = null;

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
            header('Location: /edunexo/login'); exit;
        }
    }

    /** Asignaciones vigentes del docente: asignacion, curso y materia activos. */
    private function asignaciones(): array {
        if ($this->cacheAsignaciones !== null) return $this->cacheAsignaciones;
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT cmd.id, cmd.curso_id, c.nombre AS curso, m.nombre AS materia FROM curso_materia_docente cmd JOIN cursos c ON c.id=cmd.curso_id JOIN materias m ON m.id=cmd.materia_id WHERE cmd.docente_id=? AND cmd.activo=1 AND c.activo=1 AND m.activo=1 ORDER BY c.nombre,m.nombre");
        $stmt->execute([$_SESSION['user_id']]);
        return $this->cacheAsignaciones = $stmt->fetchAll();
    }
    private function asignacion(int $cmdId): array|false {
        foreach ($this->asignaciones() as $asignacion) if ((int)$asignacion['id'] === $cmdId) return $asignacion;
        return false;
    }
    private function volver(string $ruta): void { header('Location: /edunexo/' . $ruta); exit; }

    /** Fecha real en formato Y-m-d (rechaza 2026-13-45 y valores que no sean texto). */
    private function fechaValida($fecha): bool {
        if (!is_string($fecha)) return false;
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }
    /** Lunes de la semana a la que pertenece la fecha. El reporte semanal cubre lunes a viernes. */
    private function lunesDeSemana(string $fecha): string {
        return (new \DateTime($fecha))->modify('monday this week')->format('Y-m-d');
    }
    private function esFinDeSemana(string $fecha): bool { return (int)date('N', strtotime($fecha)) >= 6; }
    /** Dias distintos con falta no justificada en la semana (mismo criterio que el mensaje de WhatsApp). */
    private function diasAusentes($db, int $estudianteId, string $lunes): int {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT fecha) FROM asistencias WHERE estudiante_id=? AND presente=0 AND justificada=0 AND fecha BETWEEN ? AND DATE_ADD(?, INTERVAL 4 DAY)");
        $stmt->execute([$estudianteId, $lunes, $lunes]);
        return (int)$stmt->fetchColumn();
    }

    public function estudiantes(): void {
        $asignaciones = $this->asignaciones();
        $cmdId = (int)($_GET['cmd_id'] ?? ($asignaciones[0]['id'] ?? 0));
        $asignacion = $this->asignacion($cmdId);
        if (!$asignacion) $this->volver('docente/materias');

        $hoy = date('Y-m-d');
        $fechaGet = $_GET['fecha'] ?? null;
        if ($this->fechaValida($fechaGet) && $fechaGet <= $hoy) {
            $fecha = $fechaGet;
        } else {
            // Sin fecha valida: hoy, o el viernes anterior si hoy es fin de semana.
            $fecha = $this->esFinDeSemana($hoy) ? date('Y-m-d', strtotime('last friday')) : $hoy;
        }
        $esFinDeSemana = $this->esFinDeSemana($fecha);
        $inicioMes = date('Y-m-01', strtotime($fecha));
        $finMes = date('Y-m-t', strtotime($fecha));

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT e.id, e.ci, e.nombre_completo, a.id AS asistencia_id, a.presente, a.justificada, a.observacion,
                (SELECT COUNT(*) FROM asistencias f WHERE f.estudiante_id=e.id AND f.curso_materia_docente_id=? AND f.presente=0 AND f.justificada=0 AND f.fecha BETWEEN ? AND ?) AS faltas_mes,
                (SELECT COUNT(*) FROM asistencias j WHERE j.estudiante_id=e.id AND j.curso_materia_docente_id=? AND j.presente=0 AND j.justificada=1 AND j.fecha BETWEEN ? AND ?) AS justificadas_mes
            FROM estudiantes e
            LEFT JOIN asistencias a ON a.estudiante_id=e.id AND a.curso_materia_docente_id=? AND a.fecha=?
            WHERE e.curso_id=? AND e.estado='activo'
            ORDER BY e.nombre_completo");
        $stmt->execute([$cmdId, $inicioMes, $finMes, $cmdId, $inicioMes, $finMes, $cmdId, $fecha, $asignacion['curso_id']]);
        $estudiantes = $stmt->fetchAll();

        // Cuantos estudiantes ya tienen asistencia guardada ese dia (0 = sin registrar).
        $totalEstudiantes = count($estudiantes);
        $conRegistro = count(array_filter($estudiantes, fn($e) => $e['asistencia_id'] !== null));
        // Otras materias del mismo curso que da este docente (para "aplicar a todas").
        $materiasCurso = count(array_filter($asignaciones, fn($a) => (int)$a['curso_id'] === (int)$asignacion['curso_id']));

        require __DIR__ . '/../views/docente/estudiantes.php';
    }

    public function guardarAsistencia(): void {
        $cmdId = (int)($_POST['cmd_id'] ?? 0);
        $asignacion = $this->asignacion($cmdId);
        $fecha = $_POST['fecha'] ?? '';
        $registros = $_POST['asistencia'] ?? [];
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '') || !$asignacion || !$this->fechaValida($fecha) || !is_array($registros)) {
            $_SESSION['error'] = 'Datos de asistencia inválidos.'; $this->volver('docente/estudiantes');
        }
        $retorno = 'docente/estudiantes?cmd_id=' . $cmdId . '&fecha=' . $fecha;
        if ($fecha > date('Y-m-d')) {
            $_SESSION['error'] = 'No se puede registrar asistencia de una fecha futura.'; $this->volver($retorno);
        }
        if ($this->esFinDeSemana($fecha)) {
            $_SESSION['error'] = 'Los fines de semana no se registran: el reporte semanal cubre de lunes a viernes.'; $this->volver($retorno);
        }

        // Materias donde se guarda: la elegida y, si se pide, todas las del docente en ese curso.
        $destinos = [$cmdId];
        if (!empty($_POST['aplicar_todas'])) {
            foreach ($this->asignaciones() as $a) {
                if ((int)$a['curso_id'] === (int)$asignacion['curso_id']) $destinos[] = (int)$a['id'];
            }
            $destinos = array_values(array_unique($destinos));
        }

        $db = Database::getConnection();
        $permitidos = $db->prepare("SELECT id FROM estudiantes WHERE id=? AND curso_id=? AND estado='activo'");
        $upsert = $db->prepare("INSERT INTO asistencias (estudiante_id,curso_materia_docente_id,fecha,presente,justificada,observacion) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE presente=VALUES(presente),justificada=VALUES(justificada),observacion=VALUES(observacion)");
        $db->beginTransaction();
        try {
            foreach ($registros as $id => $dato) {
                if (!is_array($dato)) throw new \RuntimeException('Registro inválido.');
                $permitidos->execute([(int)$id, $asignacion['curso_id']]);
                if (!$permitidos->fetch()) throw new \RuntimeException('Estudiante inválido.');
                $presente = isset($dato['presente']) ? 1 : 0;
                $justificada = $presente ? 0 : (isset($dato['justificada']) ? 1 : 0);
                // Texto plano: se escapa al mostrar (las vistas usan htmlspecialchars).
                $observacion = mb_substr(SecurityHelper::sanitize((string)($dato['observacion'] ?? '')), 0, 500);
                foreach ($destinos as $destino) {
                    $upsert->execute([(int)$id, $destino, $fecha, $presente, $justificada, $observacion]);
                }
            }
            $db->commit();
            $_SESSION['success'] = count($destinos) > 1 ? 'Asistencia guardada en ' . count($destinos) . ' materias.' : 'Asistencia guardada.';
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['error'] = 'No se pudo guardar la asistencia.';
        }
        $this->volver($retorno);
    }

    public function reportes(): void {
        $db=Database::getConnection(); $uid=(int)$_SESSION['user_id'];
        $est=$db->prepare("SELECT DISTINCT e.id,e.nombre_completo,e.curso FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE cmd.docente_id=? AND cmd.activo=1 AND e.estado='activo' ORDER BY e.nombre_completo"); $est->execute([$uid]); $estudiantes=$est->fetchAll();
        // ausencias_reales: dias con falta no justificada de esa semana, calculados en vivo desde asistencias.
        $stmt=$db->prepare("SELECT r.*,e.nombre_completo,s.id AS solicitud_pendiente,(r.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)) AS editable,(SELECT COUNT(DISTINCT a.fecha) FROM asistencias a WHERE a.estudiante_id=r.estudiante_id AND a.presente=0 AND a.justificada=0 AND a.fecha BETWEEN DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY) AND DATE_ADD(DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY), INTERVAL 4 DAY)) AS ausencias_reales FROM reportes r JOIN estudiantes e ON e.id=r.estudiante_id LEFT JOIN solicitudes_cambio_reportes s ON s.reporte_id=r.id AND s.docente_id=r.usuario_id AND s.estado='pendiente' WHERE r.usuario_id=? ORDER BY r.periodo_semana DESC,r.created_at DESC"); $stmt->execute([$uid]); $reportes=$stmt->fetchAll();
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
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$this->fechaValida($fecha)) { $_SESSION['error']='Datos del reporte inválidos.'; $this->volver('docente/reportes'); }
        $fecha=$this->lunesDeSemana($fecha);
        $db=Database::getConnection(); $ok=$db->prepare("SELECT 1 FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE e.id=? AND e.estado='activo' AND cmd.docente_id=? AND cmd.activo=1"); $ok->execute([$estId,$uid]); if(!$ok->fetch()) { $_SESSION['error']='No tenés acceso a ese estudiante.'; $this->volver('docente/reportes'); }
        $cal=in_array($_POST['calificacion_general']??'', ['Logrado','En Proceso','Aun no logrado','No evaluado'],true)?$_POST['calificacion_general']:'No evaluado'; $com=in_array($_POST['comportamiento']??'', ['Excelente','Bueno','Regular','Requiere Atencion'],true)?$_POST['comportamiento']:'Bueno';
        // Las ausencias salen de la asistencia registrada, no se escriben a mano.
        $dias=$this->diasAusentes($db,$estId,$fecha);
        $db->prepare("INSERT INTO reportes (estudiante_id,usuario_id,periodo_semana,calificacion_general,dias_ausente,tareas_incompletas,comportamiento,incidentes_disciplinarios) VALUES (?,?,?,?,?,?,?,?)")->execute([$estId,$uid,$fecha,$cal,$dias,max(0,(int)($_POST['tareas_incompletas']??0)),$com,SecurityHelper::sanitize($_POST['incidentes']??'')]);
        $nuevoReporteId = (int)$db->lastInsertId();
        if ($nuevoReporteId > 0) {
            $stmtTutor = $db->prepare("SELECT t.telefono FROM tutores t JOIN tutores_estudiantes te ON te.tutor_id = t.id WHERE te.estudiante_id = ? AND t.activo = 1 LIMIT 1");
            $stmtTutor->execute([$estId]);
            $telTutor = $stmtTutor->fetchColumn() ?: '';
            $db->prepare("INSERT INTO envios_wa (reporte_id, destinatario_telefono, estado, fecha_hora_envio) VALUES (?, ?, 'pendiente', NULL)")->execute([$nuevoReporteId, $telTutor]);
        }
        $_SESSION['success']='Reporte creado y preparado para envío por WhatsApp.'; $this->volver('docente/reportes');
    }
    public function actualizarReporte(): void {
        $uid=(int)$_SESSION['user_id']; $id=(int)($_POST['reporte_id']??0); $estId=(int)($_POST['estudiante_id']??0); $fecha=$_POST['periodo_semana']??'';
        if(!SecurityHelper::validateCsrfToken($_POST['csrf_token']??'') || !$id || !$this->fechaValida($fecha)) { $_SESSION['error']='Datos del reporte inválidos.'; $this->volver('docente/reportes'); }
        $fecha=$this->lunesDeSemana($fecha);
        $db=Database::getConnection(); $ok=$db->prepare("SELECT 1 FROM estudiantes e JOIN curso_materia_docente cmd ON cmd.curso_id=e.curso_id WHERE e.id=? AND e.estado='activo' AND cmd.docente_id=? AND cmd.activo=1"); $ok->execute([$estId,$uid]); if(!$ok->fetch()) { $_SESSION['error']='No tenés acceso a ese estudiante.'; $this->volver('docente/reportes'); }
        $cal=in_array($_POST['calificacion_general']??'', ['Logrado','En Proceso','Aun no logrado','No evaluado'],true)?$_POST['calificacion_general']:'No evaluado'; $com=in_array($_POST['comportamiento']??'', ['Excelente','Bueno','Regular','Requiere Atencion'],true)?$_POST['comportamiento']:'Bueno';
        $dias=$this->diasAusentes($db,$estId,$fecha);
        $stmt=$db->prepare("UPDATE reportes SET estudiante_id=?,periodo_semana=?,calificacion_general=?,dias_ausente=?,tareas_incompletas=?,comportamiento=?,incidentes_disciplinarios=? WHERE id=? AND usuario_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        $stmt->execute([$estId,$fecha,$cal,$dias,max(0,(int)($_POST['tareas_incompletas']??0)),$com,SecurityHelper::sanitize($_POST['incidentes']??''),$id,$uid]);
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
