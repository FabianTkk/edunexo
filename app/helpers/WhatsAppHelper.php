<?php
namespace App\Helpers;

use App\Config\Database;

class WhatsAppHelper {

    // Valores por defecto sincronizados con evolution-api/.env
    public const DEFAULT_API_URL = 'http://localhost:8080';
    public const DEFAULT_API_KEY = 'edunexo_secret_key_2026';
    public const DEFAULT_INSTANCE = 'edunexo';

    /**
     * Envía un mensaje de texto usando Evolution API v2.
     * Retorna true si fue exitoso, false en caso contrario.
     *
     * @param string $telefono Número del destinatario (ej: 595981123456 o +595 981 123456)
     * @param string $mensaje Contenido del mensaje de texto
     * @param array|null $debugInfo Si se pasa por referencia, recibe detalles de la petición y respuesta
     * @return bool
     */
    public static function enviar(string $telefono, string $mensaje, ?array &$debugInfo = null): bool {
        // 1. Limpiar número telefónico (eliminar +, guiones, espacios, etc.)
        $telefonoLimpio = preg_replace('/\D+/', '', $telefono);
        if (empty($telefonoLimpio) || trim($mensaje) === '') {
            error_log("[WhatsAppHelper] Error de validación: Teléfono o mensaje vacío. Teléfono recibido: '{$telefono}'");
            if ($debugInfo !== null) {
                $debugInfo = ['error' => 'Teléfono o mensaje vacío', 'http_code' => 0];
            }
            return false;
        }

        // 2. Obtener configuración de la base de datos (o usar valores por defecto)
        $instancia = self::DEFAULT_INSTANCE;
        $baseUrl   = self::DEFAULT_API_URL;
        $apiKey    = self::DEFAULT_API_KEY;

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM configuracion WHERE id = 1 LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                if (!empty($config['instancia_evolution'])) {
                    $instancia = trim($config['instancia_evolution']);
                }
                if (!empty($config['url_evolution'])) {
                    $baseUrl = rtrim(trim($config['url_evolution']), '/');
                }
                if (!empty($config['apikey_evolution'])) {
                    $apiKey = trim($config['apikey_evolution']);
                }
            }
        } catch (\Throwable $e) {
            error_log("[WhatsAppHelper] Aviso: No se pudo leer la tabla configuracion, usando valores por defecto: " . $e->getMessage());
        }

        // 3. Endpoint para Evolution API v2: /message/sendText/{instance}
        $apiUrl = "{$baseUrl}/message/sendText/" . rawurlencode($instancia);

        // 4. Payload adaptado a Evolution API v2.3.x (SendTextDto: number, text, delay)
        $payload = [
            'number' => $telefonoLimpio,
            'text'   => $mensaje,
            'delay'  => 1200
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 5. Ejecutar petición cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response  = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($debugInfo !== null) {
            $debugInfo = [
                'url'        => $apiUrl,
                'instancia'  => $instancia,
                'payload'    => $payload,
                'http_code'  => $httpCode,
                'response'   => $response,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno
            ];
        }

        // 6. Manejo y registro de errores
        if ($curlErrno !== 0) {
            error_log("[WhatsAppHelper] Error cURL ({$curlErrno}): {$curlError} al conectar con {$apiUrl}");
            return false;
        }

        // 200 OK o 201 Created indican éxito en Evolution API
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("[WhatsAppHelper] Error de envío Evolution API (HTTP {$httpCode}): " . substr((string)$response, 0, 300));
        return false;
    }

    /**
     * Consulta el estado de conexión de la instancia de WhatsApp en Evolution API.
     * Útil para pruebas, diagnósticos y panel de control.
     *
     * @return array [
     *   'online' => bool,
     *   'state' => string ('open', 'close', 'connecting', 'offline', etc.),
     *   'http_code' => int,
     *   'detalles' => mixed
     * ]
     */
    public static function estadoInstancia(): array {
        $instancia = self::DEFAULT_INSTANCE;
        $baseUrl   = self::DEFAULT_API_URL;
        $apiKey    = self::DEFAULT_API_KEY;

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM configuracion WHERE id = 1 LIMIT 1");
            $config = $stmt->fetch();
            if ($config) {
                if (!empty($config['instancia_evolution'])) {
                    $instancia = trim($config['instancia_evolution']);
                }
                if (!empty($config['url_evolution'])) {
                    $baseUrl = rtrim(trim($config['url_evolution']), '/');
                }
                if (!empty($config['apikey_evolution'])) {
                    $apiKey = trim($config['apikey_evolution']);
                }
            }
        } catch (\Throwable $e) {
            // Ignorar y seguir con defaults
        }

        $apiUrl = "{$baseUrl}/instance/connectionState/" . rawurlencode($instancia);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response  = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 0) {
            return [
                'online'    => false,
                'state'     => 'offline',
                'http_code' => 0,
                'error'     => $curlError ?: 'No se pudo conectar con el servidor de Evolution API (el servicio está apagado o no responde en el puerto 8080).'
            ];
        }

        $json = json_decode((string)$response, true);
        $state = $json['instance']['state'] ?? ($json['state'] ?? 'desconocido');

        return [
            'online'    => ($httpCode >= 200 && $httpCode < 300),
            'state'     => $state,
            'http_code' => $httpCode,
            'detalles'  => $json
        ];
    }
}
