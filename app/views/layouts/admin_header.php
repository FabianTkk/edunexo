<?php
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /edunexo/login'); exit;
}
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
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Admin — EduNexo' ?></title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: var(--bg-deep); color: var(--text-primary); }
        .sidebar { border-top: 3px solid hsl(0,70%,58%); }
        .dash-content-wrapper { padding: 2rem; flex: 1; }
        a { text-decoration: none; }
        .btn { border-radius: var(--bs-border-radius); }
        .card.bs-card { background-color: #1a1d2d; border: 1px solid rgba(255,255,255,0.1); }
        .table.bs-table { --bs-table-bg: transparent; --bs-table-color: #e2e8f0; }
        .table.bs-table th { border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; }
        .table.bs-table td { border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    </style>
</head>
<body>
<div class="dash-layout">

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">&#127891;</div>
        <span class="sidebar-brand-name">EduNexo</span>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-label">Administracion</span>
        <a href="/edunexo/dashboard" class="nav-item <?= navActive('/dashboard') ?>">
            <span class="nav-item-icon"><i class="bi bi-house-fill"></i></span><span>Inicio</span>
        </a>
        <a href="/edunexo/admin/usuarios" class="nav-item <?= navActive('/admin/usuarios') ?>">
            <span class="nav-item-icon"><i class="bi bi-people-fill"></i></span><span>Usuarios</span>
        </a>
        <a href="/edunexo/admin/estudiantes" class="nav-item <?= navActive('/admin/estudiantes') ?>">
            <span class="nav-item-icon"><i class="bi bi-person-badge-fill"></i></span><span>Estudiantes</span>
        </a>
        <a href="/edunexo/admin/tutores" class="nav-item <?= navActive('/admin/tutores') ?>">
            <span class="nav-item-icon"><i class="bi bi-house-heart-fill"></i></span><span>Tutores</span>
        </a>

        <span class="nav-section-label">Reportes</span>
        <a href="/edunexo/admin/reportes" class="nav-item <?= navActive('/admin/reportes') ?>">
            <span class="nav-item-icon"><i class="bi bi-bar-chart-fill"></i></span><span>Todos los Reportes</span>
        </a>
        <a href="/edunexo/admin/envios-wa" class="nav-item <?= navActive('/admin/envios-wa') ?>">
            <span class="nav-item-icon"><i class="bi bi-whatsapp"></i></span><span>Envios WhatsApp</span>
        </a>
        <a href="/edunexo/admin/logs" class="nav-item <?= navActive('/admin/logs') ?>">
            <span class="nav-item-icon"><i class="bi bi-shield-exclamation"></i></span><span>Logs de Validacion</span>
        </a>

        <span class="nav-section-label">Academico</span>
        <a href="/edunexo/admin/cursos" class="nav-item <?= navActive('/admin/cursos') ?>">
            <span class="nav-item-icon"><i class="bi bi-building-fill"></i></span><span>Cursos</span>
        </a>
        <a href="/edunexo/admin/materias" class="nav-item <?= navActive('/admin/materias') ?>">
            <span class="nav-item-icon"><i class="bi bi-book-fill"></i></span><span>Materias</span>
        </a>
        <a href="/edunexo/admin/asignaciones" class="nav-item <?= navActive('/admin/asignaciones') ?>">
            <span class="nav-item-icon"><i class="bi bi-link-45deg"></i></span><span>Asignaciones</span>
        </a>
        <a href="/edunexo/admin/tipos_evaluacion" class="nav-item <?= navActive('/admin/tipos_evaluacion') ?>">
            <span class="nav-item-icon"><i class="bi bi-clipboard2-check-fill"></i></span><span>Tipos Eval.</span>
        </a>

        <span class="nav-section-label">Sistema</span>
        <a href="/edunexo/admin/configuracion" class="nav-item <?= navActive('/admin/configuracion') ?>">
            <span class="nav-item-icon"><i class="bi bi-gear-fill"></i></span><span>Configuracion</span>
        </a>

        <form method="POST" action="/edunexo/logout" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="nav-item danger">
                <span class="nav-item-icon"><i class="bi bi-box-arrow-left"></i></span><span>Cerrar Sesion</span>
            </button>
        </form>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= $inicial ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($nombre) ?></div>
                <div class="sidebar-user-role"><span class="role-badge role-admin">admin</span></div>
            </div>
        </div>
    </div>
</aside>

<main class="dash-main">
    <header class="dash-topbar">
        <div class="dash-topbar-title"><?= $pageTitle ?? 'Panel de Administracion' ?></div>
        <div class="dash-topbar-actions">
            <span class="status-dot">Sesion activa</span>
            <div class="topbar-avatar"><?= $inicial ?></div>
        </div>
    </header>
    <div class="dash-content-wrapper">
