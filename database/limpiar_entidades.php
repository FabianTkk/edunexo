<?php
/**
 * Limpieza unica: deshace el escapado HTML que sanitize() aplicaba al GUARDAR.
 *
 * Antes, un texto como  O'Brien & "hijo"  se guardaba como  O&#039;Brien &amp; &quot;hijo&quot;.
 * Ahora sanitize() guarda texto plano y el escapado se hace al mostrar, asi que los datos
 * viejos hay que dejarlos como texto plano tambien.
 *
 * Uso (desde la carpeta del proyecto):
 *   php database/limpiar_entidades.php            -> solo muestra que cambiaria (no escribe nada)
 *   php database/limpiar_entidades.php --apply    -> aplica los cambios en una transaccion
 *
 * IMPORTANTE: hacer una copia de la base antes de usar --apply:
 *   mysqldump -u root edunexo_db > backup_antes_de_limpiar.sql
 *
 * Es seguro ejecutarlo mas de una vez: si ya no hay entidades, no cambia nada.
 */
if (PHP_SAPI !== 'cli') { exit("Este script se ejecuta solo desde la linea de comandos.\n"); }

require __DIR__ . '/../app/config/Database.php';

$aplicar = in_array('--apply', $argv, true);
$db = \App\Config\Database::getConnection();

// Tabla => columnas de texto que pasaban por SecurityHelper::sanitize() al guardarse.
$columnas = [
    'estudiantes'                 => ['ci', 'nombre_completo'],
    'usuarios'                    => ['nombre', 'username', 'email'],
    'cursos'                      => ['nombre'],
    'materias'                    => ['nombre'],
    'tipo_evaluacion'             => ['nombre'],
    'tutores'                     => ['nombre_completo', 'telefono'],
    'reportes'                    => ['incidentes_disciplinarios'],
    'solicitudes_cambio_reportes' => ['motivo'],
    'evaluaciones'                => ['titulo', 'descripcion'],
    'notas'                       => ['observacion'],
    'avisos'                      => ['titulo', 'descripcion'],
    'configuracion'               => ['nombre_colegio', 'telefono_wa_remitente', 'instancia_evolution', 'directorio_evolution'],
];

// Solo las 5 entidades que generaba htmlspecialchars(ENT_QUOTES). No toca ninguna otra (&nbsp;, &copy;, ...).
const ENTIDADES = ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', '&#039;' => "'", '&#39;' => "'"];

/** Decodifica hasta que el texto deje de cambiar: editar un texto ya escapado lo escapaba otra vez (&amp;#039;). */
function decodificar(string $valor): string {
    for ($i = 0; $i < 6; $i++) {
        $nuevo = strtr($valor, ENTIDADES);
        if ($nuevo === $valor) break;
        $valor = $nuevo;
    }
    return $valor;
}

$totalFilas = 0;
$cambios = [];   // [tabla, columna, id, antes, despues]

foreach ($columnas as $tabla => $cols) {
    // La tabla puede no existir todavia (p. ej. solicitudes_cambio_reportes sin migrar).
    $existe = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $existe->execute([$tabla]);
    if (!(int)$existe->fetchColumn()) { echo "[omitida] $tabla (no existe en esta base)\n"; continue; }

    foreach ($cols as $col) {
        $filas = $db->query("SELECT id, `$col` AS valor FROM `$tabla` WHERE `$col` LIKE '%&%;%'")->fetchAll();
        foreach ($filas as $fila) {
            $despues = decodificar((string)$fila['valor']);
            if ($despues !== $fila['valor']) {
                $cambios[] = [$tabla, $col, (int)$fila['id'], $fila['valor'], $despues];
            }
        }
    }
}

if (!$cambios) { echo "No hay textos con entidades HTML. No hay nada que limpiar.\n"; exit(0); }

echo ($aplicar ? "Aplicando" : "Se cambiarian") . " " . count($cambios) . " valor(es):\n\n";
foreach ($cambios as [$tabla, $col, $id, $antes, $despues]) {
    $corto = fn(string $s) => mb_strlen($s) > 60 ? mb_substr($s, 0, 57) . '...' : $s;
    echo sprintf("  %s.%s (id %d)\n      antes:   %s\n      despues: %s\n", $tabla, $col, $id, $corto($antes), $corto($despues));
}

if (!$aplicar) {
    echo "\nModo prueba: no se escribio nada. Para aplicar: php database/limpiar_entidades.php --apply\n";
    exit(0);
}

$db->beginTransaction();
try {
    foreach ($cambios as [$tabla, $col, $id, , $despues]) {
        $db->prepare("UPDATE `$tabla` SET `$col` = ? WHERE id = ?")->execute([$despues, $id]);
    }
    $db->commit();
    echo "\nListo: " . count($cambios) . " valor(es) actualizados.\n";
} catch (\Throwable $e) {
    $db->rollBack();
    echo "\nERROR, no se cambio nada: " . $e->getMessage() . "\n";
    exit(1);
}
