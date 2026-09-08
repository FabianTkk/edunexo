<?php
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /edunexo/login'); exit;
}

// Si es una peticion AJAX (fetch del sidebar), devolver solo el contenido
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) return; // El contenido se renderiza directo, el footer lo cierra

$nombre    = $_SESSION['nombre'];
$inicial   = strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
$csrfToken = \App\Helpers\SecurityHelper::generateCsrfToken();
$uri       = $_SERVER['REQUEST_URI'];

function navActive(string $path): string {
    global $uri;
    return strpos($uri, $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel Admin — EduNexo') ?></title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Bootstrap solo para modales y componentes — sin conflicto con nuestro CSS */
        .dash-content-wrapper { padding: 2rem; flex: 1; }
        .card.bs-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            backdrop-filter: blur(12px);
        }
        .table.bs-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .table.bs-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            background: var(--bg-dark);
        }
        .table.bs-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }
        .table.bs-table tbody tr:last-child td { border-bottom: none; }
        .table.bs-table tbody tr:hover { background: var(--bg-card-hover); }

        /* Transicion del contenido */
        #page-content {
            animation: pageIn .22s ease both;
        }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Loading spinner */
        #page-loader {
            display: none;
            position: fixed;
            top: 0; left: 260px; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            z-index: 999;
            animation: loader .8s ease infinite alternate;
        }
        @keyframes loader {
            from { opacity: .6; }
            to   { opacity: 1; }
        }
    </style>
</head>
<body>

<div id="page-loader"></div>

<div class="dash-layout">

<!-- ── SIDEBAR ─────────────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">🎓</div>
        <span class="sidebar-brand-name">EduNexo</span>
    </div>

    <nav class="sidebar-nav" id="sidebar-nav">

        <span class="nav-section-label">Administracion</span>

        <a href="/edunexo/dashboard" class="nav-item <?= navActive('/dashboard') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-house-fill"></i></span>
            <span>Inicio</span>
        </a>
        <a href="/edunexo/admin/usuarios" class="nav-item <?= navActive('/admin/usuarios') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-people-fill"></i></span>
            <span>Usuarios</span>
        </a>
        <a href="/edunexo/admin/estudiantes" class="nav-item <?= navActive('/admin/estudiantes') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-person-badge-fill"></i></span>
            <span>Estudiantes</span>
        </a>
        <a href="/edunexo/admin/tutores" class="nav-item <?= navActive('/admin/tutores') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-house-heart-fill"></i></span>
            <span>Tutores</span>
        </a>

        <span class="nav-section-label">Reportes</span>

        <a href="/edunexo/admin/reportes" class="nav-item <?= navActive('/admin/reportes') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-bar-chart-fill"></i></span>
            <span>Todos los Reportes</span>
        </a>
        <a href="/edunexo/admin/envios-wa" class="nav-item <?= navActive('/admin/envios-wa') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-whatsapp"></i></span>
            <span>Envios WhatsApp</span>
        </a>
        <a href="/edunexo/admin/logs" class="nav-item <?= navActive('/admin/logs') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-shield-exclamation"></i></span>
            <span>Logs de Validacion</span>
        </a>

        <span class="nav-section-label">Academico</span>

        <a href="/edunexo/admin/cursos" class="nav-item <?= navActive('/admin/cursos') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-building-fill"></i></span>
            <span>Cursos</span>
        </a>
        <a href="/edunexo/admin/materias" class="nav-item <?= navActive('/admin/materias') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-book-fill"></i></span>
            <span>Materias</span>
        </a>
        <a href="/edunexo/admin/asignaciones" class="nav-item <?= navActive('/admin/asignaciones') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-link-45deg"></i></span>
            <span>Asignaciones</span>
        </a>
        <a href="/edunexo/admin/tipos_evaluacion" class="nav-item <?= navActive('/admin/tipos_evaluacion') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-clipboard2-check-fill"></i></span>
            <span>Tipos Eval.</span>
        </a>

        <span class="nav-section-label">Sistema</span>

        <a href="/edunexo/admin/configuracion" class="nav-item <?= navActive('/admin/configuracion') ?>" data-spa>
            <span class="nav-item-icon"><i class="bi bi-gear-fill"></i></span>
            <span>Configuracion</span>
        </a>

        <form method="POST" action="/edunexo/logout" class="logout-form" id="logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="nav-item danger">
                <span class="nav-item-icon"><i class="bi bi-box-arrow-left"></i></span>
                <span>Cerrar Sesion</span>
            </button>
        </form>

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $inicial ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($nombre) ?></div>
                <div class="sidebar-user-role">
                    <span class="role-badge role-admin">admin</span>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- ── MAIN ────────────────────────────────────────────────────────── -->
<main class="dash-main">
    <header class="dash-topbar" id="dash-topbar">
        <div class="dash-topbar-title" id="topbar-title">
            <?= htmlspecialchars($pageTitle ?? 'Panel de Administracion') ?>
        </div>
        <div class="dash-topbar-actions">
            <span class="status-dot">Sesion activa</span>
            <div class="topbar-avatar"><?= $inicial ?></div>
        </div>
    </header>

    <div class="dash-content-wrapper" id="page-content">
