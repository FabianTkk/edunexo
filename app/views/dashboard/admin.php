<?php
// app/views/dashboard/admin.php
$pageTitle = 'Panel Admin — EduNexo';
require __DIR__ . '/../layouts/admin_header.php';

$hora    = (int)date('G');
$saludo  = $hora < 12 ? 'Buenos dias' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
?>

<style>
    .welcome-banner.admin-banner {
        background: linear-gradient(135deg, hsl(230,40%,12%), hsl(0,30%,14%));
        border-color: rgba(220,80,80,0.3);
        padding: 2rem;
        border-radius: var(--radius-lg);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        border-style: solid;
        border-width: 1px;
    }
    .welcome-text h1 { margin-bottom: 0.5rem; color: #fff; font-size: 1.8rem; font-weight: 700; }
    .welcome-text p { color: var(--text-secondary); margin-bottom: 0; }
    .welcome-emoji { font-size: 4rem; opacity: 0.8; }

    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-icon.blue { background: rgba(99,120,255,0.15); color: #879fff; }
    .stat-icon.purple { background: rgba(180,99,255,0.15); color: #d19fff; }
    .stat-icon.green { background: rgba(60,200,120,0.15); color: #7dffa9; }
    .stat-icon.red { background: rgba(220,80,80,0.15); color: #ff9a9a; }
    .stat-info { display: flex; flex-direction: column; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
    .stat-label { font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.2rem; }
    .stat-trend { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem; }
    .stat-trend.up { color: #7dffa9; }
    .stat-trend.down { color: #ff9a9a; }

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
        transition: all 0.3s ease;
    }
    .quick-btn:hover {
        background: var(--bg-card-hover);
        border-color: rgba(220,80,80,.4);
        color: #ff9a9a;
        transform: translateY(-2px);
    }
    .quick-btn .qi { font-size: 1.7rem; }

    .data-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
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
    .data-card-link { font-size: .8rem; color: var(--primary-light); text-decoration: none; }
    
    .tbl { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .tbl th {
        color: var(--text-muted); font-weight: 600; padding: .6rem 1rem; text-align: left;
        border-bottom: 1px solid var(--border); font-size: .75rem; text-transform: uppercase;
    }
    .tbl td { padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.04); color: var(--text-secondary); vertical-align: middle; }
    
    .chip { padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; display: inline-block; }
    .chip-admin   { background: rgba(220,80,80,.2); color: #ff9a9a; }
    .chip-docente { background: rgba(99,120,255,.2); color: var(--primary-light); }
    .chip-logrado { background: rgba(60,200,120,.15); color: #7dffa9; }
    .chip-proceso { background: rgba(255,160,60,.15); color: #ffd97d; }
    .chip-no-log  { background: rgba(220,80,80,.15); color: #ff9a9a; }
    .chip-noeval  { background: rgba(255,255,255,.1); color: var(--text-muted); }
    .empty-row td { text-align: center; padding: 2rem; color: var(--text-muted); font-style: italic; }
</style>

<!-- Banner -->
<div class="welcome-banner admin-banner">
    <div class="welcome-text">
        <h1><?= $saludo ?>, <?= htmlspecialchars($nombre) ?>! &#128737;&#65039;</h1>
        <p>Tenes acceso total al sistema. Gestionas usuarios, estudiantes y todos los reportes.</p>
    </div>
    <div class="welcome-emoji">&#9881;&#65039;</div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#128101;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_usuarios'] ?></div>
            <div class="stat-label">Usuarios registrados</div>
            <div class="stat-trend"><?= $stats['total_docentes'] ?> docentes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#127891;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_estudiantes'] ?></div>
            <div class="stat-label">Estudiantes activos</div>
            <div class="stat-trend">en el sistema</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#128202;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_reportes'] ?></div>
            <div class="stat-label">Reportes generados</div>
            <div class="stat-trend"><?= $stats['envios_ok'] ?> enviados por WA</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#128241;</div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['errores_wa'] ?></div>
            <div class="stat-label">Errores WhatsApp</div>
            <div class="stat-trend <?= $stats['errores_wa'] == 0 ? 'up' : 'down' ?>">
                <?= $stats['errores_wa'] == 0 ? 'Todo OK' : 'Requieren atencion' ?>
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
        <a href="/edunexo/admin/usuarios" class="quick-btn"><span class="qi">&#10133;</span>Nuevo usuario</a>
        <a href="/edunexo/admin/estudiantes" class="quick-btn"><span class="qi">&#127891;</span>Nuevo estudiante</a>
        <a href="/edunexo/admin/tutores" class="quick-btn"><span class="qi">&#128106;</span>Nuevo tutor</a>
        <a href="/edunexo/admin/reportes" class="quick-btn"><span class="qi">&#128202;</span>Ver reportes</a>
        <a href="/edunexo/admin/envios-wa" class="quick-btn"><span class="qi">&#128241;</span>Envios WA</a>
        <a href="/edunexo/admin/logs" class="quick-btn"><span class="qi">&#128737;&#65039;</span>Ver logs</a>
    </div>
</div>

<!-- Tabla usuarios recientes -->
<div class="data-card">
    <div class="data-card-header">
        <span class="data-card-title">Usuarios del sistema</span>
        <a href="/edunexo/admin/usuarios" class="data-card-link">Ver todos &rarr;</a>
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
                        <td style="color:var(--text-muted)"><?= (int)$u['id'] ?></td>
                        <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>@<?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="chip chip-<?= $u['rol'] ?>">
                                <?= $u['rol'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
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
        <a href="/edunexo/admin/reportes" class="data-card-link">Ver todos &rarr;</a>
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
                        <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($r['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['curso'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['docente'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="chip <?= $chipCal ?>"><?= htmlspecialchars($r['calificacion_general'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($r['comportamiento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:center"><?= (int)$r['dias_ausente'] ?></td>
                        <td><?= date('d/m/Y', strtotime($r['periodo_semana'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
