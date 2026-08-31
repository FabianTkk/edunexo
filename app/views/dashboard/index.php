<?php
// app/views/dashboard/index.php
// Variables disponibles: $nombre, $usuario, $rol, $csrfToken (desde DashboardController)

// Inicial del nombre para el avatar
$inicial = strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
$rolClass = 'role-' . $rol;

// Hora de bienvenida
$hora = (int)date('G');
$saludo = $hora < 12 ? '¡Buenos días' : ($hora < 19 ? '¡Buenas tardes' : '¡Buenas noches');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panel de control — EduNexo">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard — EduNexo</title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
</head>
<body>
<div class="dash-layout">

    <!-- ══════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menú principal">

        <!-- Marca -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">🎓</div>
            <span class="sidebar-brand-name">EduNexo</span>
        </div>

        <!-- Navegación -->
        <nav class="sidebar-nav">
            <span class="nav-section-label">Principal</span>

            <a href="/edunexo/dashboard" class="nav-item active" id="nav-dashboard">
                <span class="nav-item-icon">🏠</span>
                <span>Inicio</span>
            </a>

            <a href="#" class="nav-item" id="nav-cursos">
                <span class="nav-item-icon">📚</span>
                <span>Cursos</span>
            </a>

            <a href="#" class="nav-item" id="nav-alumnos">
                <span class="nav-item-icon">👥</span>
                <span>Alumnos</span>
            </a>

            <a href="#" class="nav-item" id="nav-asistencia">
                <span class="nav-item-icon">✅</span>
                <span>Asistencia</span>
            </a>

            <span class="nav-section-label">Herramientas</span>

            <a href="#" class="nav-item" id="nav-calendario">
                <span class="nav-item-icon">📅</span>
                <span>Calendario</span>
            </a>

            <a href="#" class="nav-item" id="nav-reportes">
                <span class="nav-item-icon">📊</span>
                <span>Reportes</span>
            </a>

            <a href="#" class="nav-item" id="nav-mensajes">
                <span class="nav-item-icon">💬</span>
                <span>Mensajes</span>
            </a>

            <span class="nav-section-label">Cuenta</span>

            <a href="#" class="nav-item" id="nav-perfil">
                <span class="nav-item-icon">⚙️</span>
                <span>Configuración</span>
            </a>

            <!-- Logout -->
            <form method="POST" action="/edunexo/logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="nav-item danger" id="btn-logout">
                    <span class="nav-item-icon">🚪</span>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </nav>

        <!-- Usuario en footer del sidebar -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?php echo $inicial; ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo $nombre; ?></div>
                    <div class="sidebar-user-role">
                        <span class="role-badge <?php echo $rolClass; ?>">
                            <?php echo $rol; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </aside>

    <!-- ══════════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ══════════════════════════════════════════════ -->
    <main class="dash-main">

        <!-- Topbar -->
        <header class="dash-topbar">
            <div class="dash-topbar-title">Panel de Control</div>
            <div class="dash-topbar-actions">
                <span class="status-dot">Sesión activa</span>
                <div class="topbar-avatar" title="<?php echo $nombre; ?>"><?php echo $inicial; ?></div>
            </div>
        </header>

        <!-- Contenido -->
        <div class="dash-content">

            <!-- Banner de bienvenida -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1><?php echo $saludo; ?>, <?php echo $nombre; ?>! 👋</h1>
                    <p>
                        <?php if ($rol === 'admin'): ?>
                            Tenés acceso total al sistema. Todo bajo control.
                        <?php elseif ($rol === 'docente'): ?>
                            Aquí podés gestionar tus cursos, alumnos y asistencia.
                        <?php else: ?>
                            Bienvenido/a a tu espacio de aprendizaje en EduNexo.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="welcome-emoji">
                    <?php echo $rol === 'admin' ? '🛡️' : ($rol === 'docente' ? '📖' : '🎒'); ?>
                </div>
            </div>

            <!-- Cards de estadísticas -->
            <div class="stats-grid">
                <div class="stat-card" style="animation-delay:.05s">
                    <div class="stat-icon blue">📚</div>
                    <div class="stat-info">
                        <div class="stat-value">12</div>
                        <div class="stat-label">Cursos activos</div>
                        <div class="stat-trend up">↑ 2 este mes</div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay:.1s">
                    <div class="stat-icon purple">👥</div>
                    <div class="stat-info">
                        <div class="stat-value">248</div>
                        <div class="stat-label">Alumnos inscriptos</div>
                        <div class="stat-trend up">↑ 18 este mes</div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay:.15s">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="stat-value">94%</div>
                        <div class="stat-label">Tasa de asistencia</div>
                        <div class="stat-trend up">↑ 3% vs. anterior</div>
                    </div>
                </div>

                <div class="stat-card" style="animation-delay:.2s">
                    <div class="stat-icon orange">📅</div>
                    <div class="stat-info">
                        <div class="stat-value">6</div>
                        <div class="stat-label">Eventos próximos</div>
                        <div class="stat-trend down">↓ 1 cancelado</div>
                    </div>
                </div>
            </div>

            <!-- Fila info: Actividad + Sesión -->
            <div class="info-grid">

                <!-- Actividad reciente -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="info-card-title">Actividad Reciente</span>
                        <span class="info-card-badge">Hoy</span>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--primary)"></div>
                            <span>Se registró el ingreso al sistema</span>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--success)"></div>
                            <span>Sesión iniciada correctamente</span>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--accent)"></div>
                            <span>Dashboard cargado</span>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--warning)"></div>
                            <span>Próximamente: historial de actividades detallado</span>
                        </div>
                    </div>
                </div>

                <!-- Info de sesión -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="info-card-title">Tu Sesión</span>
                        <span class="status-dot">En línea</span>
                    </div>
                    <div class="session-info">
                        <div class="session-row">
                            <span class="session-key">Usuario</span>
                            <span class="session-val">@<?php echo $nombre; ?></span>
                        </div>
                        <div class="session-row">
                            <span class="session-key">Rol</span>
                            <span class="session-val">
                                <span class="role-badge <?php echo $rolClass; ?>"><?php echo $rol; ?></span>
                            </span>
                        </div>
                        <div class="session-row">
                            <span class="session-key">Inicio</span>
                            <span class="session-val" id="session-time">—</span>
                        </div>
                        <div class="session-row">
                            <span class="session-key">Expira en</span>
                            <span class="session-val" id="session-countdown">30:00</span>
                        </div>
                        <div class="session-row">
                            <span class="session-key">Estado</span>
                            <span class="session-val status-dot">Activa</span>
                        </div>
                    </div>
                </div>

            </div><!-- /info-grid -->

        </div><!-- /dash-content -->
    </main>

</div><!-- /dash-layout -->

<script>
    // ── Hora de inicio de sesión ───────────────────────────────────────
    const timeEl = document.getElementById('session-time');
    if (timeEl) {
        const now = new Date();
        timeEl.textContent = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    }

    // ── Countdown de sesión (30 min) ──────────────────────────────────
    let remaining = 30 * 60; // segundos
    const countdownEl = document.getElementById('session-countdown');

    function updateCountdown() {
        if (remaining <= 0) {
            countdownEl.textContent = 'Expirada';
            countdownEl.style.color = 'var(--error)';
            return;
        }
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;
        if (remaining <= 300) {
            countdownEl.style.color = 'var(--warning)';
        }
        remaining--;
        setTimeout(updateCountdown, 1000);
    }

    updateCountdown();

    // ── Resetear countdown con actividad ─────────────────────────────
    ['mousemove', 'keydown', 'click'].forEach(evt => {
        document.addEventListener(evt, () => { remaining = 30 * 60; }, { passive: true });
    });

    // ── Highlight nav item activo ─────────────────────────────────────
    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.tagName === 'A' && item.href !== '#') {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        }
    });
</script>
</body>
</html>
