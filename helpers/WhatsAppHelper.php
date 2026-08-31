<?php

class WhatsAppHelper {
    private static $apiUrl = 'http://localhost:8080/message/sendText/edunexo';
    private static $apiKey = 'edunexo_secret_key_2026';

    /**
     * Enviar mensaje de texto a través de WhatsApp (Evolution API)
     *
     * @param string $number Número de teléfono con código de país
     * @param string $message Mensaje a enviar
     * @return mixed Array con la respuesta si es exitoso, false si falla
     */
    public static function sendText($number, $message) {
        // Limpiar el número de caracteres no numéricos (ej. +, espacios, guiones)
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);

        $payload = [
            'number' => $cleanNumber,
            'text'   => $message
        ];

        $ch = curl_init(self::$apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . self::$apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true) ?: true;
        }

        return false;
    }
}
