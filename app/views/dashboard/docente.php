<?php
// app/views/dashboard/docente.php
// Variables: $nombre, $usuario, $rol, $csrfToken, $stats,
//            $estudiantes_ausencias, $mis_reportes, $envios_recientes
$pageTitle = 'Panel Docente — EduNexo';
require __DIR__ . '/../layouts/docente_header.php';

$hora    = (int)date('G');
$saludo  = $hora < 12 ? 'Buenos dias' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
?>

<style>
    /* ── Extras docente sobre el design system ── */

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
        border-color: rgba(99,120,255,.4);
        color: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(99,120,255,.12);
    }
    .quick-btn .qi { font-size: 1.7rem; }

    /* Tarjetas de datos */
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

    /* Tabla */
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

    /* Chips */
    .chip {
        display: inline-block;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
    }
    .chip-logrado { background: rgba(60,200,120,.15);  color: #7dffa9; }
    .chip-proceso { background: rgba(255,160,60,.15);  color: #ffd97d; }
    .chip-no-log  { background: rgba(220,80,80,.15);   color: #ff9a9a; }
    .chip-noeval  { background: rgba(255,255,255,.1);  color: var(--text-muted); }
    .chip-exc     { background: rgba(60,200,120,.15);  color: #7dffa9; }
    .chip-bue     { background: rgba(99,120,255,.15);  color: var(--primary-light); }
    .chip-reg     { background: rgba(255,160,60,.15);  color: #ffd97d; }
    .chip-aten    { background: rgba(220,80,80,.15);   color: #ff9a9a; }

    /* Estado WA */
    .chip-enviado   { background: rgba(60,200,120,.15);  color: #7dffa9; }
    .chip-entregado { background: rgba(99,120,255,.15);  color: var(--primary-light); }
    .chip-pendiente { background: rgba(255,160,60,.15);  color: #ffd97d; }
    .chip-error     { background: rgba(220,80,80,.15);   color: #ff9a9a; }

    /* Barra de ausencias */
    .ausencia-bar {
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    .bar-wrap {
        flex: 1;
        height: 6px;
        background: rgba(255,255,255,.08);
        border-radius: 999px;
        overflow: hidden;
    }
    .bar-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--primary);
    }
    .bar-fill.warn  { background: var(--warning); }
    .bar-fill.danger { background: var(--error); }

    .empty-row td {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
        font-style: italic;
    }

    /* 2 columnas en info grid docente */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
</style>

<!-- Banner -->
<div class="welcome-banner">
    <div class="welcome-text">
        <h1><?php echo $saludo; ?>, <?php echo $nombre; ?>! &#128218;</h1>
        <p>Aqui podes cargar reportes, revisar ausencias y hacer seguimiento de tus estudiantes.</p>
    </div>
    <div class="welcome-emoji">&#128203;</div>
</div>

<!-- Stats docente -->
<div class="stats-grid">
    <div class="stat-card" style="animation-delay:.05s">
        <div class="stat-icon blue">&#128101;</div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $stats['estudiantes_asignados']; ?></div>
            <div class="stat-label">Mis estudiantes</div>
            <div class="stat-trend">en tus cursos asignados</div>
        </div>
    </div>
    <div class="stat-card" style="animation-delay:.1s">
        <div class="stat-icon purple">&#128202;</div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $stats['mis_reportes']; ?></div>
            <div class="stat-label">Reportes cargados</div>
            <div class="stat-trend">por vos</div>
        </div>
    </div>
    <div class="stat-card" style="animation-delay:.15s">
        <div class="stat-icon green">&#128241;</div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $stats['envios_ok']; ?></div>
            <div class="stat-label">Envios WA exitosos</div>
            <div class="stat-trend <?php echo $stats['envios_ok'] > 0 ? 'up' : ''; ?>">
                de tus reportes
            </div>
        </div>
    </div>
    <div class="stat-card" style="animation-delay:.2s">
        <div class="stat-icon orange">&#127891;</div>
        <div class="stat-info">
            <div class="stat-value"><?php echo $stats['mis_estudiantes']; ?></div>
            <div class="stat-label">Con seguimiento</div>
            <div class="stat-trend">estudiantes evaluados</div>
        </div>
    </div>
</div>

<!-- Acciones rapidas -->
<div class="data-card">
    <div class="data-card-header">
        <span class="data-card-title">Acciones Rapidas</span>
    </div>
    <div class="quick-grid">
        <a href="/edunexo/docente/materias" class="quick-btn"><span class="qi"><i class="bi bi-book-fill"></i></span>Mis materias</a>
        <a href="/edunexo/docente/estudiantes" class="quick-btn"><span class="qi"><i class="bi bi-people-fill"></i></span>Tomar asistencia</a>
        <a href="/edunexo/docente/reportes" class="quick-btn"><span class="qi"><i class="bi bi-file-earmark-plus-fill"></i></span>Nuevo reporte</a>
        <a href="/edunexo/docente/envios-wa" class="quick-btn"><span class="qi"><i class="bi bi-whatsapp"></i></span>Envios WhatsApp</a>
    </div>
</div>

<!-- 2 columnas: ausencias + envios WA -->
<div class="two-col">

    <!-- Estudiantes con mas ausencias -->
    <div class="data-card" style="margin-bottom:0">
        <div class="data-card-header">
            <span class="data-card-title">&#128680; Mas ausencias</span>
            <a href="/edunexo/docente/estudiantes" class="data-card-link">Ver todos &rarr;</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Dias ausente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estudiantes_ausencias)): ?>
                        <tr class="empty-row"><td colspan="3">Sin datos de ausencias aun.</td></tr>
                    <?php else: ?>
                        <?php
                        $maxAus = max(array_column($estudiantes_ausencias, 'total_ausencias')) ?: 1;
                        foreach ($estudiantes_ausencias as $ea):
                            $pct = min(100, round(($ea['total_ausencias'] / $maxAus) * 100));
                            $cls = $pct >= 70 ? 'danger' : ($pct >= 40 ? 'warn' : '');
                        ?>
                        <tr>
                            <td style="color:var(--text-primary);font-weight:600"><?php echo htmlspecialchars($ea['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($ea['curso'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="ausencia-bar">
                                    <div class="bar-wrap">
                                        <div class="bar-fill <?php echo $cls; ?>" style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                    <span style="font-size:.8rem;font-weight:700;color:var(--text-primary);min-width:20px"><?php echo (int)$ea['total_ausencias']; ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ultimos envios WA -->
    <div class="data-card" style="margin-bottom:0">
        <div class="data-card-header">
            <span class="data-card-title">&#128241; Ultimos envios WA</span>
            <a href="/edunexo/docente/envios-wa" class="data-card-link">Ver todos &rarr;</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($envios_recientes)): ?>
                        <tr class="empty-row"><td colspan="4">Sin envios registrados aun.</td></tr>
                    <?php else: ?>
                        <?php foreach ($envios_recientes as $wa):
                            $chipWa = 'chip-' . $wa['estado'];
                        ?>
                        <tr>
                            <td style="color:var(--text-primary);font-weight:600"><?php echo htmlspecialchars($wa['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($wa['destinatario_telefono'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="chip <?php echo $chipWa; ?>"><?php echo $wa['estado']; ?></span></td>
                            <td><?php echo date('d/m H:i', strtotime($wa['fecha_hora_envio'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /two-col -->

<!-- Mis ultimos reportes -->
<div class="data-card" style="margin-top:1.5rem">
    <div class="data-card-header">
        <span class="data-card-title">Mis ultimos reportes</span>
        <a href="/edunexo/docente/reportes" class="data-card-link">Ver todos &rarr;</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th>Calificacion</th>
                    <th>Comportamiento</th>
                    <th>Ausencias</th>
                    <th>Semana</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mis_reportes)): ?>
                    <tr class="empty-row"><td colspan="6">Aun no cargaste reportes. Cuando lo hagas apareceran aqui.</td></tr>
                <?php else: ?>
                    <?php foreach ($mis_reportes as $r):
                        $chipCal = match($r['calificacion_general']) {
                            'Logrado'        => 'chip-logrado',
                            'En Proceso'     => 'chip-proceso',
                            'Aun no logrado' => 'chip-no-log',
                            default          => 'chip-noeval',
                        };
                        $chipCom = match($r['comportamiento']) {
                            'Excelente'         => 'chip-exc',
                            'Bueno'             => 'chip-bue',
                            'Regular'           => 'chip-reg',
                            'Requiere Atencion' => 'chip-aten',
                            default             => 'chip-noeval',
                        };
                    ?>
                    <tr>
                        <td style="color:var(--text-primary);font-weight:600"><?php echo htmlspecialchars($r['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($r['curso'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="chip <?php echo $chipCal; ?>"><?php echo htmlspecialchars($r['calificacion_general'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="chip <?php echo $chipCom; ?>"><?php echo htmlspecialchars($r['comportamiento'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td style="text-align:center"><?php echo (int)$r['dias_ausente']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($r['periodo_semana'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
