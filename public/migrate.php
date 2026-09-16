<?php
require 'app/Config/Database.php';

try {
    $db = \App\Config\Database::getConnection();
    
    // Check if column exists first
    $check = $db->query("SHOW COLUMNS FROM configuracion LIKE 'directorio_evolution'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE configuracion ADD COLUMN directorio_evolution VARCHAR(255) DEFAULT 'C:\\laragon\\www\\evolution-api' AFTER instancia_evolution;");
        echo "Exito: Columna agregada.";
    } else {
        echo "Aviso: La columna ya existe.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Borramos el archivo por seguridad
unlink(__FILE__);
