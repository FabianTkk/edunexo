<?php $pageTitle = 'Envíos WhatsApp'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <!-- Encabezado con estado y acciones rápidas -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.3rem 0;">Envíos de Reportes por WhatsApp</h2>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
                Visualiza reportes semanales (anteriores, actuales y pendientes), supervisa su entrega y despacha mensajes a tutores.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <!-- Indicador en vivo de Evolution API -->
            <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.8rem; border-radius: 9999px; font-size: 0.82rem; font-weight: 600; <?= ($estadoEvolution['state'] ?? '') === 'open' ? 'background: rgba(34,197,94,0.15); color: #22c55e;' : ((!empty($estadoEvolution['online'])) ? 'background: rgba(234,179,8,0.15); color: #eab308;' : 'background: rgba(239,68,68,0.15); color: #ef4444;') ?>">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: currentColor;"></span>
                Evolution API: <?= htmlspecialchars(strtoupper($estadoEvolution['state'] ?? 'OFFLINE')) ?>
            </span>

            <!-- Botón Probar Envío Directo -->
            <button type="button" class="btn-secondary" style="margin: 0; display: inline-flex; align-items: center; gap: 0.4rem;" onclick="abrirModalTest()">
                <i class="bi bi-chat-dots-fill"></i> Probar Envío Directo
            </button>

            <!-- Botón Procesar Pendientes -->
            <?php if (!empty($conteoPendientes)): ?>
                <form method="POST" action="/edunexo/admin/envios-wa/procesar-pendientes" style="margin: 0;" onsubmit="return confirm('¿Enviar todos los reportes pendientes por WhatsApp ahora mismo?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" class="btn-primary" style="margin: 0; background-color: #22c55e; border-color: #22c55e; color: #fff; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <i class="bi bi-send-fill"></i> Procesar Pendientes (<?= $conteoPendientes ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas de sesión -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Filtros Mejorados -->
    <div class="data-card" style="margin-bottom: 1.5rem;">
        <form method="GET" action="/edunexo/admin/envios-wa" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 180px;">
                <label class="form-label" style="font-size: 0.85rem;">Buscar</label>
                <input type="text" name="buscar" class="form-input no-icon" style="margin-bottom: 0;"
                       placeholder="Estudiante, CI o tutor..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0; width: 140px;">
                <label class="form-label" style="font-size: 0.85rem;">Estado</label>
                <select name="estado" class="form-input no-icon" style="margin-bottom: 0;">
                    <option value="">Todos</option>
                    <option value="pendiente"  <?= ($_GET['estado']??'')==='pendiente' ?'selected':'' ?>>Pendiente</option>
                    <option value="enviado"    <?= ($_GET['estado']??'')==='enviado'   ?'selected':'' ?>>Enviado</option>
                    <option value="entregado"  <?= ($_GET['estado']??'')==='entregado' ?'selected':'' ?>>Entregado</option>
                    <option value="error"      <?= ($_GET['estado']??'')==='error'     ?'selected':'' ?>>Error</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0; width: 160px;">
                <label class="form-label" style="font-size: 0.85rem;">Desde (Semana o Fecha)</label>
                <input type="date" name="desde" class="form-input no-icon" style="margin-bottom: 0;" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0; width: 160px;">
                <label class="form-label" style="font-size: 0.85rem;">Hasta (Semana o Fecha)</label>
                <input type="date" name="hasta" class="form-input no-icon" style="margin-bottom: 0;" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <a href="/edunexo/admin/envios-wa" class="btn-secondary" style="width: auto; text-decoration: none; display: flex; align-items: center; justify-content: center; height: 100%;">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Envíos -->
    <div class="data-card">
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Semana</th>
                        <th>Estudiante</th>
                        <th>Tutor / Destino</th>
                        <th>Docente</th>
                        <th>Estado</th>
                        <th>Fecha de Envío / Registro</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($envios)): ?>
                    <tr class="empty-row"><td colspan="7">No se encontraron reportes o envíos con los filtros aplicados.</td></tr>
                <?php else: foreach ($envios as $ev): ?>
                    <?php
                    $badgeClass = match($ev['estado']) {
                        'enviado'   => 'chip-logrado',
                        'entregado' => 'chip-docente',
                        'error'     => 'chip-noeval',
                        default     => 'chip-noeval',
                    };
                    $badgeStyle = match($ev['estado']) {
                        'entregado' => 'background-color: rgba(99,120,255,0.15); color: #879fff;',
                        'error'     => 'background-color: rgba(255,60,60,0.15); color: #ff6b6b;',
                        'pendiente' => 'background-color: rgba(255,160,60,0.15); color: #ffd97d;',
                        default     => '',
                    };
                    ?>
                    <tr>
                        <!-- Semana del reporte -->
                        <td>
                            <span style="font-weight: 600; font-size: 0.88rem; color: #a5b4fc; background: rgba(99,102,241,0.1); padding: 0.2rem 0.5rem; border-radius: 6px; white-space: nowrap;">
                                <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y', strtotime($ev['periodo_semana'])) ?>
                            </span>
                        </td>

                        <!-- Estudiante -->
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($ev['estudiante']) ?></div>
                            <div style="color: var(--text-muted); font-size: 0.8rem;">
                                CI: <?= htmlspecialchars($ev['ci'] ?? '—') ?> <?= !empty($ev['curso']) ? '&bull; ' . htmlspecialchars($ev['curso']) : '' ?>
                            </div>
                        </td>

                        <!-- Tutor / Teléfono -->
                        <td>
                            <div><?= htmlspecialchars($ev['tutor'] ?? 'Sin tutor asignado') ?></div>
                            <div style="color: var(--text-muted); font-family: monospace; font-size: 0.82rem;">
                                <?= !empty($ev['destinatario_telefono']) ? htmlspecialchars($ev['destinatario_telefono']) : '<span style="color: #ef4444;">Sin teléfono</span>' ?>
                            </div>
                        </td>

                        <!-- Docente -->
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            <?= htmlspecialchars($ev['docente_nombre'] ?? '—') ?>
                        </td>

                        <!-- Estado -->
                        <td>
                            <span class="chip <?= $badgeClass ?>" style="<?= $badgeStyle ?>">
                                <?= ucfirst($ev['estado']) ?>
                            </span>
                        </td>

                        <!-- Fecha de Envío o Creación -->
                        <td style="color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;">
                            <?php if ($ev['estado'] === 'enviado'): ?>
                                <span style="color: #22c55e;"><i class="bi bi-check2-all me-1"></i> <?= date('d/m/Y H:i', strtotime($ev['fecha_hora_envio'])) ?></span>
                            <?php elseif ($ev['estado'] === 'pendiente'): ?>
                                <span style="color: #ffd97d;"><i class="bi bi-clock-history me-1"></i> Pendiente</span>
                                <div style="font-size: 0.75rem; color: var(--text-muted); opacity: 0.8;">
                                    Creado: <?= !empty($ev['fecha_creacion_reporte']) ? date('d/m/Y', strtotime($ev['fecha_creacion_reporte'])) : date('d/m/Y', strtotime($ev['periodo_semana'])) ?>
                                </div>
                            <?php elseif ($ev['estado'] === 'error'): ?>
                                <span style="color: #ff6b6b;"><i class="bi bi-exclamation-triangle me-1"></i> Falló <?= date('d/m/Y H:i', strtotime($ev['fecha_hora_envio'])) ?></span>
                            <?php else: ?>
                                <?= !empty($ev['fecha_hora_envio']) ? date('d/m/Y H:i', strtotime($ev['fecha_hora_envio'])) : '—' ?>
                            <?php endif; ?>
                        </td>

                        <!-- Acciones -->
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">
                                <!-- Botón Envío Manual / Reintento -->
                                <?php if (!empty($ev['destinatario_telefono'])): ?>
                                    <form method="POST" action="/edunexo/admin/envios-wa/enviar" style="margin: 0;" onsubmit="return confirm('¿Enviar este reporte por WhatsApp a <?= htmlspecialchars($ev['destinatario_telefono'] ?? '') ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="envio_id" value="<?= (int)$ev['id'] ?>">
                                        <button type="submit" class="btn-secondary btn-sm" style="color: #22c55e; border-color: rgba(34,197,94,0.3); padding: 0.3rem 0.6rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Enviar o reintentar envío por WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                            <span><?= ($ev['estado'] === 'pendiente' || $ev['estado'] === 'error') ? 'Enviar' : 'Reenviar' ?></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-secondary btn-sm" disabled style="opacity: 0.4; padding: 0.3rem 0.6rem;" title="No se puede enviar: tutor sin teléfono registrado">
                                        <i class="bi bi-whatsapp"></i> Sin tel.
                                    </button>
                                <?php endif; ?>

                                <!-- Ver detalle del reporte -->
                                <button class="btn-secondary btn-sm btn-icon" style="color: var(--text-muted); border-color: rgba(255,255,255,0.1);"
                                    title="Ver Detalle del Reporte"
                                    onclick="verDetalle(<?= \App\Helpers\SecurityHelper::jsArg($ev['estudiante']) ?>,<?= \App\Helpers\SecurityHelper::jsArg($ev['tutor'] ?? '—') ?>,<?= \App\Helpers\SecurityHelper::jsArg(date('d/m/Y', strtotime($ev['periodo_semana']))) ?>,<?= (int)$ev['dias_ausente'] ?>,<?= \App\Helpers\SecurityHelper::jsArg($ev['calificacion_general'] ?? 'Logrado') ?>,<?= (int)($ev['tareas_incompletas'] ?? 0) ?>,<?= \App\Helpers\SecurityHelper::jsArg($ev['comportamiento'] ?? '') ?>,<?= \App\Helpers\SecurityHelper::jsArg($ev['incidentes_disciplinarios'] ?? '') ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalle del Reporte -->
