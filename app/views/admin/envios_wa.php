<?php $pageTitle = 'Envios WhatsApp'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 1.5rem 0;">Envios WhatsApp</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

    <!-- Filtros -->
    <div class="data-card" style="margin-bottom: 2rem;">
        <form method="GET" action="/edunexo/admin/envios-wa" style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Estado</label>
                <select name="estado" class="form-input no-icon" style="width: auto; min-width: 150px; margin-bottom: 0;">
                    <option value="">Todos</option>
                    <option value="pendiente"  <?= ($_GET['estado']??'')==='pendiente' ?'selected':'' ?>>Pendiente</option>
                    <option value="enviado"    <?= ($_GET['estado']??'')==='enviado'   ?'selected':'' ?>>Enviado</option>
                    <option value="entregado"  <?= ($_GET['estado']??'')==='entregado' ?'selected':'' ?>>Entregado</option>
                    <option value="error"      <?= ($_GET['estado']??'')==='error'     ?'selected':'' ?>>Error</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Desde</label>
                <input type="date" name="desde" class="form-input no-icon" style="width: auto; margin-bottom: 0;" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Hasta</label>
                <input type="date" name="hasta" class="form-input no-icon" style="width: auto; margin-bottom: 0;" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
                <a href="/edunexo/admin/envios-wa" class="btn-secondary" style="width: auto; text-decoration: none; display: flex; align-items: center; justify-content: center; height: 100%;">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="data-card">
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Tutor</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Fecha y hora</th>
                        <th style="text-align: right;">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($envios)): ?>
                    <tr class="empty-row"><td colspan="6">No hay envios para mostrar.</td></tr>
                <?php else: foreach ($envios as $ev): ?>
                    <?php
                    $badgeClass = match($ev['estado']) {
                        'enviado'   => 'chip-logrado',
                        'entregado' => 'chip-docente',
                        'error'     => 'chip-noeval',
                        default     => 'chip-noeval',
                    };
                    // Override colors for some states since default chips don't match exactly
                    $badgeStyle = match($ev['estado']) {
                        'entregado' => 'background-color: rgba(99,120,255,0.15); color: #879fff;',
                        'error'     => 'background-color: rgba(255,60,60,0.15); color: #ff6b6b;',
                        'pendiente' => 'background-color: rgba(255,160,60,0.15); color: #ffd97d;',
                        default     => '',
                    };
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($ev['estudiante']) ?></td>
                        <td><?= htmlspecialchars($ev['tutor'] ?? '—') ?></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($ev['telefono'] ?? '—') ?></td>
                        <td><span class="chip <?= $badgeClass ?>" style="<?= $badgeStyle ?>"><?= ucfirst($ev['estado']) ?></span></td>
                        <td style="color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($ev['fecha_hora_envio'])) ?></td>
                        <td style="text-align: right;">
                            <button class="btn-secondary btn-sm btn-icon" style="color: var(--text-muted); border-color: rgba(255,255,255,0.1);"
                                onclick="verDetalle('<?= addslashes($ev['estudiante']) ?>','<?= addslashes($ev['tutor'] ?? '—') ?>','<?= $ev['periodo_semana'] ?>','<?= $ev['dias_ausente'] ?>','<?= addslashes(str_replace(array("\r", "\n"), '', $ev['comportamiento'])) ?>')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal detalle -->
<div class="modal-overlay" id="detalleModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title">Detalle del Reporte</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="detalleBody"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cerrar</button>
        </div>
    </div>
</div>

<script>
const modalOverlay = document.getElementById('detalleModal');

function openModal() {
    modalOverlay.classList.add('show');
}

function closeModal() {
    modalOverlay.classList.remove('show');
}

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});

function verDetalle(estudiante, tutor, semana, ausencias, comportamiento) {
    document.getElementById('detalleBody').innerHTML = `
        <div style="margin-bottom: 1rem;"><span style="color: var(--text-muted); display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Estudiante</span><strong style="color: var(--text-primary); font-size: 1.1rem;">${estudiante}</strong></div>
        <div style="margin-bottom: 1rem;"><span style="color: var(--text-muted); display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Tutor</span><span style="color: var(--text-primary);">${tutor}</span></div>
        <div style="margin-bottom: 1rem;"><span style="color: var(--text-muted); display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Semana</span><span style="color: var(--text-primary);">${semana}</span></div>
        <div style="margin-bottom: 1rem;"><span style="color: var(--text-muted); display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Dias ausente</span><span style="color: var(--text-primary);">${ausencias}</span></div>
        <div style="margin-bottom: 0;"><span style="color: var(--text-muted); display: block; font-size: 0.85rem; margin-bottom: 0.25rem;">Comportamiento</span><div style="color: var(--text-primary); background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 0.5rem;">${comportamiento}</div></div>
    `;
    openModal();
}
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
