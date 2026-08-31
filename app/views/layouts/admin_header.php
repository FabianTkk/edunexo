<?php
// app/views/layouts/admin_header.php
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /edunexo/login');
    exit;
}
$nombre = $_SESSION['nombre'];
$inicial = strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
$csrfToken = \App\Helpers\SecurityHelper::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Admin — EduNexo' ?></title>
    
    <!-- CSS Personalizado del Dashboard -->
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
    
    <!-- Bootstrap y Iconos para el contenido -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* Ajustes para compatibilidad entre el layout custom y Bootstrap */
        body { background-color: var(--bg-deep); color: var(--text-primary); }
        .sidebar { border-top: 3px solid hsl(0,70%,58%); }
        .dash-content-wrapper { padding: 2rem; flex: 1; }
        
        /* Reset de algunos estilos de BS que pisan el dashboard */
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
<div class="dash-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">&#127891;</div>
            <span class="sidebar-brand-name">EduNexo</span>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Administración</span>
            <a href="/edunexo/dashboard" class="nav-item">
                <span class="nav-item-icon">&#127968;</span><span>Inicio</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128101;</span><span>Usuarios</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#127891;</span><span>Estudiantes</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128106;</span><span>Tutores</span>
            </a>

            <span class="nav-section-label">Reportes</span>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128202;</span><span>Todos los Reportes</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128241;</span><span>Envíos WhatsApp</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128737;&#65039;</span><span>Logs de Validación</span>
            </a>

            <span class="nav-section-label">Académico (Nuevo)</span>
            <a href="/edunexo/admin/cursos" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/cursos') !== false ? 'active' : '' ?>">
                <span class="nav-item-icon">&#127979;</span><span>Cursos</span>
            </a>
            <a href="/edunexo/admin/materias" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/materias') !== false ? 'active' : '' ?>">
                <span class="nav-item-icon">&#128218;</span><span>Materias</span>
            </a>
            <a href="/edunexo/admin/asignaciones" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/asignaciones') !== false ? 'active' : '' ?>">
                <span class="nav-item-icon">&#128279;</span><span>Asignaciones</span>
            </a>
            <a href="/edunexo/admin/tipos_evaluacion" class="nav-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/tipos_evaluacion') !== false ? 'active' : '' ?>">
                <span class="nav-item-icon">&#128221;</span><span>Tipos Eval.</span>
            </a>

            <span class="nav-section-label">Sistema</span>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#9881;&#65039;</span><span>Configuración</span>
            </a>

            <form method="POST" action="/edunexo/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="nav-item danger">
                    <span class="nav-item-icon">&#128682;</span><span>Cerrar Sesión</span>
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

    <!-- MAIN -->
    <main class="dash-main">
        <header class="dash-topbar">
            <div class="dash-topbar-title"><?= $pageTitle ?? 'Panel de Administración' ?></div>
            <div class="dash-topbar-actions">
                <span class="status-dot">Sesión activa</span>
                <div class="topbar-avatar"><?= $inicial ?></div>
            </div>
        </header>
        
        <div class="dash-content-wrapper">
