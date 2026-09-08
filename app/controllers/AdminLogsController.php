<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminLogsController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();

        $resultado = SecurityHelper::sanitize($_GET['resultado'] ?? '');
        $desde     = SecurityHelper::sanitize($_GET['desde']     ?? '');
        $hasta     = SecurityHelper::sanitize($_GET['hasta']     ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($resultado === 'exitoso')  { $where[] = 'lv.resultado = 1'; }
        if ($resultado === 'fallido')  { $where[] = 'lv.resultado = 0'; }
        if (!empty($desde)) { $where[] = 'lv.fecha_intento >= ?'; $params[] = $desde . ' 00:00:00'; }
        if (!empty($hasta)) { $where[] = 'lv.fecha_intento <= ?'; $params[] = $hasta . ' 23:59:59'; }

        $sql = "
            SELECT lv.*, e.nombre_completo, e.ci
            FROM logs_validacion lv
            JOIN estudiantes e ON e.id = lv.estudiante_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY lv.fecha_intento DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $totales = $db->query("SELECT
            COUNT(*) AS total,
            SUM(resultado=1) AS exitosos,
            SUM(resultado=0) AS fallidos
            FROM logs_validacion")->fetch();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/logs.php';
    }
}
