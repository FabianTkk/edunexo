<?php $pageTitle = 'Envios WhatsApp'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Envios WhatsApp</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>

    <!-- Filtros -->
    <form method="GET" action="/edunexo/admin/envios-wa" class="card bs-card rounded-4 shadow-sm mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-end">
            <div>
                <label class="form-label text-light small mb-1">Estado</label>
                <select name="estado" class="form-select bg-dark text-light border-secondary" style="width:auto">
                    <option value="">Todos</option>
                    <option value="pendiente"  <?= ($_GET['estado']??'')==='pendiente' ?'selected':'' ?>>Pendiente</option>
                    <option value="enviado"    <?= ($_GET['estado']??'')==='enviado'   ?'selected':'' ?>>Enviado</option>
                    <option value="entregado"  <?= ($_GET['estado']??'')==='entregado' ?'selected':'' ?>>Entregado</option>
                    <option value="error"      <?= ($_GET['estado']??'')==='error'     ?'selected':'' ?>>Error</option>
                </select>
            </div>
            <div>
                <label class="form-label text-light small mb-1">Desde</label>
                <input type="date" name="desde" class="form-control bg-dark text-light border-secondary" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label text-light small mb-1">Hasta</label>
                <input type="date" name="hasta" class="form-control bg-dark text-light border-secondary" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/edunexo/admin/envios-wa" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0">
                    <thead><tr>
                        <th class="ps-4">Estudiante</th>
                        <th>Tutor</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Fecha y hora</th>
                        <th class="text-end pe-4">Detalle</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($envios)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay envios para mostrar.</td></tr>
                    <?php else: foreach ($envios as $ev): ?>
                        <?php
                        $badgeClass = match($ev['estado']) {
                            'enviado'   => 'bg-success bg-opacity-25 text-success',
                            'entregado' => 'bg-info bg-opacity-25 text-info',
                            'error'     => 'bg-danger bg-opacity-25 text-danger',
                            default     => 'bg-warning bg-opacity-25 text-warning',
                        };
                        ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($ev['estudiante']) ?></td>
                            <td><?= htmlspecialchars($ev['tutor'] ?? '—') ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($ev['telefono'] ?? '—') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($ev['estado']) ?></span></td>
                            <td class="text-secondary"><?= date('d/m/Y H:i', strtotime($ev['fecha_hora_envio'])) ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary"
                                    onclick="verDetalle('<?= addslashes($ev['estudiante']) ?>','<?= addslashes($ev['tutor'] ?? '—') ?>','<?= $ev['periodo_semana'] ?>','<?= $ev['dias_ausente'] ?>','<?= addslashes($ev['comportamiento']) ?>')">
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
</div>

<!-- Modal detalle -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:#1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title">Detalle del Reporte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleBody"></div>
        </div>
    </div>
</div>

<script>
const detalleModal = new bootstrap.Modal(document.getElementById('detalleModal'));

function verDetalle(estudiante, tutor, semana, ausencias, comportamiento) {
    document.getElementById('detalleBody').innerHTML = `
        <div class="mb-2"><span class="text-muted">Estudiante:</span> <strong>${estudiante}</strong></div>
        <div class="mb-2"><span class="text-muted">Tutor:</span> ${tutor}</div>
        <div class="mb-2"><span class="text-muted">Semana:</span> ${semana}</div>
        <div class="mb-2"><span class="text-muted">Dias ausente:</span> ${ausencias}</div>
        <div class="mb-2"><span class="text-muted">Comportamiento:</span> ${comportamiento}</div>
    `;
    detalleModal.show();
}
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
