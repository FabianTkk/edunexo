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

        // 1. Auto-sincronizar reportes existentes que aún no estén encolados en envios_wa.
        // Se agrupa por estudiante+semana+tutor: si ya hay un envio para esa combinacion (creado
        // por el propio docente al guardar su reporte, o por otro docente antes), no se duplica.
        try {
            $db->exec("
                INSERT IGNORE INTO envios_wa (reporte_id, estudiante_id, periodo_semana, destinatario_telefono, estado, fecha_hora_envio)
                SELECT r.id, r.estudiante_id, DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY), t.telefono, 'pendiente', NULL
                FROM reportes r
                JOIN tutores_estudiantes te ON te.estudiante_id = r.estudiante_id
                JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
            ");
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
                        AND DATE_SUB(r2.periodo_semana, INTERVAL WEEKDAY(r2.periodo_semana) DAY) = ew.periodo_semana) AS fecha_creacion_reporte
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
        $conteoPendientes = (int)$db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente'")->fetchColumn();
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
        $stmt = $db->query("SELECT id FROM envios_wa WHERE estado = 'pendiente'");
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
}