<div class="modal-overlay" id="detalleModal">
    <div class="custom-modal" style="max-width: 550px;">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-file-earmark-text-fill me-2" style="color: var(--primary);"></i> Detalle del Reporte Semanal</div>
            <button class="modal-close" onclick="closeModal('detalleModal')">&times;</button>
        </div>
        <div class="modal-body" id="detalleBody"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('detalleModal')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Prueba de Envío Directo (Para pruebas del Administrador) -->
<div class="modal-overlay" id="modalTestDirecto">
    <div class="custom-modal" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-whatsapp" style="color: #22c55e; margin-right: 0.4rem;"></i> Probar Envío WhatsApp Directo</div>
            <button class="modal-close" onclick="closeModal('modalTestDirecto')">&times;</button>
        </div>
        <form method="POST" action="/edunexo/admin/envios-wa/test-directo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-body">
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0; margin-bottom: 1rem;">
                    Esta herramienta te permite enviar un mensaje instantáneo a tu propio número para verificar que Evolution API esté despachando correctamente hacia WhatsApp.
                </p>

                <div class="form-group">
                    <label class="form-label">Número de Teléfono (con código de país)</label>
                    <input type="text" name="telefono_prueba" class="form-input no-icon" required
                           placeholder="Ej: 595981234567" value="595">
                    <small style="color: var(--text-muted); font-size: 0.78rem; display: block; margin-top: 0.3rem;">
                        Paraguay: 595 + 9 dígitos (sin el 0 ni espacios). Ej: 595971366877
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Mensaje de Prueba</label>
                    <textarea name="mensaje_prueba" class="form-input no-icon" rows="3" required
                              style="resize: vertical; font-family: inherit;">EduNexo: Mensaje de prueba de conexión con Evolution API v2 exitoso ✅</textarea>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn-secondary" onclick="closeModal('modalTestDirecto')">Cancelar</button>
                <button type="submit" class="btn-primary" style="background-color: #22c55e; border-color: #22c55e;">
                    <i class="bi bi-send-fill" style="margin-right: 0.3rem;"></i> Enviar Mensaje
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

