<?php
$pageTitle = 'Mensajes';
require __DIR__ . '/../layouts/docente_header.php';
?>

<div class="welcome-banner"><div class="welcome-text"><h1>Mensajes y avisos</h1><p>Publica recordatorios de exámenes, exposiciones y otras novedades.</p></div><div class="welcome-emoji"><i class="bi bi-chat-left-text-fill"></i></div></div>

<div class="data-card">
    <div class="data-card-header"><span class="data-card-title">Nuevo aviso</span></div>
    <?php if (!$asignaciones): ?><p style="color:var(--text-muted)">No tenes materias asignadas para publicar avisos.</p><?php else: ?>
    <form method="POST" action="/edunexo/docente/mensajes" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="col-md-4"><label class="form-label">Materia y curso</label><select name="cmd_id" class="form-select" required><?php foreach ($asignaciones as $asig): ?><option value="<?= (int)$asig['id'] ?>"><?= htmlspecialchars($asig['materia'].' - '.$asig['curso'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Titulo</label><input name="titulo" maxlength="150" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Fecha</label><input type="date" name="fecha_aviso" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Descripcion</label><textarea name="descripcion" class="form-control" rows="3"></textarea></div>
        <div class="col-12"><button class="btn btn-primary" style="width:auto;margin:0"><i class="bi bi-send"></i> Publicar aviso</button></div>
    </form><?php endif; ?>
</div>

<div class="data-card"><div class="data-card-header"><span class="data-card-title">Avisos publicados</span></div><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Fecha</th><th>Titulo</th><th>Materia</th><th>Curso</th></tr></thead><tbody><?php if (!$avisos): ?><tr class="empty-row"><td colspan="4">No hay avisos publicados.</td></tr><?php else: foreach ($avisos as $aviso): ?><tr><td><?= date('d/m/Y', strtotime($aviso['fecha_aviso'])) ?></td><td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($aviso['titulo'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($aviso['materia'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($aviso['curso'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
