<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;
use App\Helpers\WhatsAppHelper;

class AdminEnviosWaController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();

        // 1. Auto-sincronizar: encola lo que todavia no este en envios_wa (reportes de docentes y
        // estudiantes con asistencia o notas de la semana sin reporte). Ver CronController::sincronizarEnvios.
        // Se agrupa por estudiante+semana+tutor, asi que no se duplica nada ya encolado.
        try {
            CronController::sincronizarEnvios(CronController::semanaObjetivo());
        } catch (\Throwable $e) {
            error_log("[AdminEnviosWaController] Error en sincronización automática: " . $e->getMessage());
        }

        // 2. Filtros de búsqueda
        $estado = SecurityHelper::sanitize($_GET['estado'] ?? '');
        $desde  = SecurityHelper::sanitize($_GET['desde']  ?? '');
        $hasta  = SecurityHelper::sanitize($_GET['hasta']  ?? '');
        $buscar = SecurityHelper::sanitize($_GET['buscar'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if (!empty($estado) && in_array($estado, ['pendiente','enviado','entregado','error'])) {
            $where[]  = 'ew.estado = ?';
            $params[] = $estado;
        }

        if (!empty($desde)) {
            // Filtra por la semana del envio o por su fecha de envio
            $where[]  = '(ew.periodo_semana >= ? OR DATE(ew.fecha_hora_envio) >= ?)';
            $params[] = $desde;
            $params[] = $desde;
        }

        if (!empty($hasta)) {
            $where[]  = '(ew.periodo_semana <= ? OR DATE(ew.fecha_hora_envio) <= ?)';
            $params[] = $hasta;
            $params[] = $hasta;
        }

        if (!empty($buscar)) {
            $where[]  = '(e.nombre_completo LIKE ? OR e.ci LIKE ? OR t.nombre_completo LIKE ? OR ew.destinatario_telefono LIKE ?)';
            $term     = "%{$buscar}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        // 3. Consulta principal con priorización de pendientes y orden por semana y fecha.
        // Un envio puede reunir varios reportes (de distintos docentes): se muestran contados y
        // con la lista de nombres; el detalle completo de cada uno se ve en "Ver detalle".
        $sql = "
            SELECT ew.*, e.nombre_completo AS estudiante, e.ci, e.curso,
                   t.nombre_completo AS tutor,
                   (SELECT COUNT(*) FROM reportes r2 WHERE r2.estudiante_id = ew.estudiante_id
                        AND DATE_SUB(r2.periodo_semana, INTERVAL WEEKDAY(r2.periodo_semana) DAY) = ew.periodo_semana) AS cantidad_reportes,
                   (SELECT GROUP_CONCAT(DISTINCT u2.nombre ORDER BY u2.nombre SEPARATOR ', ') FROM reportes r2
                        JOIN usuarios u2 ON u2.id = r2.usuario_id
                        WHERE r2.estudiante_id = ew.estudiante_id
                        AND DATE_SUB(r2.periodo_semana, INTERVAL WEEKDAY(r2.periodo_semana) DAY) = ew.periodo_semana) AS docentes,
                   (SELECT MIN(r2.created_at) FROM reportes r2 WHERE r2.estudiante_id = ew.estudiante_id
                        AND DATE_SUB(r2.periodo_semana, INTERVAL WEEKDAY(r2.periodo_semana) DAY) = ew.periodo_semana) AS fecha_creacion_reporte,
                   -- Texto escrito por docentes que sale tal cual al tutor (incidentes u observaciones): conviene revisarlo.
                   (EXISTS (SELECT 1 FROM reportes r3 WHERE r3.estudiante_id = ew.estudiante_id
                        AND DATE_SUB(r3.periodo_semana, INTERVAL WEEKDAY(r3.periodo_semana) DAY) = ew.periodo_semana
                        AND r3.incidentes_disciplinarios IS NOT NULL AND r3.incidentes_disciplinarios <> '')
                    OR EXISTS (SELECT 1 FROM notas n3 WHERE n3.estudiante_id = ew.estudiante_id
                        AND n3.updated_at >= ew.periodo_semana AND n3.updated_at < DATE_ADD(ew.periodo_semana, INTERVAL 7 DAY)
                        AND n3.observacion IS NOT NULL AND n3.observacion <> '')) AS tiene_texto_libre,
                   -- Mensaje ya enviado y despues se creo o edito un reporte de esa semana: ofrece rectificacion.
                   (ew.estado IN ('enviado','entregado') AND ew.fecha_hora_envio IS NOT NULL
                    AND EXISTS (SELECT 1 FROM reportes r4 WHERE r4.estudiante_id = ew.estudiante_id
                        AND DATE_SUB(r4.periodo_semana, INTERVAL WEEKDAY(r4.periodo_semana) DAY) = ew.periodo_semana
                        AND r4.updated_at > ew.fecha_hora_envio)) AS editado_tras_envio
            FROM envios_wa ew
            JOIN estudiantes e ON e.id = ew.estudiante_id
            LEFT JOIN tutores t ON t.telefono = ew.destinatario_telefono
            WHERE " . implode(' AND ', $where) . "
            ORDER BY 
                CASE WHEN ew.estado = 'pendiente' THEN 1 ELSE 2 END ASC,
                ew.periodo_semana DESC,
                COALESCE(ew.fecha_hora_envio, ew.periodo_semana) DESC,
                ew.id DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $envios = $stmt->fetchAll();

        // 3b. Detalle de cada reporte agrupado en cada envio (para el modal "Ver detalle"), en una
        // sola consulta: se buscan por la combinacion estudiante+semana de los envios ya obtenidos.
        $reportesPorGrupo = [];
        if ($envios) {
            $grupos = [];
            foreach ($envios as $ev) {
                $grupos[$ev['estudiante_id'] . '|' . $ev['periodo_semana']] = [$ev['estudiante_id'], $ev['periodo_semana']];
            }
            $condiciones = array_fill(0, count($grupos), '(r.estudiante_id = ? AND DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY) = ?)');
            $paramsDetalle = array_merge(...array_values($grupos));
            $stmtDetalle = $db->prepare("SELECT r.estudiante_id, DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY) AS semana,
                    r.calificacion_general, r.comportamiento, r.tareas_incompletas, r.incidentes_disciplinarios, r.dias_ausente,
                    u.nombre AS docente_nombre
                FROM reportes r JOIN usuarios u ON u.id = r.usuario_id
                WHERE " . implode(' OR ', $condiciones));
            $stmtDetalle->execute($paramsDetalle);
            foreach ($stmtDetalle->fetchAll() as $r) {
                $reportesPorGrupo[$r['estudiante_id'] . '|' . $r['semana']][] = $r;
            }
        }
        foreach ($envios as &$ev) {
            $ev['reportes'] = $reportesPorGrupo[$ev['estudiante_id'] . '|' . $ev['periodo_semana']] ?? [];
        }
        unset($ev);

        // 4. Contadores para métricas y botón de procesar
        $conteoPendientes = (int)$db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente' AND retenido = 0")->fetchColumn();
        $conteoRetenidos  = (int)$db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente' AND retenido = 1")->fetchColumn();
        $conteoErrores    = (int)$db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'error'")->fetchColumn();

        // 5. Estado en vivo del microservicio Evolution API
        $estadoEvolution = WhatsAppHelper::estadoInstancia();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/envios_wa.php';
    }

    /**
     * Envía manualmente un reporte individual
     */
    public function enviarIndividual() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $envioId = (int)($_POST['envio_id'] ?? 0);
        if ($envioId <= 0) {
            $_SESSION['error'] = 'ID de envío no válido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $resultado = CronController::generarYProcesarEnvio($envioId);

        if ($resultado['success']) {
            $_SESSION['success'] = "¡Reporte enviado exitosamente por WhatsApp a {$resultado['destinatario']} ({$resultado['estudiante']})!";
        } else {
            $detallesError = $resultado['error'] ?? 'Error desconocido';
            if (!empty($resultado['debug']['curl_error'])) {
                $detallesError .= ' (' . $resultado['debug']['curl_error'] . ')';
            }
            $_SESSION['error'] = "No se pudo enviar el reporte: {$detallesError}";
        }

        header('Location: /edunexo/admin/envios-wa');
        exit;
    }

    /**
     * Procesa manualmente todos los reportes pendientes
     */
    public function procesarPendientes() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->query("SELECT id FROM envios_wa WHERE estado = 'pendiente' AND retenido = 0");
        $pendientes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($pendientes)) {
            $_SESSION['error'] = 'No hay envíos pendientes para procesar en este momento.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $enviados = 0;
        $errores = 0;

        foreach ($pendientes as $id) {
            $res = CronController::generarYProcesarEnvio((int)$id);
            if ($res['success']) {
                $enviados++;
            } else {
                $errores++;
            }
        }

        if ($errores === 0) {
            $_SESSION['success'] = "¡Éxito! Se procesaron {$enviados} reporte(s) y todos fueron enviados.";
        } else {
            $_SESSION['success'] = "Procesamiento completado: {$enviados} enviado(s) con éxito y {$errores} con error.";
        }

        header('Location: /edunexo/admin/envios-wa');
        exit;
    }

    /**
     * Envía un mensaje directo de prueba a cualquier teléfono (para pruebas del administrador)
     */
    public function testDirecto() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $telefono = SecurityHelper::sanitize($_POST['telefono_prueba'] ?? '');
        $mensaje  = trim($_POST['mensaje_prueba'] ?? '');

        if (empty($telefono) || empty($mensaje)) {
            $_SESSION['error'] = 'El número de teléfono y el texto del mensaje son obligatorios.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $debugInfo = [];
        $enviado = WhatsAppHelper::enviar($telefono, $mensaje, $debugInfo);

        if ($enviado) {
            $_SESSION['success'] = "¡Mensaje de prueba entregado exitosamente a WhatsApp ({$telefono})!";
        } else {
            $errorMsg = $debugInfo['response'] ?? $debugInfo['curl_error'] ?? 'El servidor de Evolution API rechazó la solicitud.';
            $_SESSION['error'] = "Fallo al enviar mensaje de prueba a {$telefono}: {$errorMsg}";
        }

        header('Location: /edunexo/admin/envios-wa');
        exit;
    }

    /**
     * Vista previa: devuelve el texto EXACTO que recibira el tutor (o el de la rectificacion) sin enviar ni
     * escribir nada. GET ?id=<envio>&tipo=mensaje|rectificacion
     */
    public function vistaPrevia() {
        $envioId = (int)($_GET['id'] ?? 0);
        $tipo = (($_GET['tipo'] ?? '') === 'rectificacion') ? 'rectificacion' : 'mensaje';
        $r = ($tipo === 'rectificacion')
            ? CronController::construirRectificacion($envioId)
            : CronController::construirMensaje($envioId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'          => !empty($r['success']),
            'tipo'        => $tipo,
            'mensaje'     => $r['mensaje'] ?? '',
            'error'       => $r['error'] ?? null,
            'estudiante'  => $r['estudiante'] ?? '',
            'destinatario' => $r['destinatario'] ?? '',
            'texto_libre' => !empty($r['tiene_texto_libre']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Retiene o libera un envio pendiente. Un envio retenido no sale en el envio automatico del viernes ni en
     * "Procesar pendientes"; sigue pudiendo enviarse a mano con su boton "Enviar".
     */
    public function retener() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $envioId = (int)($_POST['envio_id'] ?? 0);
        $retener = (($_POST['accion'] ?? '') === 'liberar') ? 0 : 1;

        $stmt = Database::getConnection()->prepare("UPDATE envios_wa SET retenido = ? WHERE id = ? AND estado = 'pendiente'");
        $stmt->execute([$retener, $envioId]);

        if ($stmt->rowCount()) {
            $_SESSION['success'] = $retener
                ? 'Envío retenido: no saldrá en el envío automático hasta que lo liberes.'
                : 'Envío liberado: saldrá en el próximo envío automático.';
        } else {
            $_SESSION['error'] = 'Sin cambios: el envío ya estaba en ese estado, ya salió o no existe.';
        }

        header('Location: /edunexo/admin/envios-wa');
        exit;
    }

    /**
     * Envia la rectificacion corta de un mensaje ya enviado (reportes nuevos o editados despues del envio).
     */
    public function rectificar() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/envios-wa');
            exit;
        }

        $envioId = (int)($_POST['envio_id'] ?? 0);
        $resultado = CronController::enviarRectificacion($envioId);

        if ($resultado['success']) {
            $_SESSION['success'] = "Rectificación enviada a {$resultado['destinatario']} ({$resultado['estudiante']}).";
        } else {
            $detallesError = $resultado['error'] ?? 'Error desconocido';
            if (!empty($resultado['debug']['curl_error'])) {
                $detallesError .= ' (' . $resultado['debug']['curl_error'] . ')';
            }
            $_SESSION['error'] = "No se pudo enviar la rectificación: {$detallesError}";
        }

        header('Location: /edunexo/admin/envios-wa');
        exit;
    }
}
