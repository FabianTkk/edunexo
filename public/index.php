<?php
// public/index.php — Front Controller

// Zona horaria unica del sistema (Paraguay: UTC-3 todo el anio, sin horario de verano desde octubre de 2024).
// Database.php hace que MySQL use este mismo desfase. Si tu PHP tiene la base de zonas desactualizada y
// date('P') no da -03:00 con America/Asuncion, usar 'Etc/GMT+3' (fijo, sin horario de verano).
date_default_timezone_set('America/Asuncion');

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len     = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require $file;
});

$url    = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];

define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// ── Rutas publicas ────────────────────────────────────────────────────────
$rutasPublicas = ['', 'login', 'register', 'logout'];

// ── Manejo centralizado de sesion ─────────────────────────────────────────
if (!in_array($url, $rutasPublicas) && isset($_SESSION['user_id'])) {
    $timeout = 120 * 60; // 2 horas

    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > $timeout) {
            session_unset();
            session_destroy();
            header('Location: /edunexo/login');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

// ── CSRF token global ─────────────────────────────────────────────────────
// Se genera una sola vez aqui para que TODAS las vistas lo tengan
// disponible sin depender de que el controlador lo genere
if (isset($_SESSION['user_id'])) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
} else {
    $csrfToken = '';
}

switch ($url) {

    case '':
        header('Location: /edunexo/login'); exit;

    case 'login':
        $ctrl = new \App\Controllers\AuthController();
        $method === 'POST' ? $ctrl->login() : $ctrl->index();
        break;

    case 'register':
        $ctrl = new \App\Controllers\AuthController();
        $method === 'POST' ? $ctrl->register() : $ctrl->showRegister();
        break;

    case 'logout':
        (new \App\Controllers\AuthController())->logout();
        break;

    case 'dashboard':
        (new \App\Controllers\DashboardController())->index();
        break;

    // ── Admin Usuarios ────────────────────────────────────────────────────
    case 'admin/usuarios':
        (new \App\Controllers\AdminUsuariosController())->index(); break;
    case 'admin/usuarios/store':
        (new \App\Controllers\AdminUsuariosController())->store(); break;
    case 'admin/usuarios/update':
        (new \App\Controllers\AdminUsuariosController())->update(); break;
    case 'admin/usuarios/toggle':
        (new \App\Controllers\AdminUsuariosController())->toggle(); break;

    // ── Admin Estudiantes ─────────────────────────────────────────────────
    case 'admin/estudiantes':
        (new \App\Controllers\AdminEstudiantesController())->index(); break;
    case 'admin/estudiantes/store':
        (new \App\Controllers\AdminEstudiantesController())->store(); break;
    case 'admin/estudiantes/update':
        (new \App\Controllers\AdminEstudiantesController())->update(); break;
    case 'admin/estudiantes/toggle':
        (new \App\Controllers\AdminEstudiantesController())->toggle(); break;

    // ── Admin Tutores ─────────────────────────────────────────────────────
    case 'admin/tutores':
        (new \App\Controllers\AdminTutoresController())->index(); break;
    case 'admin/tutores/store':
        (new \App\Controllers\AdminTutoresController())->store(); break;
    case 'admin/tutores/update':
        (new \App\Controllers\AdminTutoresController())->update(); break;
    case 'admin/tutores/toggle':
        (new \App\Controllers\AdminTutoresController())->toggle(); break;

    // ── Admin Cursos ──────────────────────────────────────────────────────
    case 'admin/cursos':
        (new \App\Controllers\AdminCursosController())->index(); break;
    case 'admin/cursos/store':
        (new \App\Controllers\AdminCursosController())->store(); break;
    case 'admin/cursos/update':
        (new \App\Controllers\AdminCursosController())->update(); break;
    case 'admin/cursos/toggle':
        (new \App\Controllers\AdminCursosController())->toggle(); break;

    // ── Admin Materias ────────────────────────────────────────────────────
    case 'admin/materias':
        (new \App\Controllers\AdminMateriasController())->index(); break;
    case 'admin/materias/store':
        (new \App\Controllers\AdminMateriasController())->store(); break;
    case 'admin/materias/update':
        (new \App\Controllers\AdminMateriasController())->update(); break;
    case 'admin/materias/toggle':
        (new \App\Controllers\AdminMateriasController())->toggle(); break;

    // ── Admin Asignaciones ────────────────────────────────────────────────
    case 'admin/asignaciones':
        (new \App\Controllers\AdminAsignacionesController())->index(); break;
    case 'admin/asignaciones/single':
        (new \App\Controllers\AdminAsignacionesController())->single(); break;
    case 'admin/asignaciones/remove':
        (new \App\Controllers\AdminAsignacionesController())->remove(); break;

    // ── Admin Tipos Evaluacion ────────────────────────────────────────────
    case 'admin/tipos_evaluacion':
        (new \App\Controllers\AdminTipoEvaluacionController())->index(); break;
    case 'admin/tipos_evaluacion/store':
        (new \App\Controllers\AdminTipoEvaluacionController())->store(); break;
    case 'admin/tipos_evaluacion/update':
        (new \App\Controllers\AdminTipoEvaluacionController())->update(); break;
    case 'admin/tipos_evaluacion/toggle':
        (new \App\Controllers\AdminTipoEvaluacionController())->toggle(); break;

    // ── Admin Reportes ────────────────────────────────────────────────────
    case 'admin/reportes':
        (new \App\Controllers\AdminReportesController())->index(); break;
    case 'admin/reportes/curso':
        (new \App\Controllers\AdminReportesController())->porCurso(); break;
    case 'admin/reportes/ci':
        (new \App\Controllers\AdminReportesController())->porCI(); break;
    case 'admin/reportes/gestionar':
        (new \App\Controllers\AdminReportesController())->gestionar(); break;
    case 'admin/reportes/actualizar':
        (new \App\Controllers\AdminReportesController())->actualizar(); break;
    case 'admin/reportes/eliminar':
        (new \App\Controllers\AdminReportesController())->eliminar(); break;

    // ── Admin Envios WA ───────────────────────────────────────────────────
    case 'admin/envios-wa':
        (new \App\Controllers\AdminEnviosWaController())->index(); break;
    case 'admin/envios-wa/enviar':
        (new \App\Controllers\AdminEnviosWaController())->enviarIndividual(); break;
    case 'admin/envios-wa/procesar-pendientes':
        (new \App\Controllers\AdminEnviosWaController())->procesarPendientes(); break;
    case 'admin/envios-wa/test-directo':
        (new \App\Controllers\AdminEnviosWaController())->testDirecto(); break;

    // ── Admin Logs ────────────────────────────────────────────────────────
    case 'admin/logs':
        (new \App\Controllers\AdminLogsController())->index(); break;

    // ── Admin Configuracion ───────────────────────────────────────────────
    case 'admin/configuracion':
        (new \App\Controllers\AdminConfiguracionController())->index(); break;
    case 'admin/configuracion/update':
        (new \App\Controllers\AdminConfiguracionController())->update(); break;

    // ── Docente ───────────────────────────────────────────────────────────
    case 'docente/materias':
        (new \App\Controllers\DocenteMateriasController())->index(); break;

    case 'docente/evaluaciones':
        (new \App\Controllers\DocenteEvaluacionesController())->index(); break;
    case 'docente/evaluaciones/store':
        (new \App\Controllers\DocenteEvaluacionesController())->store(); break;
    case 'docente/evaluaciones/update':
        (new \App\Controllers\DocenteEvaluacionesController())->update(); break;
    case 'docente/evaluaciones/delete':
        (new \App\Controllers\DocenteEvaluacionesController())->delete(); break;

    case 'docente/notas':
        (new \App\Controllers\DocenteNotasController())->index(); break;
    case 'docente/notas/store':
        (new \App\Controllers\DocenteNotasController())->bulkStore(); break;
    case 'docente/estudiantes':
        (new \App\Controllers\DocenteGestionController())->estudiantes(); break;
    case 'docente/estudiantes/asistencia':
        (new \App\Controllers\DocenteGestionController())->guardarAsistencia(); break;
    case 'docente/reportes':
        $ctrl = new \App\Controllers\DocenteGestionController();
        if ($method !== 'POST') { $ctrl->reportes(); break; }
        match ($_POST['accion'] ?? 'crear') {
            'actualizar' => $ctrl->actualizarReporte(),
            'eliminar' => $ctrl->eliminarReporte(),
            'solicitar_cambio' => $ctrl->solicitarCambioReporte(),
            default => $ctrl->guardarReporte(),
        };
        break;
    case 'docente/envios-wa':
        $ctrl = new \App\Controllers\DocenteGestionController(); $method === 'POST' ? $ctrl->prepararEnviosWhatsApp() : $ctrl->enviosWhatsApp(); break;
    case 'docente/calendario':
        (new \App\Controllers\DocenteGestionController())->calendario(); break;
    case 'docente/mensajes':
        $ctrl = new \App\Controllers\DocenteGestionController(); $method === 'POST' ? $ctrl->guardarMensaje() : $ctrl->mensajes(); break;
    case 'docente/perfil':
        $ctrl = new \App\Controllers\DocenteGestionController(); $method === 'POST' ? $ctrl->guardarPerfil() : $ctrl->perfil(); break;

    // ── CronJobs ──────────────────────────────────────────────────────────
    case 'cron/procesar-envios':
        (new \App\Controllers\CronController())->procesarEnviosSemanales(); break;

    // ── 404 ───────────────────────────────────────────────────────────────
    default:
        http_response_code(404);
        echo '<div id="page-content"><h1 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#888">404 — Pagina no encontrada</h1></div>';
        break;
}
?>
