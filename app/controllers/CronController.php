<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\WhatsAppHelper;

class CronController {

    /**
     * Lunes (Y-m-d) de la semana que le toca a un envio.
     * - Con $fecha: el lunes de la semana de esa fecha.
     * - Sin $fecha: de viernes a domingo, la semana en curso; de lunes a jueves, la semana anterior.
     *   Asi, si el envio del viernes no corrio (PC apagada) y se ejecuta despues, toma la semana correcta.
     */
    public static function semanaObjetivo(?string $fecha = null): string {
        if ($fecha !== null) {
            return (new \DateTime($fecha))->modify('monday this week')->format('Y-m-d');
        }
        $hoy   = new \DateTime('now');
        $lunes = (clone $hoy)->modify('monday this week');
        if ((int)$hoy->format('N') <= 4) {
            $lunes->modify('-7 days');
        }
        return $lunes->format('Y-m-d');
    }

    /**
     * Encola en envios_wa lo que todavia no esta encolado. Nunca toca filas que ya existen
     * (INSERT IGNORE sobre la clave estudiante+semana+tutor).
     *  1) Un envio por cada reporte de docente cargado (cualquier semana), como antes.
     *  2) Un envio para la semana $lunes de cada estudiante activo que tenga asistencia registrada
     *     o notas cargadas esa semana, aunque ningun docente haya escrito un reporte.
     *     Solo se mira la semana indicada: no se encolan semanas viejas.
     *
     * @return array{reportes:int, actividad:int} filas nuevas encoladas
     */
    public static function sincronizarEnvios(string $lunes): array {
        $db = Database::getConnection();

        $deReportes = (int)$db->exec("
            INSERT IGNORE INTO envios_wa (reporte_id, estudiante_id, periodo_semana, destinatario_telefono, estado, fecha_hora_envio)
            SELECT r.id, r.estudiante_id, DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY), t.telefono, 'pendiente', NULL
            FROM reportes r
            JOIN tutores_estudiantes te ON te.estudiante_id = r.estudiante_id
            JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
        ");

        // Notas: cuentan por fecha de CARGA (updated_at), de lunes 00:00 al lunes siguiente 00:00,
        // asi un examen del jueves calificado el fin de semana o el lunes no se pierde.
        $stmt = $db->prepare("
            INSERT IGNORE INTO envios_wa (reporte_id, estudiante_id, periodo_semana, destinatario_telefono, estado, fecha_hora_envio)
            SELECT NULL, e.id, ?, t.telefono, 'pendiente', NULL
            FROM estudiantes e
            JOIN tutores_estudiantes te ON te.estudiante_id = e.id
            JOIN tutores t ON t.id = te.tutor_id AND t.activo = 1
            WHERE e.estado = 'activo'
              AND (
                    EXISTS (SELECT 1 FROM asistencias a
                            WHERE a.estudiante_id = e.id AND a.fecha >= ? AND a.fecha <= DATE_ADD(?, INTERVAL 4 DAY))
                 OR EXISTS (SELECT 1 FROM notas n
                            WHERE n.estudiante_id = e.id
                              AND n.updated_at >= ? AND n.updated_at < DATE_ADD(?, INTERVAL 7 DAY)
                              AND (n.puntaje_obtenido IS NOT NULL OR (n.observacion IS NOT NULL AND n.observacion <> '')))
              )
        ");
        $stmt->execute([$lunes, $lunes, $lunes, $lunes, $lunes]);

        return ['reportes' => $deReportes, 'actividad' => $stmt->rowCount()];
    }

    /** Nombre y telefono del colegio para el pie de los mensajes. */
    private static function datosColegio(): array {
        $db = Database::getConnection();
        $config = $db->query("SELECT * FROM configuracion WHERE id = 1 LIMIT 1")->fetch();
        return [
            $config['nombre_colegio'] ?? 'Nombre del Colegio',
            $config['telefono_wa_remitente'] ?? '595XXXXXXXXX',
        ];
    }

    /**
     * Datos del envio agrupado (estudiante+semana+tutor). El tutor se busca por el TELEFONO que
     * realmente recibe el mensaje, no por el primero que aparezca para el estudiante: asi el saludo
     * nunca nombra a un tutor distinto.
     */
    private static function cargarEnvio(int $envioId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ew.id AS envio_id, ew.destinatario_telefono, ew.estado, ew.fecha_hora_envio,
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
        return $stmt->fetch();
    }

    /** "Matematica (08/09, 10/09); Artes (09/09)" a partir de [materia => [fechas Y-m-d]]. */
    private static function listarPorMateria(array $porMateria): string {
        ksort($porMateria, SORT_NATURAL | SORT_FLAG_CASE);
        $partes = [];
        foreach ($porMateria as $materia => $fechas) {
            $fechas = array_map(fn($f) => date('d/m', strtotime($f)), $fechas);
            $partes[] = "{$materia} (" . implode(', ', $fechas) . ")";
        }
        return implode('; ', $partes);
    }

    private static function pie(string $telefonoColegio, string $nombreColegio): string {
        return "Este es un mensaje automatico del sistema, por favor no responda a este chat.\n"
             . "Ante cualquier consulta, comuniquese con la secretaria del colegio al {$telefonoColegio}.\n"
             . "EduNexo — {$nombreColegio}";
    }

    /**
     * Arma el texto del reporte semanal SIN escribir nada en la base ni enviarlo. Lo usan la vista previa
     * del admin y el envio real, asi lo que se ve es exactamente lo que sale.
     *
     * @return array{success:bool, codigo?:string, error?:string, envio:array|false, destinatario?:string,
     *               estudiante?:string, mensaje?:string, tiene_texto_libre?:bool}
     *         codigo (solo si falla): no_encontrado | tutor_invalido | sin_datos
     */
    public static function construirMensaje(int $envioId): array {
        $db = Database::getConnection();
        [$nombreColegio, $telefonoColegio] = self::datosColegio();

        $envio = self::cargarEnvio($envioId);
        if (!$envio) {
            return ['success' => false, 'codigo' => 'no_encontrado', 'error' => 'Registro de envío no encontrado.', 'envio' => false];
        }

        $base = [
            'envio'        => $envio,
            'destinatario' => $envio['destinatario_telefono'],
            'estudiante'   => $envio['estudiante_nombre'],
        ];

        if ($envio['tutor_nombre'] === null) {
            return $base + [
                'success' => false,
                'codigo'  => 'tutor_invalido',
                'error'   => 'El teléfono destino no corresponde a ningún tutor activo de este estudiante.',
            ];
        }

        $estudianteId  = $envio['estudiante_id'];
        $periodoSemana = $envio['periodo_semana'];
        $finSemana     = date('Y-m-d', strtotime($periodoSemana . ' + 4 days'));

        // Reportes de TODOS los docentes que cargaron algo para este estudiante esa semana (puede no haber ninguno).
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

        // Asistencia de la semana (lunes a viernes), CLASE por clase: cada docente toma la asistencia de su
        // materia, asi que el padre ve a que materias falto y no un "dia de ausencia" por faltar a una sola clase.
        $stmtAsist = $db->prepare("
            SELECT a.fecha, a.presente, a.justificada, m.nombre AS materia
            FROM asistencias a
            JOIN curso_materia_docente cmd ON cmd.id = a.curso_materia_docente_id
            JOIN materias m ON m.id = cmd.materia_id
            WHERE a.estudiante_id = ? AND a.fecha >= ? AND a.fecha <= ?
            ORDER BY a.fecha, m.nombre
        ");
        $stmtAsist->execute([$estudianteId, $periodoSemana, $finSemana]);
        $registrosAsistencia = $stmtAsist->fetchAll();

        // Notas cargadas esa semana. Se filtra por fecha de CARGA (updated_at), de lunes 00:00 al lunes
        // siguiente 00:00, y no por la fecha de la evaluacion: un examen del jueves calificado el lunes
        // sale en el mensaje de la semana en que se califico. Se omiten las filas vacias (sin puntaje ni observacion).
        $inicioNotas = $periodoSemana . ' 00:00:00';
        $finNotas    = date('Y-m-d', strtotime($periodoSemana . ' + 7 days')) . ' 00:00:00';
        $stmtNotas = $db->prepare("
            SELECT m.nombre AS materia, te.nombre AS tipo_eval, e.titulo, e.fecha AS fecha_eval, e.puntaje_maximo,
                   n.puntaje_obtenido, n.observacion
            FROM notas n
            JOIN evaluaciones e ON n.evaluacion_id = e.id
            JOIN curso_materia_docente cmd ON e.curso_materia_docente_id = cmd.id
            JOIN materias m ON cmd.materia_id = m.id
            JOIN tipo_evaluacion te ON e.tipo_evaluacion_id = te.id
            WHERE n.estudiante_id = ?
              AND n.updated_at >= ? AND n.updated_at < ?
              AND (n.puntaje_obtenido IS NOT NULL OR (n.observacion IS NOT NULL AND n.observacion <> ''))
            ORDER BY m.nombre, e.fecha, e.id
        ");
        $stmtNotas->execute([$estudianteId, $inicioNotas, $finNotas]);
        $notas = $stmtNotas->fetchAll();

        // Sin reporte de docente el mensaje sale igual con asistencia y notas.
        // Solo falla si no hay NADA cargado para la semana.
        if (!$reportesSemana && !$registrosAsistencia && !$notas) {
            return $base + [
                'success' => false,
                'codigo'  => 'sin_datos',
                'error'   => 'No hay reportes, asistencia ni notas cargadas para esta semana (puede que se hayan borrado antes del envío).',
            ];
        }

        $fechaInicio = date('d/m/Y', strtotime($periodoSemana));
        $fechaFin    = date('d/m/Y', strtotime($periodoSemana . ' + 4 days')); // Viernes

        $tutorNombre = $envio['tutor_nombre'] ?? 'Tutor/a';
        $mensaje = "Hola {$tutorNombre}, le enviamos el reporte semanal de {$envio['estudiante_nombre']} — semana del {$fechaInicio} al {$fechaFin}.\n\n";

        // ASISTENCIA, por clase (materia). Una falta es una clase a la que no asistio.
        $totalClases = count($registrosAsistencia);
        $sinJustificar = [];
        $justificadas  = [];
        foreach ($registrosAsistencia as $registro) {
            if ((int)$registro['presente'] === 0) {
                if ((int)$registro['justificada']) $justificadas[$registro['materia']][] = $registro['fecha'];
                else $sinJustificar[$registro['materia']][] = $registro['fecha'];
            }
        }
        $nSin  = array_sum(array_map('count', $sinJustificar));
        $nJust = array_sum(array_map('count', $justificadas));

        $mensaje .= "ASISTENCIA:\n";
        if ($totalClases === 0) {
            // Sin filas no significa asistencia perfecta: significa que el docente no registro nada.
            $mensaje .= "Sin registro de asistencia esta semana.\n\n";
        } elseif ($nSin + $nJust === 0) {
            $mensaje .= "Sin ausencias (clases registradas esta semana: {$totalClases}).\n\n";
        } else {
            $mensaje .= "Ausencias: " . ($nSin + $nJust) . " (clases registradas esta semana: {$totalClases}).\n";
            if ($nSin)  $mensaje .= "- Sin justificar: " . self::listarPorMateria($sinJustificar) . "\n";
            if ($nJust) $mensaje .= "- Justificadas: " . self::listarPorMateria($justificadas) . "\n";
            $mensaje .= "\n";
        }

        // REPORTES DE LOS DOCENTES (uno por cada docente que cargo algo esta semana).
        // Si ningun docente cargo reporte, la seccion se omite.
        $tieneTextoLibre = false;
        if ($reportesSemana) {
            $mensaje .= "REPORTES DE TUS DOCENTES:\n";
            foreach ($reportesSemana as $reporte) {
                $mensaje .= "- {$reporte['docente_nombre']}: {$reporte['calificacion_general']} | Comportamiento: {$reporte['comportamiento']}";
                if ((int)$reporte['tareas_incompletas'] > 0) {
                    $mensaje .= " | Tareas incompletas: {$reporte['tareas_incompletas']}";
                }
                $mensaje .= "\n";
                if (!empty($reporte['incidentes_disciplinarios'])) {
                    $mensaje .= "  Incidentes: {$reporte['incidentes_disciplinarios']}\n";
                    $tieneTextoLibre = true;
                }
            }
            $mensaje .= "\n";
        }

        // CALIFICACIONES Y OBSERVACIONES
        $calificaciones = "";
        $observaciones = "";
        foreach ($notas as $nota) {
            if ($nota['puntaje_obtenido'] !== null) {
                $fechaEval = date('d/m', strtotime($nota['fecha_eval']));
                $calificaciones .= "- [{$nota['materia']}]: [{$nota['tipo_eval']}] \"{$nota['titulo']}\" del {$fechaEval} — {$nota['puntaje_obtenido']}/{$nota['puntaje_maximo']}\n";
            }
            if (!empty($nota['observacion'])) {
                $observaciones .= "- [{$nota['materia']}]: {$nota['observacion']}\n";
                $tieneTextoLibre = true;
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

        $mensaje .= self::pie($telefonoColegio, $nombreColegio);

        return $base + ['success' => true, 'mensaje' => $mensaje, 'tiene_texto_libre' => $tieneTextoLibre];
    }

    /**
     * Genera el texto del reporte semanal y lo envía por WhatsApp.
     * Retorna un array con el resultado y detalles.
     *
     * @param int $envioId ID en la tabla envios_wa
     * @return array
     */
    public static function generarYProcesarEnvio(int $envioId): array {
        $db = Database::getConnection();

        $r = self::construirMensaje($envioId);
        if (!$r['envio']) {
            return ['success' => false, 'error' => $r['error']];
        }
        $envio = $r['envio'];

        // Log de validación: valido si el telefono destino corresponde a un tutor activo real.
        $valido = (($r['codigo'] ?? '') === 'tutor_invalido') ? 0 : 1;
        try {
            $stmtLog = $db->prepare("INSERT INTO logs_validacion (estudiante_id, telefono_intentado, resultado, fecha_intento) VALUES (?, ?, ?, NOW())");
            $stmtLog->execute([$envio['estudiante_id'], $envio['destinatario_telefono'], $valido]);
        } catch (\Throwable $e) {
            // No bloquear el envío si la tabla de logs tiene algún detalle
        }

        if (!$r['success']) {
            $db->prepare("UPDATE envios_wa SET estado = 'error' WHERE id = ?")->execute([$envioId]);
            return [
                'success'      => false,
                'destinatario' => $envio['destinatario_telefono'],
                'estudiante'   => $envio['estudiante_nombre'],
                'error'        => $r['error'],
            ];
        }

        $mensaje = $r['mensaje'];

        // LLAMAR A WHATSAPP API
        $debugInfo = [];
        $enviado = WhatsAppHelper::enviar($envio['destinatario_telefono'], $mensaje, $debugInfo);

        // UPDATE ENVIOS_WA. Un envio que sale deja de estar retenido.
        $nuevoEstado = $enviado ? 'enviado' : 'error';
        $db->prepare("UPDATE envios_wa SET estado = ?, fecha_hora_envio = NOW() WHERE id = ?")
           ->execute([$nuevoEstado, $envioId]);
        if ($enviado) {
            $db->prepare("UPDATE envios_wa SET retenido = 0 WHERE id = ?")->execute([$envioId]);
        }

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
     * Mensaje corto de RECTIFICACION: solo los reportes de docentes que se crearon o editaron DESPUES de
     * que el mensaje semanal ya salio (reportes.updated_at > envios_wa.fecha_hora_envio). Regla: un envio ya
     * enviado NO se reenvia completo; si el docente cambia algo, el admin decide si manda esta rectificacion.
     * No escribe nada en la base.
     */
    public static function construirRectificacion(int $envioId): array {
        $db = Database::getConnection();
        [$nombreColegio, $telefonoColegio] = self::datosColegio();

        $envio = self::cargarEnvio($envioId);
        if (!$envio) {
            return ['success' => false, 'codigo' => 'no_encontrado', 'error' => 'Registro de envío no encontrado.', 'envio' => false];
        }
        $base = [
            'envio'        => $envio,
            'destinatario' => $envio['destinatario_telefono'],
            'estudiante'   => $envio['estudiante_nombre'],
        ];

        if (!in_array($envio['estado'], ['enviado', 'entregado'], true) || empty($envio['fecha_hora_envio'])) {
            return $base + ['success' => false, 'codigo' => 'no_enviado',
                'error' => 'Este envío todavía no salió: el mensaje incluirá los reportes actuales cuando se envíe.'];
        }
        if ($envio['tutor_nombre'] === null) {
            return $base + ['success' => false, 'codigo' => 'tutor_invalido',
                'error' => 'El teléfono destino no corresponde a ningún tutor activo de este estudiante.'];
        }

        $stmt = $db->prepare("
            SELECT r.calificacion_general, r.comportamiento, r.tareas_incompletas, r.incidentes_disciplinarios,
                   u.nombre AS docente_nombre
            FROM reportes r
            JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.estudiante_id = ?
              AND DATE_SUB(r.periodo_semana, INTERVAL WEEKDAY(r.periodo_semana) DAY) = ?
              AND r.updated_at > ?
            ORDER BY u.nombre
        ");
        $stmt->execute([$envio['estudiante_id'], $envio['periodo_semana'], $envio['fecha_hora_envio']]);
        $cambios = $stmt->fetchAll();

        if (!$cambios) {
            return $base + ['success' => false, 'codigo' => 'sin_cambios',
                'error' => 'No hay reportes nuevos ni editados después del envío: no hay nada que rectificar.'];
        }

        $fechaInicio = date('d/m/Y', strtotime($envio['periodo_semana']));
        $fechaFin    = date('d/m/Y', strtotime($envio['periodo_semana'] . ' + 4 days'));
        $mensaje = "Hola {$envio['tutor_nombre']}, actualizamos el reporte semanal de {$envio['estudiante_nombre']} — semana del {$fechaInicio} al {$fechaFin} — con novedades de sus docentes:\n\n";

        $tieneTextoLibre = false;
        foreach ($cambios as $reporte) {
            $mensaje .= "- {$reporte['docente_nombre']}: {$reporte['calificacion_general']} | Comportamiento: {$reporte['comportamiento']}";
            if ((int)$reporte['tareas_incompletas'] > 0) {
                $mensaje .= " | Tareas incompletas: {$reporte['tareas_incompletas']}";
            }
            $mensaje .= "\n";
            if (!empty($reporte['incidentes_disciplinarios'])) {
                $mensaje .= "  Incidentes: {$reporte['incidentes_disciplinarios']}\n";
                $tieneTextoLibre = true;
            }
        }
        $mensaje .= "\nEsta información se suma o reemplaza la del mensaje anterior.\n\n";
        $mensaje .= self::pie($telefonoColegio, $nombreColegio);

        return $base + ['success' => true, 'mensaje' => $mensaje, 'tiene_texto_libre' => $tieneTextoLibre];
    }

    /** Envia la rectificacion. Si sale, fecha_hora_envio pasa a ser ahora y el aviso "editado tras el envio" se apaga. */
    public static function enviarRectificacion(int $envioId): array {
        $db = Database::getConnection();
        $r = self::construirRectificacion($envioId);
        if (!$r['success']) {
            return ['success' => false, 'estudiante' => $r['estudiante'] ?? '', 'destinatario' => $r['destinatario'] ?? '', 'error' => $r['error']];
        }

        $debugInfo = [];
        $enviado = WhatsAppHelper::enviar($r['destinatario'], $r['mensaje'], $debugInfo);
        if ($enviado) {
            $db->prepare("UPDATE envios_wa SET fecha_hora_envio = NOW() WHERE id = ?")->execute([$envioId]);
        }

        return [
            'success'      => $enviado,
            'destinatario' => $r['destinatario'],
            'estudiante'   => $r['estudiante'],
            'debug'        => $debugInfo,
            'error'        => $enviado ? null : ($debugInfo['response'] ?? $debugInfo['curl_error'] ?? 'Error de entrega en Evolution API'),
        ];
    }

    /**
     * Proceso semanal completo: encola lo que falte y envia todos los pendientes que NO esten retenidos.
     * Lo usan el script de consola (cron/procesar_envios.php) y el endpoint HTTP local.
     *
     * Si WhatsApp no esta conectado NO envia nada y deja los envios en 'pendiente', para que
     * la proxima corrida los tome (marcarlos 'error' obligaria a reenviarlos a mano).
     *
     * @param string|null $semana   Cualquier fecha de la semana a cubrir (Y-m-d). Sin valor, ver semanaObjetivo().
     * @param int         $pausaMs  Pausa entre mensajes, para no enviar en rafaga.
     * @return array{semana:string, encolados_reportes:int, encolados_actividad:int, pendientes:int, retenidos:int, enviados:int, errores:int, detalle_errores:array, abortado:?string}
     */
    public static function ejecutarProcesoSemanal(?string $semana = null, int $pausaMs = 0): array {
        $db    = Database::getConnection();
        $lunes = self::semanaObjetivo($semana);
        $sync  = self::sincronizarEnvios($lunes);

        $pendientes = $db->query("SELECT id FROM envios_wa WHERE estado = 'pendiente' AND retenido = 0 ORDER BY periodo_semana, id")
                         ->fetchAll(\PDO::FETCH_COLUMN);
        $retenidos  = (int)$db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente' AND retenido = 1")->fetchColumn();

        $resumen = [
            'semana'              => $lunes,
            'encolados_reportes'  => $sync['reportes'],
            'encolados_actividad' => $sync['actividad'],
            'pendientes'          => count($pendientes),
            'retenidos'           => $retenidos,
            'enviados'            => 0,
            'errores'             => 0,
            'detalle_errores'     => [],
            'abortado'            => null,
        ];

        if (!$pendientes) {
            return $resumen;
        }

        $estado = WhatsAppHelper::estadoInstancia();
        if (!$estado['online'] || in_array($estado['state'] ?? '', ['close', 'closed', 'connecting', 'offline', 'sin_configurar'], true)) {
            $resumen['abortado'] = 'WhatsApp no esta conectado (estado: ' . ($estado['state'] ?? 'desconocido') . '). No se envio nada; los envios siguen pendientes.';
            return $resumen;
        }

        foreach ($pendientes as $i => $envioId) {
            if ($i > 0 && $pausaMs > 0) {
                usleep($pausaMs * 1000);
            }

            try {
                $resultado = self::generarYProcesarEnvio((int)$envioId);
            } catch (\Throwable $e) {
                $db->prepare("UPDATE envios_wa SET estado = 'error' WHERE id = ?")->execute([(int)$envioId]);
                $resultado = ['success' => false, 'estudiante' => '?', 'error' => $e->getMessage()];
            }

            if ($resultado['success']) {
                $resumen['enviados']++;
            } else {
                $resumen['errores']++;
                $resumen['detalle_errores'][] = [
                    'envio_id'   => (int)$envioId,
                    'estudiante' => $resultado['estudiante'] ?? '?',
                    'error'      => substr((string)($resultado['error'] ?? 'Error desconocido'), 0, 200),
                ];
            }
        }

        return $resumen;
    }

    /**
     * Endpoint para ejecucion automatizada (CLI o localhost). Para el envio programado de los viernes
     * se usa cron/procesar_envios.php, que llama a ejecutarProcesoSemanal() directamente.
     */
    public function procesarEnviosSemanales() {
        // 1. Verificar CLI o IP
        $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
        
        if (!$isCli && !$isLocal) {
            http_response_code(403);
            die("Access Denied");
        }

        $resumen = self::ejecutarProcesoSemanal(null, 1500);

        if ($isCli) {
            echo "Procesamiento de envios semanal completado.\n";
            echo "Semana: {$resumen['semana']}\n";
            echo "Enviados exitosamente: {$resumen['enviados']}\n";
            echo "Errores: {$resumen['errores']}\n";
            echo "Retenidos (no se enviaron): {$resumen['retenidos']}\n";
            if ($resumen['abortado']) echo "Aviso: {$resumen['abortado']}\n";
        } else {
            echo json_encode([
                'success'   => $resumen['abortado'] === null,
                'semana'    => $resumen['semana'],
                'enviados'  => $resumen['enviados'],
                'errores'   => $resumen['errores'],
                'retenidos' => $resumen['retenidos'],
                'aviso'     => $resumen['abortado'],
            ]);
        }
    }
}
