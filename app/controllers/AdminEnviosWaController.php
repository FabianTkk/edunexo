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

        // 1. Auto-sincronizar reportes existentes creados por docentes que aún no estén encolados en envios_wa
        try {
            $db->exec("
                INSERT INTO envios_wa (reporte_id, destinatario_telefono, estado, fecha_hora_envio)
                SELECT r.id, COALESCE(t.telefono, ''), 'pendiente', NULL
                FROM reportes r
                LEFT JOIN tutores_estudiantes te ON te.estudiante_id = r.estudiante_id
                LEFT JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
                LEFT JOIN envios_wa wa ON wa.reporte_id = r.id
                WHERE wa.id IS NULL
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
            // Filtra por la semana académica (periodo_semana) o por la fecha de envío/creación
            $where[]  = '(r.periodo_semana >= ? OR DATE(COALESCE(ew.fecha_hora_envio, r.created_at)) >= ?)';
            $params[] = $desde;
            $params[] = $desde;
        }

        if (!empty($hasta)) {
            $where[]  = '(r.periodo_semana <= ? OR DATE(COALESCE(ew.fecha_hora_envio, r.created_at)) <= ?)';
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

        // 3. Consulta principal con priorización de pendientes y orden por semana y fecha
        $sql = "
            SELECT ew.*, e.nombre_completo AS estudiante, e.ci, e.curso,
                   t.nombre_completo AS tutor, t.telefono AS tutor_telefono,
                   r.periodo_semana, r.calificacion_general, r.comportamiento, r.dias_ausente,
                   r.tareas_incompletas, r.incidentes_disciplinarios, r.created_at AS fecha_creacion_reporte,
                   u.nombre AS docente_nombre
            FROM envios_wa ew
            JOIN reportes r ON r.id = ew.reporte_id
            JOIN estudiantes e ON e.id = r.estudiante_id
            LEFT JOIN usuarios u ON u.id = r.usuario_id
            LEFT JOIN tutores_estudiantes te ON te.estudiante_id = e.id
            LEFT JOIN tutores t ON t.id = te.tutor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY 
                CASE WHEN ew.estado = 'pendiente' THEN 1 ELSE 2 END ASC,
                r.periodo_semana DESC,
                COALESCE(ew.fecha_hora_envio, r.created_at) DESC,
                ew.id DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $envios = $stmt->fetchAll();

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
