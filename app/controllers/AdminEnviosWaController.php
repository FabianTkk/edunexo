<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminEnviosWaController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();

        $estado = SecurityHelper::sanitize($_GET['estado'] ?? '');
        $desde  = SecurityHelper::sanitize($_GET['desde']  ?? '');
        $hasta  = SecurityHelper::sanitize($_GET['hasta']  ?? '');

        $where  = ['1=1'];
        $params = [];

        if (!empty($estado) && in_array($estado, ['pendiente','enviado','entregado','error'])) {
            $where[]  = 'ew.estado = ?';
            $params[] = $estado;
        }
        if (!empty($desde)) { $where[] = 'ew.fecha_hora_envio >= ?'; $params[] = $desde . ' 00:00:00'; }
        if (!empty($hasta)) { $where[] = 'ew.fecha_hora_envio <= ?'; $params[] = $hasta . ' 23:59:59'; }

        $sql = "
            SELECT ew.*, e.nombre_completo AS estudiante, e.ci,
                   t.nombre_completo AS tutor, t.telefono,
                   r.periodo_semana, r.comportamiento, r.dias_ausente
            FROM envios_wa ew
            JOIN reportes r ON r.id = ew.reporte_id
            JOIN estudiantes e ON e.id = r.estudiante_id
            LEFT JOIN tutores_estudiantes te ON te.estudiante_id = e.id
            LEFT JOIN tutores t ON t.id = te.tutor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ew.fecha_hora_envio DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $envios = $stmt->fetchAll();

        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/envios_wa.php';
    }
}
