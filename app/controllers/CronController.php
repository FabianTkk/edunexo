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

        // 2. Obtener datos del envio
        $stmt = $db->prepare("
            SELECT ew.id AS envio_id, ew.destinatario_telefono, ew.estado,
                   r.id AS reporte_id, r.estudiante_id, r.periodo_semana, r.dias_ausente,
                   e.nombre_completo AS estudiante_nombre, e.ci AS estudiante_ci,
                   t.nombre_completo AS tutor_nombre, t.telefono AS tutor_telefono
            FROM envios_wa ew
            JOIN reportes r ON ew.reporte_id = r.id
            JOIN estudiantes e ON r.estudiante_id = e.id
            LEFT JOIN tutores_estudiantes te ON e.id = te.estudiante_id
            LEFT JOIN tutores t ON te.tutor_id = t.id
            WHERE ew.id = ?
            LIMIT 1
        ");
        $stmt->execute([$envioId]);
        $envio = $stmt->fetch();

        if (!$envio) {
            return ['success' => false, 'error' => 'Registro de envío no encontrado.'];
        }

        $telefonoTutor = $envio['tutor_telefono'] ?? $envio['destinatario_telefono'];
        $estudianteId = $envio['estudiante_id'];
        // La semana siempre va de lunes a viernes; si el reporte se cargo con otra fecha, se lleva al lunes.
        $periodoSemana = date('Y-m-d', strtotime('monday this week', strtotime($envio['periodo_semana'])));
        $finSemana = date('Y-m-d', strtotime($periodoSemana . ' + 4 days'));

        // 3. Log de validación
        $valido = (!empty($telefonoTutor) && $telefonoTutor === $envio['destinatario_telefono']) ? 1 : 0;
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
                'error' => 'El teléfono registrado del tutor no coincide con el destinatario del reporte.'
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
