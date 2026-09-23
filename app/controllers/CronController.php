<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\WhatsAppHelper;

class CronController {

    /**
     * Genera el texto del reporte semanal y lo procesa/envía por WhatsApp.
     * Retorna un array con el resultado y detalles.
     *
     * @param int $envioId ID en la tabla envios_wa
     * @return array
     */
    public static function generarYProcesarEnvio(int $envioId): array {
        $db = Database::getConnection();

        // 1. Obtener config del colegio
        $stmtConfig = $db->query("SELECT * FROM configuracion WHERE id = 1 LIMIT 1");
        $config = $stmtConfig->fetch();
        $nombreColegio = $config['nombre_colegio'] ?? 'Nombre del Colegio';
        $telefonoColegio = $config['telefono_wa_remitente'] ?? '595XXXXXXXXX';

        // 2. Obtener datos del envio (agrupado por estudiante+semana+tutor: puede reunir reportes de varios docentes).
        $stmt = $db->prepare("
            SELECT ew.id AS envio_id, ew.destinatario_telefono, ew.estado,
                   ew.estudiante_id, ew.periodo_semana,
                   e.nombre_completo AS estudiante_nombre, e.ci AS estudiante_ci,
                   t.nombre_completo AS tutor_nombre
            FROM envios_wa ew
            JOIN estudiantes e ON e.id = ew.estudiante_id
            LEFT JOIN tutores t ON t.telefono = ew.destinatario_telefono AND t.activo = 1
            WHERE ew.id = ?
            LIMIT 1
        ");
        $stmt->execute([$envioId]);
        $envio = $stmt->fetch();

        if (!$envio) {
            return ['success' => false, 'error' => 'Registro de envío no encontrado.'];
        }

        // El tutor se busca por el TELEFONO que realmente recibe el mensaje, no por el primero
        // que aparezca para el estudiante: asi el saludo nunca nombra a un tutor distinto.
        $estudianteId = $envio['estudiante_id'];
        $periodoSemana = $envio['periodo_semana'];
        $finSemana = date('Y-m-d', strtotime($periodoSemana . ' + 4 days'));

        // 3. Log de validación: valido si el telefono destino corresponde a un tutor activo real.
        $valido = $envio['tutor_nombre'] !== null ? 1 : 0;
        try {
            $stmtLog = $db->prepare("INSERT INTO logs_validacion (estudiante_id, telefono_intentado, resultado, fecha_intento) VALUES (?, ?, ?, NOW())");
            $stmtLog->execute([$estudianteId, $envio['destinatario_telefono'], $valido]);
        } catch (\Throwable $e) {
            // No bloquear el envío si la tabla de logs tiene algún detalle
        }

        if (!$valido) {
            $db->prepare("UPDATE envios_wa SET estado = 'error' WHERE id = ?")->execute([$envioId]);
            return [
                'success' => false,
                'destinatario' => $envio['destinatario_telefono'],
                'estudiante' => $envio['estudiante_nombre'],
                'error' => 'El teléfono destino no corresponde a ningún tutor activo de este estudiante.'
            ];
        }

        // 3b. Reportes de TODOS los docentes que cargaron algo para este estudiante esa semana.
        // La comparacion normaliza periodo_semana al lunes por si quedara algun dato viejo sin normalizar.
        $stmtReportes = $db->prepare("
            SELECT r.calificacion_general, r.comportamiento, r.tareas_incompletas, r.incidentes_disciplinarios, r.dias_ausente,
                   u.nombre AS docente_nombre
            FROM reportes r
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.estudiante_id = ?
              AND DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY) = ?
            ORDER BY u.nombre
        ");
        $stmtReportes->execute([$estudianteId, $periodoSemana]);
        $reportesSemana = $stmtReportes->fetchAll();

        if (!$reportesSemana) {
            // Puede pasar si se borraron todos los reportes de la semana antes de que se enviara el mensaje.
            $db->prepare("UPDATE envios_wa SET estado = 'error' WHERE id = ?")->execute([$envioId]);
            return [
                'success' => false,
                'destinatario' => $envio['destinatario_telefono'],
                'estudiante' => $envio['estudiante_nombre'],
                'error' => 'No quedan reportes cargados para esta semana (se habrán borrado antes del envío).'
            ];
        }

        // 4. Construir mensaje del reporte
        $fechaInicio = date('d/m/Y', strtotime($periodoSemana));
        $fechaFin = date('d/m/Y', strtotime($periodoSemana . ' + 4 days')); // Viernes

        $tutorNombre = $envio['tutor_nombre'] ?? 'Tutor/a';
        $mensaje = "Hola {$tutorNombre}, le enviamos el reporte semanal de {$envio['estudiante_nombre']} — semana del {$fechaInicio} al {$fechaFin}.\n\n";

        // ASISTENCIAS
        // Se cuentan DIAS, no filas: un estudiante ausente en varias materias el mismo dia es una sola ausencia.
        $stmtAsist = $db->prepare("SELECT fecha, presente, justificada FROM asistencias WHERE estudiante_id = ? AND fecha >= ? AND fecha <= ?");
        $stmtAsist->execute([$estudianteId, $periodoSemana, $finSemana]);
        $registrosAsistencia = $stmtAsist->fetchAll();

        $diasRegistrados = [];
        $diasNoJustificados = [];
        $diasJustificados = [];
        foreach ($registrosAsistencia as $registro) {
            $dia = $registro['fecha'];
            $diasRegistrados[$dia] = true;
            if ((int)$registro['presente'] === 0) {
                if ((int)$registro['justificada']) $diasJustificados[$dia] = true;
                else $diasNoJustificados[$dia] = true;
            }
        }
        // Si en un mismo dia hay falta justificada y no justificada, cuenta como no justificada.
        $diasJustificados = array_diff_key($diasJustificados, $diasNoJustificados);

        $mensaje .= "ASISTENCIA:\n";
        if (empty($diasRegistrados)) {
            // Sin filas no significa asistencia perfecta: significa que el docente no registro nada.
            $mensaje .= "Sin registro de asistencia esta semana.\n\n";
        } elseif (empty($diasNoJustificados) && empty($diasJustificados)) {
            $mensaje .= "Asistencia completa esta semana.\n\n";
        } else {
            $mensaje .= "- Ausencias no justificadas: " . count($diasNoJustificados) . "\n";
            if ($diasNoJustificados) {
                $fechas = array_map(fn($dia) => date('d/m', strtotime($dia)), array_keys($diasNoJustificados));
                sort($fechas);
                $mensaje .= "- Fechas de ausencia: " . implode(', ', $fechas) . "\n";
            }
            $mensaje .= "- Ausencias justificadas: " . count($diasJustificados) . "\n\n";
        }

        // REPORTES DE LOS DOCENTES (uno por cada docente que cargo algo esta semana)
        $mensaje .= "REPORTES DE TUS DOCENTES:\n";
        foreach ($reportesSemana as $reporte) {
            $mensaje .= "- {$reporte['docente_nombre']}: {$reporte['calificacion_general']} | Comportamiento: {$reporte['comportamiento']}";
            if ((int)$reporte['tareas_incompletas'] > 0) {
                $mensaje .= " | Tareas incompletas: {$reporte['tareas_incompletas']}";
            }
            $mensaje .= "\n";
            if (!empty($reporte['incidentes_disciplinarios'])) {
                $mensaje .= "  Incidentes: {$reporte['incidentes_disciplinarios']}\n";
            }
        }
        $mensaje .= "\n";

        // CALIFICACIONES Y OBSERVACIONES
        $stmtNotas = $db->prepare("
            SELECT m.nombre AS materia, te.nombre AS tipo_eval, e.titulo, e.puntaje_maximo, n.puntaje_obtenido, n.observacion
            FROM notas n
            JOIN evaluaciones e ON n.evaluacion_id = e.id
            JOIN curso_materia_docente cmd ON e.curso_materia_docente_id = cmd.id
            JOIN materias m ON cmd.materia_id = m.id
            JOIN tipo_evaluacion te ON e.tipo_evaluacion_id = te.id
            WHERE n.estudiante_id = ? AND e.fecha >= ? AND e.fecha <= ?
        ");
        $stmtNotas->execute([$estudianteId, $periodoSemana, $finSemana]);
        $notas = $stmtNotas->fetchAll();

        $calificaciones = "";
        $observaciones = "";
        foreach ($notas as $nota) {
            $calificaciones .= "- [{$nota['materia']}]: [{$nota['tipo_eval']}] \"{$nota['titulo']}\" — {$nota['puntaje_obtenido']}/{$nota['puntaje_maximo']}\n";
            if (!empty($nota['observacion'])) {
                $observaciones .= "- [{$nota['materia']}]: {$nota['observacion']}\n";
            }
        }

        if (!empty($calificaciones)) {
            $mensaje .= "CALIFICACIONES:\n" . $calificaciones . "\n";
        }

        if (!empty($observaciones)) {
            $mensaje .= "OBSERVACIONES:\n" . $observaciones . "\n";
        }

        // AVISOS
        $stmtAvisos = $db->prepare("
            SELECT a.titulo, a.descripcion, a.fecha_aviso
            FROM avisos a
            JOIN curso_materia_docente cmd ON a.curso_materia_docente_id = cmd.id
            JOIN estudiantes e ON e.curso_id = cmd.curso_id
            LEFT JOIN aviso_estudiante ae ON a.id = ae.aviso_id
            WHERE e.id = ? 
            AND a.fecha_aviso >= ? AND a.fecha_aviso <= ?
            AND (a.aplica_a_todos = 1 OR ae.estudiante_id = ?)
        ");
        $stmtAvisos->execute([$estudianteId, $periodoSemana, $finSemana, $estudianteId]);
        $avisos = $stmtAvisos->fetchAll();

        if (!empty($avisos)) {
            $mensaje .= "AVISOS:\n";
            foreach ($avisos as $aviso) {
                $fechaAvisoStr = date('d/m/Y', strtotime($aviso['fecha_aviso']));
                $mensaje .= "- [{$fechaAvisoStr}]: [{$aviso['titulo']}] — {$aviso['descripcion']}\n";
            }
            $mensaje .= "\n";
        }

        // PIE DE MENSAJE
        $mensaje .= "Este es un mensaje automatico del sistema, por favor no responda a este chat.\n";
        $mensaje .= "Ante cualquier consulta, comuniquese con la secretaria del colegio al {$telefonoColegio}.\n";
        $mensaje .= "EduNexo — {$nombreColegio}";

        // 5. LLAMAR A WHATSAPP API
        $debugInfo = [];
        $enviado = WhatsAppHelper::enviar($envio['destinatario_telefono'], $mensaje, $debugInfo);

        // 6. UPDATE ENVIOS_WA
        $nuevoEstado = $enviado ? 'enviado' : 'error';
        $db->prepare("UPDATE envios_wa SET estado = ?, fecha_hora_envio = NOW() WHERE id = ?")
           ->execute([$nuevoEstado, $envioId]);

        return [
            'success'      => $enviado,
            'destinatario' => $envio['destinatario_telefono'],
            'estudiante'   => $envio['estudiante_nombre'],
            'mensaje'      => $mensaje,
            'debug'        => $debugInfo,
            'error'        => $enviado ? null : ($debugInfo['response'] ?? $debugInfo['curl_error'] ?? 'Error de entrega en Evolution API')
        ];
    }

    /**
     * Endpoint para cron jobs automatizados (CLI o Localhost)
     */
    public function procesarEnviosSemanales() {
        // 1. Verificar CLI o IP
        $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        
        if (!$isCli && !$isLocal) {
            http_response_code(403);
            die("Access Denied");
        }

        $db = Database::getConnection();
        
        // 2. Obtener IDs de reportes con estado pendiente en envios_wa
        $stmt = $db->query("SELECT id FROM envios_wa WHERE estado = 'pendiente'");
        $enviosPendientes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $enviadosCount = 0;
        $erroresCount = 0;

        foreach ($enviosPendientes as $envioId) {
            $resultado = self::generarYProcesarEnvio((int)$envioId);
            if ($resultado['success']) {
                $enviadosCount++;
            } else {
                $erroresCount++;
            }
        }

        if ($isCli) {
            echo "Procesamiento de envios semanal completado.\n";
            echo "Enviados exitosamente: $enviadosCount\n";
            echo "Errores: $erroresCount\n";
        } else {
            echo json_encode(['success' => true, 'enviados' => $enviadosCount, 'errores' => $erroresCount]);
        }
    }
}
