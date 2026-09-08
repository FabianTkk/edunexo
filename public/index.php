<?php
// public/index.php — Front Controller

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

// Detectar peticion AJAX del SPA router
// Los layouts lo usan para saber si renderizar el shell completo
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

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
    case 'admin/asignaciones/bulk':
        (new \App\Controllers\AdminAsignacionesController())->bulk(); break;
    case 'admin/asignaciones/single':
        (new \App\Controllers\AdminAsignacionesController())->single(); break;

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

    // ── Admin Envios WA ───────────────────────────────────────────────────
    case 'admin/envios-wa':
        (new \App\Controllers\AdminEnviosWaController())->index(); break;

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
