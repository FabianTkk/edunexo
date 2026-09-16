<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminConfiguracionController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $config = $db->query("SELECT * FROM configuracion WHERE id=1 LIMIT 1")->fetch();
        $csrfToken = SecurityHelper::generateCsrfToken();
        require __DIR__ . '/../views/admin/configuracion.php';
    }

    public function update() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/configuracion'); exit;
        }

        $nombre    = SecurityHelper::sanitize($_POST['nombre_colegio']        ?? '');
        $telefono  = SecurityHelper::sanitize($_POST['telefono_wa_remitente'] ?? '');
        $instancia = SecurityHelper::sanitize($_POST['instancia_evolution']   ?? '');
        $directorio= SecurityHelper::sanitize($_POST['directorio_evolution']  ?? '');

        if (empty($nombre) || empty($telefono) || empty($directorio)) {
            $_SESSION['error'] = 'Nombre del colegio, telefono y directorio son obligatorios.';
            header('Location: /edunexo/admin/configuracion'); exit;
        }

        $db = Database::getConnection();
        $existe = $db->query("SELECT id FROM configuracion WHERE id=1 LIMIT 1")->fetch();

        if ($existe) {
            $db->prepare("UPDATE configuracion SET nombre_colegio=?, telefono_wa_remitente=?, instancia_evolution=?, directorio_evolution=? WHERE id=1")
               ->execute([$nombre, $telefono, $instancia, $directorio]);
        } else {
            $db->prepare("INSERT INTO configuracion (id, nombre_colegio, telefono_wa_remitente, instancia_evolution, directorio_evolution) VALUES (1,?,?,?,?)")
               ->execute([$nombre, $telefono, $instancia, $directorio]);
        }

        $_SESSION['success'] = 'Configuracion guardada correctamente.';
        header('Location: /edunexo/admin/configuracion'); exit;
    }

    public function startEvolution() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/configuracion'); exit;
        }

        $db = Database::getConnection();
        $config = $db->query("SELECT directorio_evolution FROM configuracion WHERE id=1 LIMIT 1")->fetch();
        $directorio = $config['directorio_evolution'] ?? 'C:\laragon\www\evolution-api';

        // Escapar el directorio para seguridad en CMD
        $cmdDirectorio = escapeshellarg($directorio);

        // Ejecutar Evolution API en una nueva ventana CMD de forma asincrona
        $cmd = "start cmd /C \"cd /d {$cmdDirectorio} && npm run start\"";
        pclose(popen($cmd, "r"));

        $_SESSION['success'] = 'Comando enviado. Debería abrirse una ventana de consola en el servidor iniciando Evolution API.';
        header('Location: /edunexo/admin/configuracion'); exit;
    }
}
