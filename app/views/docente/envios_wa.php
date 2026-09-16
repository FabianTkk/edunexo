<?php
$pageTitle = 'Envios WhatsApp';
require __DIR__ . '/../layouts/docente_header.php';
?>

<div class="welcome-banner">
    <div class="welcome-text">
        <h1>Envios de WhatsApp</h1>
        <p>Revisa los reportes y preparalos para su envío a los tutores.</p>
    </div>
    <div class="welcome-emoji"><i class="bi bi-whatsapp"></i></div>
</div>

<?php if (!empty($_SESSION['success']) || !empty($_SESSION['error'])): ?>
    <div class="alert <?= !empty($_SESSION['error']) ? 'alert-error' : 'alert-success' ?>">
        <?= htmlspecialchars($_SESSION['error'] ?? $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success'], $_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="data-card">
    <div class="data-card-header">
        <div>
            <div class="data-card-title">Reportes listos para preparar</div>
            <small style="color:var(--text-muted)"><?= count($pendientes) ?> destinatario(s) pendiente(s)</small>
        </div>
        <?php if ($pendientes): ?>
            <form method="POST" action="/edunexo/docente/envios-wa">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary" style="width:auto;margin:0"><i class="bi bi-send-fill"></i> Preparar envios</button>
            </form>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto"><table class="tbl">
        <thead><tr><th>Estudiante</th><th>Curso</th><th>Tutor</th><th>Semana</th><th>Telefono</th></tr></thead>
        <tbody>
        <?php if (!$pendientes): ?><tr class="empty-row"><td colspan="5">No hay reportes pendientes de preparar.</td></tr>
        <?php else: foreach ($pendientes as $item): ?><tr>
            <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($item['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['curso'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['tutor'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= date('d/m/Y', strtotime($item['periodo_semana'])) ?></td>
            <td><?= htmlspecialchars($item['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr><?php endforeach; endif; ?>
        </tbody>
    </table></div>
</div>

<div class="data-card">
    <div class="data-card-header"><span class="data-card-title">Historial de envios</span></div>
    <div style="overflow-x:auto"><table class="tbl">
        <thead><tr><th>Estudiante</th><th>Curso</th><th>Semana</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php if (!$historial): ?><tr class="empty-row"><td colspan="5">Aun no preparaste envios.</td></tr>
        <?php else: foreach ($historial as $item): ?><tr>
            <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($item['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['curso'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= date('d/m/Y', strtotime($item['periodo_semana'])) ?></td>
            <td><span class="chip chip-<?= htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($item['estado']), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= date('d/m/Y H:i', strtotime($item['fecha_hora_envio'])) ?></td>
        </tr><?php endforeach; endif; ?>
        </tbody>
    </table></div>
</div>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
