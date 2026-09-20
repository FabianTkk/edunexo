<?php
// app/views/layouts/docente_header.php
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header('Location: /edunexo/login');
    exit;
}
$nombre    = $_SESSION['nombre'];
$inicial   = htmlspecialchars(strtoupper(mb_substr($nombre, 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8');
$csrfToken = \App\Helpers\SecurityHelper::generateCsrfToken();
$uri       = $_SERVER['REQUEST_URI'];

function navActiveDocente(string $path): string {
    global $uri;
    return strpos($uri, $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel Docente — EduNexo') ?></title>

    <link rel="stylesheet" href="/edunexo/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body { background-color: var(--bg-deep); color: var(--text-primary); }
        .dash-content-wrapper { padding: 2rem; flex: 1; }
        #page-content { animation: pageIn .22s ease both; }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
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

        a { text-decoration: none; }
        .btn { border-radius: var(--bs-border-radius); }

        /* Estilos de las tablas BS integradas al modo oscuro custom */
        .card.bs-card { background-color: #1a1d2d; border: 1px solid rgba(255,255,255,0.1); }
        .table.bs-table { --bs-table-bg: transparent; --bs-table-color: #e2e8f0; }
        .table.bs-table th { border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; }
        .table.bs-table td { border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    </style>
</head>
<body>

<div id="page-loader"></div>

<div class="dash-layout">

    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <span class="sidebar-brand-name">EduNexo</span>
        </div>

        <nav class="sidebar-nav" id="sidebar-nav">
            <span class="nav-section-label">Mi Panel</span>
            <a href="/edunexo/dashboard" class="nav-item <?= navActiveDocente('/dashboard') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-house-fill"></i></span><span>Inicio</span>
            </a>
            <a href="/edunexo/docente/materias" class="nav-item <?= (navActiveDocente('/docente/materias') || navActiveDocente('/docente/evaluaciones') || navActiveDocente('/docente/notas')) ? 'active' : '' ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-book-fill"></i></span><span>Mis Materias</span>
            </a>
            <a href="/edunexo/docente/estudiantes" class="nav-item <?= navActiveDocente('/docente/estudiantes') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-people-fill"></i></span><span>Mis Estudiantes</span>
            </a>
            <a href="/edunexo/docente/reportes" class="nav-item <?= navActiveDocente('/docente/reportes') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-bar-chart-fill"></i></span><span>Mis Reportes</span>
            </a>
            <a href="/edunexo/docente/envios-wa" class="nav-item <?= navActiveDocente('/docente/envios-wa') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-whatsapp"></i></span><span>Envios WhatsApp</span>
            </a>

            <span class="nav-section-label">Herramientas</span>
            <a href="/edunexo/docente/calendario" class="nav-item <?= navActiveDocente('/docente/calendario') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-calendar3"></i></span><span>Calendario</span>
            </a>
            <a href="/edunexo/docente/mensajes" class="nav-item <?= navActiveDocente('/docente/mensajes') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-chat-left-text-fill"></i></span><span>Mensajes</span>
            </a>

            <span class="nav-section-label">Cuenta</span>
            <a href="/edunexo/docente/perfil" class="nav-item <?= navActiveDocente('/docente/perfil') ?>" data-spa>
                <span class="nav-item-icon"><i class="bi bi-person-circle"></i></span><span>Mi Perfil</span>
            </a>

            <form method="POST" action="/edunexo/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="nav-item danger">
                    <span class="nav-item-icon"><i class="bi bi-box-arrow-left"></i></span><span>Cerrar Sesión</span>
                </button>
            </form>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= $inicial ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($nombre) ?></div>
                    <div class="sidebar-user-role">
                        <span class="role-badge role-docente">docente</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="dash-main">
        <header class="dash-topbar" id="dash-topbar">
            <div class="dash-topbar-title" id="topbar-title">
                <?= htmlspecialchars($pageTitle ?? 'Panel Docente') ?>
            </div>
            <div class="dash-topbar-actions">
                <span class="status-dot">Sesión activa</span>
                <div class="topbar-avatar"><?= $inicial ?></div>
            </div>
        </header>

        <div class="dash-content-wrapper" id="page-content">
