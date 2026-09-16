<?php $pageTitle='Mis estudiantes'; require __DIR__.'/../layouts/docente_header.php'; ?>
<div style="padding-top:1rem">
    <h2>Mis estudiantes y asistencia</h2>
    <form method="GET" class="data-card" style="display:flex;gap:1rem;align-items:end">
        <div class="form-group" style="flex:1"><label class="form-label">Materia</label><select class="form-input no-icon" name="cmd_id"><?php foreach($asignaciones as $a): ?><option value="<?= $a['id'] ?>" <?= $a['id']==$cmdId?'selected':'' ?>><?= htmlspecialchars($a['curso'].' — '.$a['materia']) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label class="form-label">Fecha</label><input class="form-input no-icon" type="date" name="fecha" value="<?= $fecha ?>"></div>
        <button class="btn-primary" style="width:auto">Ver</button>
    </form>
    <?php foreach(['success'=>'alert-success','error'=>'alert-error'] as $k=>$c) if(isset($_SESSION[$k])): ?><div class="alert <?= $c ?>"><?= htmlspecialchars($_SESSION[$k]); unset($_SESSION[$k]) ?></div><?php endif; ?>
    <form method="POST" action="/edunexo/docente/estudiantes/asistencia" class="data-card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="cmd_id" value="<?= $cmdId ?>"><input type="hidden" name="fecha" value="<?= $fecha ?>">
        <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Estudiante</th><th>CI</th><th>Presente</th><th>Falta justificada</th><th>Observación</th></tr></thead><tbody>
        <?php foreach($estudiantes as $e): $presente=!isset($e['presente']) || $e['presente']; $justificada=!$presente && !empty($e['justificada']); ?><tr>
            <td><?= htmlspecialchars($e['nombre_completo']) ?></td><td><?= htmlspecialchars($e['ci']) ?></td>
            <td><input class="js-presente" type="checkbox" name="asistencia[<?= $e['id'] ?>][presente]" data-estudiante="<?= $e['id'] ?>" <?= $presente?'checked':'' ?>></td>
            <td><label style="display:flex;align-items:center;gap:.45rem;white-space:nowrap"><input class="js-justificada" type="checkbox" name="asistencia[<?= $e['id'] ?>][justificada]" data-estudiante="<?= $e['id'] ?>" <?= $justificada?'checked':'' ?> <?= $presente?'disabled':'' ?>> Justificada</label></td>
            <td><input class="form-input no-icon" name="asistencia[<?= $e['id'] ?>][observacion]" value="<?= htmlspecialchars($e['observacion']??'') ?>" placeholder="Ej.: certificado médico o tardanza"></td>
        </tr><?php endforeach ?></tbody></table></div>
        <p style="color:var(--text-muted);font-size:.82rem;margin:1rem 0">La tardanza se registra en observación. Una falta justificada queda como ausencia, pero se informa por separado.</p>
        <button class="btn-primary" style="width:auto">Guardar asistencia</button>
    </form>
</div>
<script>
document.querySelectorAll('.js-presente').forEach(presente => presente.addEventListener('change', () => {
    const justificada = document.querySelector('.js-justificada[data-estudiante="' + presente.dataset.estudiante + '"]');
    justificada.disabled = presente.checked;
    if (presente.checked) justificada.checked = false;
}));
document.querySelectorAll('.js-justificada').forEach(justificada => justificada.addEventListener('change', () => {
    if (justificada.checked) document.querySelector('.js-presente[data-estudiante="' + justificada.dataset.estudiante + '"]').checked = false;
}));
</script>
<?php require __DIR__.'/../layouts/docente_footer.php'; ?>
