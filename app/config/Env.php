<?php
namespace App\Config;

/**
 * Lector minimo de .env (en la raiz del proyecto, fuera de public/).
 * Formato: CLAVE=valor, una por linea. Se ignoran lineas vacias y las que empiezan con #.
 * Las variables reales del entorno (getenv) tienen prioridad sobre el archivo.
 */
class Env {
    private static ?array $vars = null;

    public static function get(string $key, ?string $default = null): ?string {
        $real = getenv($key);
        if ($real !== false && $real !== '') {
            return $real;
        }

        if (self::$vars === null) {
            self::cargar();
        }

        $valor = self::$vars[$key] ?? '';
        return $valor !== '' ? $valor : $default;
    }

    private static function cargar(): void {
        self::$vars = [];
        $ruta = __DIR__ . '/../../.env';
        if (!is_file($ruta)) {
            return;
        }

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            $valor = trim($valor);
            // Quita comillas simples o dobles alrededor del valor
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }
            self::$vars[trim($clave)] = $valor;
        }
    }
}
