<?php
// public/index.php — Front Controller

// ── Configuración de seguridad de sesión ANTES de session_start() ─────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Activar cuando uses HTTPS

session_start();

// ── Autoloader PSR-4 simple ───────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len     = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ── Router simple ─────────────────────────────────────────────────────────
$url    = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($url) {

    // Raíz → redirigir a login
    case '':
        header('Location: /edunexo/login');
        exit;

    // Login
    case 'login':
        $ctrl = new \App\Controllers\AuthController();
        if ($method === 'POST') {
            $ctrl->login();
        } else {
            $ctrl->index();
        }
        break;

    // Registro (URL discreta — no linkada desde el login)
    case 'register':
        $ctrl = new \App\Controllers\AuthController();
        if ($method === 'POST') {
            $ctrl->register();
        } else {
            $ctrl->showRegister();
        }
        break;

    // Logout
    case 'logout':
        $ctrl = new \App\Controllers\AuthController();
        $ctrl->logout();
        break;

    // Dashboard
    case 'dashboard':
        $ctrl = new \App\Controllers\DashboardController();
        $ctrl->index();
        break;

    // Admin Cursos
    case 'admin/cursos':
        $ctrl = new \App\Controllers\AdminCursosController();
        $ctrl->index();
        break;
    case 'admin/cursos/store':
        $ctrl = new \App\Controllers\AdminCursosController();
        $ctrl->store();
        break;
    case 'admin/cursos/update':
        $ctrl = new \App\Controllers\AdminCursosController();
        $ctrl->update();
        break;
    case 'admin/cursos/toggle':
        $ctrl = new \App\Controllers\AdminCursosController();
        $ctrl->toggle();
        break;

    // Admin Materias
    case 'admin/materias':
        $ctrl = new \App\Controllers\AdminMateriasController();
        $ctrl->index();
        break;
    case 'admin/materias/store':
        $ctrl = new \App\Controllers\AdminMateriasController();
        $ctrl->store();
        break;
    case 'admin/materias/update':
        $ctrl = new \App\Controllers\AdminMateriasController();
        $ctrl->update();
        break;
    case 'admin/materias/toggle':
        $ctrl = new \App\Controllers\AdminMateriasController();
        $ctrl->toggle();
        break;

    // Admin Asignaciones
    case 'admin/asignaciones':
        $ctrl = new \App\Controllers\AdminAsignacionesController();
        $ctrl->index();
        break;
    case 'admin/asignaciones/bulk':
        $ctrl = new \App\Controllers\AdminAsignacionesController();
        $ctrl->bulk();
        break;
    case 'admin/asignaciones/single':
        $ctrl = new \App\Controllers\AdminAsignacionesController();
        $ctrl->single();
        break;

    // Docente Materias
    case 'docente/materias':
        $ctrl = new \App\Controllers\DocenteMateriasController();
        $ctrl->index();
        break;

    // Docente Evaluaciones
    case 'docente/evaluaciones':
        $ctrl = new \App\Controllers\DocenteEvaluacionesController();
        $ctrl->index();
        break;
    case 'docente/evaluaciones/store':
        $ctrl = new \App\Controllers\DocenteEvaluacionesController();
        $ctrl->store();
        break;
    case 'docente/evaluaciones/update':
        $ctrl = new \App\Controllers\DocenteEvaluacionesController();
        $ctrl->update();
        break;
    case 'docente/evaluaciones/delete':
        $ctrl = new \App\Controllers\DocenteEvaluacionesController();
        $ctrl->delete();
        break;

    // Docente Notas
    case 'docente/notas':
        $ctrl = new \App\Controllers\DocenteNotasController();
        $ctrl->index();
        break;
    case 'docente/notas/store':
        $ctrl = new \App\Controllers\DocenteNotasController();
        $ctrl->bulkStore();
        break;

    // Admin Tipos Evaluación
    case 'admin/tipos_evaluacion':
        $ctrl = new \App\Controllers\AdminTipoEvaluacionController();
        $ctrl->index();
        break;
    case 'admin/tipos_evaluacion/store':
        $ctrl = new \App\Controllers\AdminTipoEvaluacionController();
        $ctrl->store();
        break;
    case 'admin/tipos_evaluacion/update':
        $ctrl = new \App\Controllers\AdminTipoEvaluacionController();
        $ctrl->update();
        break;
    case 'admin/tipos_evaluacion/toggle':
        $ctrl = new \App\Controllers\AdminTipoEvaluacionController();
        $ctrl->toggle();
        break;

    // 404
    default:
        http_response_code(404);
        echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:4rem;color:#888">404 — Página no encontrada</h1>';
        break;
}
?>
