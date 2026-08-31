<?php
namespace App\Config;

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $dsn = "mysql:host=localhost;dbname=edunexo_db;charset=utf8mb4";
            $user = "root";
            $pass = "";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            try {
                self::$pdo = new \PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                die('Database connection error: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
