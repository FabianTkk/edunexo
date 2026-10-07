<?php
// cron/procesar_envios.php
// Envio semanal de reportes por WhatsApp. Se ejecuta SOLO desde consola; lo llama el Programador de
// tareas de Windows (ver cron/enviar_semanal.bat).
//
// Uso (desde la terminal de Laragon, en la carpeta del proyecto):
//   php cron/procesar_envios.php                      encola y envia
//   php cron/procesar_envios.php --solo-encolar       encola y muestra cuantos hay pendientes, sin enviar nada
//   php cron/procesar_envios.php --semana=2026-09-28  fuerza la semana (cualquier fecha de esa semana)
//
// Sin --semana: de viernes a domingo toma la semana en curso; de lunes a jueves, la semana anterior.
// Codigo de salida: 0 todo bien, 1 hubo errores o WhatsApp no estaba conectado, 2 argumentos invalidos.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la consola.');
}

date_default_timezone_set('America/Asuncion');

spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len     = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require $file;
});

use App\Controllers\CronController;

function log_linea(string $texto): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $texto . PHP_EOL;
}

$semana = null;
$soloEncolar = false;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--semana=') === 0) {
        $semana = substr($arg, 9);
        $d = \DateTime::createFromFormat('Y-m-d', $semana);
        if ($d === false || $d->format('Y-m-d') !== $semana) {
            log_linea("Fecha invalida en --semana: '{$semana}'. Formato esperado: AAAA-MM-DD.");
            exit(2);
        }
    } elseif ($arg === '--solo-encolar') {
        $soloEncolar = true;
    } else {
        log_linea("Argumento desconocido: '{$arg}'.");
        exit(2);
    }
}

try {
    if ($soloEncolar) {
        $lunes = CronController::semanaObjetivo($semana);
        $sync  = CronController::sincronizarEnvios($lunes);
        $pend  = (int)\App\Config\Database::getConnection()->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente' AND retenido = 0")->fetchColumn();
        $ret   = (int)\App\Config\Database::getConnection()->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'pendiente' AND retenido = 1")->fetchColumn();
        log_linea("Semana {$lunes}: {$sync['reportes']} envio(s) nuevo(s) por reportes, {$sync['actividad']} por asistencia/notas.");
        log_linea("Envios pendientes que saldrian: {$pend}. Retenidos por el admin (no salen): {$ret}. No se envio nada (--solo-encolar).");
        exit(0);
    }

    log_linea('Inicio del envio semanal.');
    $r = CronController::ejecutarProcesoSemanal($semana, 1500);

    log_linea("Semana {$r['semana']}: {$r['encolados_reportes']} envio(s) nuevo(s) por reportes, {$r['encolados_actividad']} por asistencia/notas.");
    log_linea("Pendientes: {$r['pendientes']} | Enviados: {$r['enviados']} | Con error: {$r['errores']} | Retenidos (no salen): {$r['retenidos']}");
    foreach ($r['detalle_errores'] as $e) {
        log_linea("  Error envio #{$e['envio_id']} ({$e['estudiante']}): {$e['error']}");
    }
    if ($r['abortado']) {
        log_linea('AVISO: ' . $r['abortado']);
    }
    log_linea('Fin.');

    exit(($r['errores'] > 0 || $r['abortado']) ? 1 : 0);
} catch (\Throwable $e) {
    log_linea('ERROR: ' . $e->getMessage());
    exit(1);
}
