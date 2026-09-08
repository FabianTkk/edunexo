<?php
namespace App\Helpers;

use App\Config\Database;

class WhatsAppHelper {
    
    /**
     * Envía un mensaje de texto usando Evolution API.
     * Retorna true si fue exitoso, false en caso contrario.
     */
    public static function enviar(string $telefono, string $mensaje): bool {
        // Obtener configuración global
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM configuracion WHERE id = 1");
        $config = $stmt->fetch();
        
        if (!$config || empty($config['instancia_evolution'])) {
            return false;
        }
        
        $instancia = $config['instancia_evolution'];
        
        // Evolution API configuration
        // Reemplazar URL y API KEY según la instalación real de Evolution API
        $apiUrl = 'http://localhost:8080/message/sendText/' . $instancia; 
        // Asumimos que la API de Evolution no requiere apikey global (o habría que configurarla)
        // Enviar JSON
        
        $data = [
            'number' => $telefono,
            'options' => [
                'delay' => 1200,
                'presence' => 'composing'
            ],
            'textMessage' => [
                'text' => $mensaje
            ]
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: apikey_aqui_si_es_necesario' // Puede que no se necesite en local, o debería ir en config
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Asumiendo que 200/201 indica éxito
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        return false;
    }
}
