<?php
$pageTitle = 'Calendario';
require __DIR__ . '/../layouts/docente_header.php';
?>

<div class="welcome-banner">
    <div class="welcome-text"><h1>Calendario académico</h1><p>Consulta las evaluaciones y avisos de tus materias.</p></div>
    <div class="welcome-emoji"><i class="bi bi-calendar3"></i></div>
</div>

<div class="data-card">
    <div class="data-card-header"><span class="data-card-title">Proximos eventos</span></div>
    <div style="overflow-x:auto"><table class="tbl">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Titulo</th><th>Materia</th></tr></thead>
        <tbody><?php if (!$eventos): ?><tr class="empty-row"><td colspan="4">No hay eventos registrados.</td></tr><?php else: foreach ($eventos as $evento): ?><tr>
            <td><?= date('d/m/Y', strtotime($evento['fecha'])) ?></td><td><?= htmlspecialchars($evento['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($evento['materia'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr><?php endforeach; endif; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