function abrirModalTest() {
    openModal('modalTestDirecto');
}

// Cerrar modales con clic afuera
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('show');
    });
});

// Escapa texto para insertarlo en innerHTML (los datos llegan crudos, sin escapar).
function escHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
}

function verDetalle(estudiante, tutor, semana, ausencias, calificacion, tareas, comportamiento, incidentes) {
    [estudiante, tutor, semana, ausencias, calificacion, tareas, comportamiento, incidentes] =
        [estudiante, tutor, semana, ausencias, calificacion, tareas, comportamiento, incidentes].map(escHtml);
    document.getElementById('detalleBody').innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Estudiante</span>
                <strong style="color: var(--text-primary); font-size: 1rem;">${estudiante}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Tutor</span>
                <span style="color: var(--text-primary); font-size: 0.95rem;">${tutor}</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; background: rgba(255,255,255,0.03); padding: 0.75rem; border-radius: 8px;">
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Semana</span>
                <strong style="color: #a5b4fc;">${semana}</strong>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Calificación General</span>
                <span style="color: var(--text-primary); font-weight: 600;">${calificacion}</span>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Días Ausente</span>
                <span style="color: var(--text-primary);">${ausencias} día(s)</span>
            </div>
            <div>
                <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Tareas Incompletas</span>
                <span style="color: var(--text-primary);">${tareas} tarea(s)</span>
            </div>
        </div>

        <div style="margin-bottom: 0.75rem;">
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; margin-bottom: 0.25rem;">Comportamiento</span>
            <div style="color: var(--text-primary); background: rgba(0,0,0,0.25); padding: 0.6rem 0.8rem; border-radius: 6px; font-size: 0.9rem;">
                ${comportamiento || 'Bueno'}
            </div>
        </div>

        ${incidentes ? `
        <div style="margin-bottom: 0;">
            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; margin-bottom: 0.25rem;">Incidentes / Observaciones</span>
            <div style="color: var(--text-primary); background: rgba(0,0,0,0.25); padding: 0.6rem 0.8rem; border-radius: 6px; font-size: 0.875rem; white-space: pre-wrap;">
                ${incidentes}
            </div>
        </div>
        ` : ''}
    `;
    openModal('detalleModal');
}
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
