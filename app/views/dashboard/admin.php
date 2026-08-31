<?php
// app/views/dashboard/admin.php
// Variables: $nombre, $usuario, $rol, $csrfToken, $stats, $usuarios_recientes, $reportes_recientes
$inicial = strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
$hora    = (int)date('G');
$saludo  = $hora < 12 ? 'Buenos dias' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel Admin &mdash; EduNexo</title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
    <style>
        /* ── Extras admin sobre el design system ── */
        .sidebar { border-top: 3px solid hsl(0,70%,58%); }

        .welcome-banner.admin-banner {
            background: linear-gradient(135deg, hsl(230,40%,12%), hsl(0,30%,14%));
            border-color: rgba(220,80,80,0.3);
        }

        /* Acciones rapidas */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .quick-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: 1.3rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            font-family: var(--font);
            cursor: pointer;
            transition: var(--transition);
        }
        .quick-btn:hover {
            background: var(--bg-card-hover);
            border-color: rgba(220,80,80,.4);
            color: #ff9a9a;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(220,80,80,.12);
        }
        .quick-btn .qi { font-size: 1.7rem; }

        /* Tabla */
        .data-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            animation: card-enter .5s ease both;
        }
        .data-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .data-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .data-card-link {
            font-size: .8rem;
            color: var(--primary-light);
            text-decoration: none;
        }
        .data-card-link:hover { color: var(--accent-light); }

        .tbl { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .tbl th {
            color: var(--text-muted);
            font-weight: 600;
            padding: .6rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .tbl td {
            padding: .75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.04);
            color: var(--text-secondary);
            vertical-align: middle;
        }
        .tbl tr:last-child td { border-bottom: none; }
        .tbl tr:hover td { background: rgba(255,255,255,.03); }

        .chip {
            display: inline-block;
            padding: .2rem .65rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
        }
        .chip-admin   { background: rgba(220,80,80,.2);    color: #ff9a9a; }
        .chip-docente { background: rgba(99,120,255,.2);   color: var(--primary-light); }
        .chip-logrado { background: rgba(60,200,120,.15);  color: #7dffa9; }
        .chip-proceso { background: rgba(255,160,60,.15);  color: #ffd97d; }
        .chip-no-log  { background: rgba(220,80,80,.15);   color: #ff9a9a; }
        .chip-noeval  { background: rgba(255,255,255,.1);  color: var(--text-muted); }

        .empty-row td {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Stat icon rojo */
        .stat-icon.red { background: rgba(220,80,80,.15); }
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
            <span class="nav-section-label">Administracion</span>
            <a href="/edunexo/dashboard" class="nav-item active">
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
                <span class="nav-item-icon">&#128241;</span><span>Envios WhatsApp</span>
            </a>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#128737;&#65039;</span><span>Logs de Validacion</span>
            </a>

            <span class="nav-section-label">Académico (Nuevo)</span>
            <a href="/edunexo/admin/cursos" class="nav-item">
                <span class="nav-item-icon">&#127979;</span><span>Cursos</span>
            </a>
            <a href="/edunexo/admin/materias" class="nav-item">
                <span class="nav-item-icon">&#128218;</span><span>Materias</span>
            </a>
            <a href="/edunexo/admin/asignaciones" class="nav-item">
                <span class="nav-item-icon">&#128279;</span><span>Asignaciones</span>
            </a>
            <a href="/edunexo/admin/tipos_evaluacion" class="nav-item">
                <span class="nav-item-icon">&#128221;</span><span>Tipos Eval.</span>
            </a>

            <span class="nav-section-label">Sistema</span>
            <a href="#" class="nav-item">
                <span class="nav-item-icon">&#9881;&#65039;</span><span>Configuracion</span>
            </a>

            <form method="POST" action="/edunexo/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="nav-item danger">
                    <span class="nav-item-icon">&#128682;</span><span>Cerrar Sesion</span>
                </button>
            </form>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?php echo $inicial; ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo $nombre; ?></div>
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
            <div class="dash-topbar-title">Panel de Administracion</div>
            <div class="dash-topbar-actions">
                <span class="status-dot">Sesion activa</span>
                <div class="topbar-avatar"><?php echo $inicial; ?></div>
            </div>
        </header>

        <div class="dash-content">

            <!-- Banner -->
            <div class="welcome-banner admin-banner">
                <div class="welcome-text">
                    <h1><?php echo $saludo; ?>, <?php echo $nombre; ?>! &#128737;&#65039;</h1>
                    <p>Tenes acceso total al sistema. Gestionas usuarios, estudiantes y todos los reportes.</p>
                </div>
                <div class="welcome-emoji">&#9881;&#65039;</div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" style="animation-delay:.05s">
                    <div class="stat-icon blue">&#128101;</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_usuarios']; ?></div>
                        <div class="stat-label">Usuarios registrados</div>
                        <div class="stat-trend"><?php echo $stats['total_docentes']; ?> docentes</div>
                    </div>
                </div>
                <div class="stat-card" style="animation-delay:.1s">
                    <div class="stat-icon purple">&#127891;</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_estudiantes']; ?></div>
                        <div class="stat-label">Estudiantes activos</div>
                        <div class="stat-trend">en el sistema</div>
                    </div>
                </div>
                <div class="stat-card" style="animation-delay:.15s">
                    <div class="stat-icon green">&#128202;</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_reportes']; ?></div>
                        <div class="stat-label">Reportes generados</div>
                        <div class="stat-trend"><?php echo $stats['envios_ok']; ?> enviados por WA</div>
                    </div>
                </div>
                <div class="stat-card" style="animation-delay:.2s">
                    <div class="stat-icon red">&#128241;</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['errores_wa']; ?></div>
                        <div class="stat-label">Errores WhatsApp</div>
                        <div class="stat-trend <?php echo $stats['errores_wa'] == 0 ? 'up' : 'down'; ?>">
                            <?php echo $stats['errores_wa'] == 0 ? 'Todo OK' : 'Requieren atencion'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones rapidas -->
            <div class="data-card">
                <div class="data-card-header">
                    <span class="data-card-title">Acciones Rapidas</span>
                </div>
                <div class="quick-grid">
                    <a href="#" class="quick-btn"><span class="qi">&#10133;</span>Nuevo usuario</a>
                    <a href="#" class="quick-btn"><span class="qi">&#127891;</span>Nuevo estudiante</a>
                    <a href="#" class="quick-btn"><span class="qi">&#128106;</span>Nuevo tutor</a>
                    <a href="#" class="quick-btn"><span class="qi">&#128202;</span>Ver reportes</a>
                    <a href="#" class="quick-btn"><span class="qi">&#128241;</span>Envios WA</a>
                    <a href="#" class="quick-btn"><span class="qi">&#128737;&#65039;</span>Ver logs</a>
                </div>
            </div>

            <!-- Tabla usuarios recientes -->
            <div class="data-card">
                <div class="data-card-header">
                    <span class="data-card-title">Usuarios del sistema</span>
                    <a href="#" class="data-card-link">Ver todos &rarr;</a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios_recientes)): ?>
                                <tr class="empty-row"><td colspan="5">No hay usuarios registrados aun.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios_recientes as $u): ?>
                                <tr>
                                    <td style="color:var(--text-muted)"><?php echo (int)$u['id']; ?></td>
                                    <td style="color:var(--text-primary);font-weight:600"><?php echo htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>@<?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="chip chip-<?php echo $u['rol']; ?>">
                                            <?php echo $u['rol']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla reportes recientes -->
            <div class="data-card">
                <div class="data-card-header">
                    <span class="data-card-title">Reportes recientes</span>
                    <a href="#" class="data-card-link">Ver todos &rarr;</a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Docente</th>
                                <th>Calificacion</th>
                                <th>Comportamiento</th>
                                <th>Ausencias</th>
                                <th>Semana</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportes_recientes)): ?>
                                <tr class="empty-row"><td colspan="7">No hay reportes cargados aun.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reportes_recientes as $r):
                                    $chipCal = match($r['calificacion_general']) {
                                        'Logrado'        => 'chip-logrado',
                                        'En Proceso'     => 'chip-proceso',
                                        'Aun no logrado' => 'chip-no-log',
                                        default          => 'chip-noeval',
                                    };
                                ?>
                                <tr>
                                    <td style="color:var(--text-primary);font-weight:600"><?php echo htmlspecialchars($r['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($r['curso'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($r['docente'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="chip <?php echo $chipCal; ?>"><?php echo htmlspecialchars($r['calificacion_general'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars($r['comportamiento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="text-align:center"><?php echo (int)$r['dias_ausente']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($r['periodo_semana'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /dash-content -->
    </main>
</div>
</body>
</html>
