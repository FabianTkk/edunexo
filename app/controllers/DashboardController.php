<?php
namespace App\Controllers;

use App\Helpers\SecurityHelper;
use App\Config\Database;

class DashboardController {

    public function index(): void {
        SecurityHelper::setSecurityHeaders();

        if (!isset($_SESSION['user_id'])) {
            header('Location: /edunexo/login');
            exit;
        }

        // El timeout de sesion se maneja centralizadamente en el router (index.php)
        // para que aplique uniformemente a todos los modulos

        $nombre  = htmlspecialchars($_SESSION['nombre']  ?? 'Usuario', ENT_QUOTES, 'UTF-8');
        $usuario = htmlspecialchars($_SESSION['usuario'] ?? '',         ENT_QUOTES, 'UTF-8');
        $rol     = htmlspecialchars($_SESSION['rol']     ?? 'docente',  ENT_QUOTES, 'UTF-8');
        $userId  = (int)$_SESSION['user_id'];

        $csrfToken = SecurityHelper::generateCsrfToken();
        $db = Database::getConnection();

        if ($rol === 'admin') {
            $stats = [];

            $stmt = $db->query('SELECT COUNT(*) FROM usuarios');
            $stats['total_usuarios'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'docente'");
            $stats['total_docentes'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'activo'");
            $stats['total_estudiantes'] = (int)$stmt->fetchColumn();

            $stmt = $db->query('SELECT COUNT(*) FROM reportes');
            $stats['total_reportes'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'error'");
            $stats['errores_wa'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM envios_wa WHERE estado = 'enviado' OR estado = 'entregado'");
            $stats['envios_ok'] = (int)$stmt->fetchColumn();

            $stmt = $db->query(
                'SELECT id, nombre, username AS usuario, rol, created_at
                 FROM usuarios
                 ORDER BY created_at DESC
                 LIMIT 8'
            );
            $usuarios_recientes = $stmt->fetchAll();

            $stmt = $db->query(
                'SELECT r.id, e.nombre_completo, e.curso, u.nombre AS docente,
                        r.calificacion_general, r.comportamiento, r.dias_ausente, r.periodo_semana
                 FROM reportes r
                 JOIN estudiantes e ON e.id = r.estudiante_id
                 LEFT JOIN usuarios u ON u.id = r.usuario_id
                 ORDER BY r.created_at DESC
                 LIMIT 6'
            );
            $reportes_recientes = $stmt->fetchAll();

            require __DIR__ . '/../views/dashboard/admin.php';

        } elseif ($rol === 'docente') {
            $stats = [];

            $stmt = $db->prepare(
                'SELECT COUNT(DISTINCT r.estudiante_id) FROM reportes r WHERE r.usuario_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);
            $stats['mis_estudiantes'] = (int)$stmt->fetchColumn();

            // Solo alumnos de cursos y materias asignados al docente actual.
            $stmt = $db->prepare(
                "SELECT COUNT(DISTINCT e.id)
                 FROM estudiantes e
                 JOIN curso_materia_docente cmd ON cmd.curso_id = e.curso_id
                 WHERE cmd.docente_id = :uid
                   AND cmd.activo = 1
                   AND e.estado = 'activo'"
            );
            $stmt->execute([':uid' => $userId]);
            $stats['estudiantes_asignados'] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM reportes WHERE usuario_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);
            $stats['mis_reportes'] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM envios_wa wa
                 JOIN reportes r ON r.id = wa.reporte_id
                 WHERE r.usuario_id = :uid AND (wa.estado = 'enviado' OR wa.estado = 'entregado')"
            );
            $stmt->execute([':uid' => $userId]);
            $stats['envios_ok'] = (int)$stmt->fetchColumn();

            $stmt = $db->prepare(
                'SELECT e.nombre_completo, e.curso,
                        SUM(r.dias_ausente) AS total_ausencias,
                        COUNT(r.id) AS total_reportes
                 FROM reportes r
                 JOIN estudiantes e ON e.id = r.estudiante_id
                 WHERE r.usuario_id = :uid
                 GROUP BY e.id
                 ORDER BY total_ausencias DESC
                 LIMIT 6'
            );
            $stmt->execute([':uid' => $userId]);
            $estudiantes_ausencias = $stmt->fetchAll();

            $stmt = $db->prepare(
                'SELECT r.id, e.nombre_completo, e.curso, r.calificacion_general,
                        r.comportamiento, r.dias_ausente, r.periodo_semana,
                        r.created_at
                 FROM reportes r
                 JOIN estudiantes e ON e.id = r.estudiante_id
                 WHERE r.usuario_id = :uid
                 ORDER BY r.created_at DESC
                 LIMIT 8'
            );
            $stmt->execute([':uid' => $userId]);
            $mis_reportes = $stmt->fetchAll();

            $stmt = $db->prepare(
                'SELECT wa.estado, wa.destinatario_telefono, wa.fecha_hora_envio,
                        e.nombre_completo
                 FROM envios_wa wa
                 JOIN reportes r ON r.id = wa.reporte_id
                 JOIN estudiantes e ON e.id = r.estudiante_id
                 WHERE r.usuario_id = :uid
                 ORDER BY wa.fecha_hora_envio DESC
                 LIMIT 6'
            );
            $stmt->execute([':uid' => $userId]);
            $envios_recientes = $stmt->fetchAll();

            require __DIR__ . '/../views/dashboard/docente.php';

        } else {
            require __DIR__ . '/../views/dashboard/index.php';
        }
    }
}
?>
