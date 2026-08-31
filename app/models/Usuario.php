<?php
namespace App\Models;

use App\Config\Database;

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Busca un usuario por su nombre de usuario (campo 'username').
     */
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca un usuario por email.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Verifica si el email ya existe en la BD.
     */
    public function emailExists(string $email): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si el nombre de usuario ya existe en la BD.
     */
public function usernameExists($username) {
    // 1. El nombre del marcador aquí debe ser :username
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = :username");
    
    // 2. La clave en el arreglo DEBE coincidir con el marcador ':username'
    $stmt->execute([':username' => $username]);
    
    return $stmt->fetchColumn() > 0;
}

/**
     * Crea un nuevo usuario con contraseña hasheada en bcrypt.
     * @return int|false ID del nuevo usuario o false en caso de error
     */
    public function createUser(array $data): int|false {
        // Verificar si la tabla tiene la columna 'email'
        $hasEmail = $this->columnExists('email');

        // Extraer el nombre de usuario
        $usernameVal = $data['username'] ?? $data['usuario'] ?? '';

        // Validar rol: si viene 'admin' usa 'admin', de lo contrario asigna 'docente'
        $rol = (isset($data['rol']) && $data['rol'] === 'admin') ? 'admin' : 'docente';

        if ($hasEmail) {
            $sql = 'INSERT INTO usuarios (nombre, email, username, password, rol, activo, created_at)
                    VALUES (:nombre, :email, :username, :password, :rol, 1, NOW())';
            $params = [
                ':nombre'   => $data['nombre'],
                ':email'    => $data['email'] ?? null,
                ':username' => $usernameVal,
                ':password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                ':rol'      => $rol,
            ];
        } else {
            $sql = 'INSERT INTO usuarios (nombre, username, password, rol, activo, created_at)
                    VALUES (:nombre, :username, :password, :rol, 1, NOW())';
            $params = [
                ':nombre'   => $data['nombre'],
                ':username' => $usernameVal,
                ':password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                ':rol'      => $rol,
            ];
        }

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Verifica si una columna existe en la tabla usuarios.
     */
    private function columnExists(string $column): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = :col"
            );
            $stmt->execute([':col' => $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
?>