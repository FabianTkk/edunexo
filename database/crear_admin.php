<?php
// database/crear_admin.php
// Crea (o resetea) el usuario administrador desde la consola, sin guardar contrasenas en el repositorio.
// Uso (desde la terminal de Laragon):   php database/crear_admin.php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo se ejecuta desde la consola.');
}

require __DIR__ . '/../app/config/Database.php';

function preguntar(string $texto): string {
    echo $texto;
    return trim((string)fgets(STDIN));
}

$username = preguntar('Usuario (3 a 30 caracteres: letras, numeros o guion bajo): ');
if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    exit("Usuario invalido.\n");
}

$nombre = preguntar('Nombre completo: ');
if (strlen($nombre) < 2) {
    exit("Nombre invalido.\n");
}

$email = preguntar('Email (opcional, Enter para omitir): ');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Email invalido.\n");
}

// Nota: lo que escribis se ve en pantalla. Ejecutalo solo en tu PC.
$password = preguntar('Contrasena (minimo 10 caracteres): ');
if (strlen($password) < 10) {
    exit("La contrasena debe tener al menos 10 caracteres.\n");
}

$db = \App\Config\Database::getConnection();
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare('SELECT id FROM usuarios WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$existente = $stmt->fetchColumn();

if ($existente) {
    $resp = strtolower(preguntar("Ya existe '{$username}'. Lo convierto en admin activo y le cambio la contrasena? (s/n): "));
    if ($resp !== 's') {
        exit("Cancelado.\n");
    }
    $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = 'admin', activo = 1 WHERE id = ?")
       ->execute([$nombre, $email !== '' ? $email : null, $hash, $existente]);
    echo "Usuario actualizado.\n";
} else {
    $db->prepare("INSERT INTO usuarios (nombre, username, password, email, rol, activo, created_at) VALUES (?, ?, ?, ?, 'admin', 1, NOW())")
       ->execute([$nombre, $username, $hash, $email !== '' ? $email : null]);
    echo "Administrador creado.\n";
}
