<?php $pageTitle='Mis estudiantes'; require __DIR__.'/../layouts/docente_header.php'; ?>
<style>
    .as-badge { display:inline-block; padding:.2rem .65rem; border-radius:999px; font-size:.75rem; font-weight:700; }
    .as-ok    { background:rgba(60,200,120,.15); color:#7dffa9; }
    .as-warn  { background:rgba(255,160,60,.15); color:#ffd97d; }
    .as-estado { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:1rem; font-size:.85rem; color:var(--text-muted); }
    .as-tools { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; margin-bottom:1rem; }
    .as-tools .form-input { max-width:240px; }
    .as-count { margin-left:auto; color:var(--text-muted); font-size:.85rem; }
</style>
<div style="padding-top:1rem">
    <h2>Mis estudiantes</h2>
    <p style="color:var(--text-muted)">Tomá asistencia y revisá las faltas del mes de cada estudiante.</p>

    <form method="GET" class="data-card" style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:220px"><label class="form-label">Materia</label><select class="form-input no-icon" name="cmd_id"><?php foreach($asignaciones as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)$a['id']===$cmdId?'selected':'' ?>><?= htmlspecialchars($a['curso'].' — '.$a['materia']) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label class="form-label">Fecha</label><input class="form-input no-icon" type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" max="<?= htmlspecialchars($hoy) ?>"></div>
        <button class="btn-primary" style="width:auto">Ver</button>
    </form>

    <?php foreach(['success'=>'alert-success','error'=>'alert-error'] as $k=>$c) if(isset($_SESSION[$k])): ?><div class="alert <?= $c ?>"><?= htmlspecialchars($_SESSION[$k]); unset($_SESSION[$k]) ?></div><?php endif; ?>

    <form method="POST" action="/edunexo/docente/estudiantes/asistencia" class="data-card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="cmd_id" value="<?= (int)$cmdId ?>"><input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">

        <div class="as-estado">
            <?php if ($esFinDeSemana): ?>
                <span class="as-badge as-warn">Fin de semana</span> La asistencia se registra de lunes a viernes. Elegí otra fecha.
            <?php elseif ($totalEstudiantes === 0): ?>
                <span class="as-badge as-warn">Sin estudiantes</span> No hay estudiantes activos en este curso.
            <?php elseif ($conRegistro === 0): ?>
                <span class="as-badge as-warn">Sin registrar</span> Todavía no se guardó la asistencia de este día. Si no la guardás, el reporte semanal indicará que no hay registro.
            <?php elseif ($conRegistro < $totalEstudiantes): ?>
                <span class="as-badge as-warn">Registro parcial</span> <?= (int)$conRegistro ?> de <?= (int)$totalEstudiantes ?> estudiantes tienen asistencia guardada. Revisá y guardá para completar.
            <?php else: ?>
                <span class="as-badge as-ok">Asistencia registrada</span> Podés modificarla y volver a guardar.
            <?php endif; ?>
        </div>

        <?php if (!$esFinDeSemana && $totalEstudiantes > 0): ?>
        <div class="as-tools">
            <button type="button" class="btn-secondary btn-sm" id="btnTodosPresentes">Todos presentes</button>
            <button type="button" class="btn-secondary btn-sm" id="btnTodosAusentes">Todos ausentes</button>
            <input class="form-input no-icon" type="search" id="buscarEstudiante" placeholder="Buscar estudiante">
            <span class="as-count" id="contadorAsistencia"></span>
        </div>
        <?php endif; ?>

        <div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Estudiante</th><th>CI</th><th>Presente</th><th>Falta justificada</th><th>Observación</th><th>Faltas del mes</th></tr></thead><tbody>
        <?php if ($totalEstudiantes === 0): ?><tr class="empty-row"><td colspan="6">No hay estudiantes activos en este curso.</td></tr><?php endif; ?>
        <?php foreach($estudiantes as $e):
            $id = (int)$e['id'];
            $presente = $e['presente'] === null || (int)$e['presente'] === 1;
            $justificada = !$presente && !empty($e['justificada']);
        ?><tr class="js-fila" data-nombre="<?= htmlspecialchars(mb_strtolower($e['nombre_completo'])) ?>">
            <td><?= htmlspecialchars($e['nombre_completo']) ?></td><td><?= htmlspecialchars($e['ci']) ?></td>
            <td><input class="js-presente" type="checkbox" name="asistencia[<?= $id ?>][presente]" data-estudiante="<?= $id ?>" <?= $presente?'checked':'' ?>></td>
            <td><label style="display:flex;align-items:center;gap:.45rem;white-space:nowrap"><input class="js-justificada" type="checkbox" name="asistencia[<?= $id ?>][justificada]" data-estudiante="<?= $id ?>" <?= $justificada?'checked':'' ?> <?= $presente?'disabled':'' ?>> Justificada</label></td>
            <td><input class="form-input no-icon" name="asistencia[<?= $id ?>][observacion]" maxlength="500" value="<?= htmlspecialchars($e['observacion']??'') ?>" placeholder="Ej.: certificado médico o tardanza"></td>
            <td style="white-space:nowrap"><?= (int)$e['faltas_mes'] ?><?php if ((int)$e['justificadas_mes'] > 0): ?> <span style="color:var(--text-muted);font-size:.78rem">(+<?= (int)$e['justificadas_mes'] ?> just.)</span><?php endif; ?></td>
        </tr><?php endforeach ?></tbody></table></div>

        <p style="color:var(--text-muted);font-size:.82rem;margin:1rem 0">La tardanza se registra en observación. Una falta justificada queda como ausencia, pero se informa por separado. "Faltas del mes" cuenta las no justificadas de esta materia.</p>

        <?php if ($materiasCurso > 1): ?>
        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;font-size:.9rem"><input type="checkbox" name="aplicar_todas" value="1"> Aplicar esta asistencia a mis <?= (int)$materiasCurso ?> materias de este curso</label>
        <?php endif; ?>

        <button class="btn-primary" style="width:auto" <?= ($esFinDeSemana || $totalEstudiantes === 0) ? 'disabled' : '' ?>>Guardar asistencia</button>
    </form>
</div>
<script>
(function () {
    const presentes  = document.querySelectorAll('.js-presente');
    const contador   = document.getElementById('contadorAsistencia');
    const buscador   = document.getElementById('buscarEstudiante');

    function justificadaDe(id) {
        return document.querySelector('.js-justificada[data-estudiante="' + id + '"]');
    }
    function actualizarContador() {
        if (!contador) return;
        let p = 0;
        presentes.forEach(c => { if (c.checked) p++; });
        contador.textContent = 'Presentes: ' + p + ' · Ausentes: ' + (presentes.length - p);
    }
    function marcarVisibles(valor) {
        presentes.forEach(presente => {
            if (presente.closest('tr').hidden) return;
            presente.checked = valor;
            const j = justificadaDe(presente.dataset.estudiante);
            j.disabled = valor;
            if (valor) j.checked = false;
        });
        actualizarContador();
    }

    presentes.forEach(presente => presente.addEventListener('change', () => {
        const j = justificadaDe(presente.dataset.estudiante);
        j.disabled = presente.checked;
        if (presente.checked) j.checked = false;
        actualizarContador();
    }));
    document.querySelectorAll('.js-justificada').forEach(j => j.addEventListener('change', () => {
        if (j.checked) {
            document.querySelector('.js-presente[data-estudiante="' + j.dataset.estudiante + '"]').checked = false;
            actualizarContador();
        }
    }));

    const btnP = document.getElementById('btnTodosPresentes');
    const btnA = document.getElementById('btnTodosAusentes');
    if (btnP) btnP.addEventListener('click', () => marcarVisibles(true));
    if (btnA) btnA.addEventListener('click', () => marcarVisibles(false));
    if (buscador) buscador.addEventListener('input', () => {
        const q = buscador.value.trim().toLowerCase();
        document.querySelectorAll('.js-fila').forEach(f => { f.hidden = q !== '' && !f.dataset.nombre.includes(q); });
    });

    actualizarContador();
})();
</script>
<?php require __DIR__.'/../layouts/docente_footer.php'; ?>
