<?php
$pageTitle = 'Asignación de materias';
require __DIR__ . '/../layouts/admin_header.php';
?>
<style>.curso-section { display: none; }</style>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem;">Materias por curso</h2>
        <p style="color: var(--text-muted); margin: 0;">Agregá sólo las materias que se dictan en cada curso y asignales un docente.</p>
    </div>

    <?php foreach (['success' => 'alert-success', 'error' => 'alert-error'] as $tipo => $clase): ?>
        <?php if (isset($_SESSION[$tipo])): ?>
            <div class="alert <?= $clase ?>"><span><?= htmlspecialchars($_SESSION[$tipo]) ?></span></div>
            <?php unset($_SESSION[$tipo]); ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="data-card" style="margin-bottom: 2rem; border: 1px solid rgba(99,120,255,0.3);">
        <label class="form-label" style="font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem;">Seleccionar curso</label>
        <select class="form-input no-icon" style="font-size: 1.1rem; padding: .75rem 1rem;" id="selectCurso" onchange="mostrarCurso(this.value)">
            <option value="">-- Elegir un curso --</option>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nombre'] . ' (' . $curso['turno'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php foreach ($cursos as $curso): ?>
        <?php $asignacionesCurso = $asignaciones[$curso['id']] ?? []; ?>
        <section class="curso-section" id="curso-<?= $curso['id'] ?>">
            <div style="display:grid; grid-template-columns:minmax(280px, 1fr) minmax(420px, 2fr); gap:1.5rem;">
                <div class="data-card" style="height:max-content;">
                    <h3 style="font-size:1.1rem; margin-top:0;">Agregar materia</h3>
                    <form method="POST" action="/edunexo/admin/asignaciones/single">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                        <input type="hidden" name="cmd_id" value="0">
                        <div class="form-group">
                            <label class="form-label">Materia</label>
                            <select name="materia_id" class="form-input no-icon" required>
                                <option value="">-- Seleccionar materia --</option>
                                <?php foreach ($materias as $materia): ?>
                                    <?php if (!isset($asignacionesCurso[$materia['id']])): ?>
                                        <option value="<?= $materia['id'] ?>"><?= htmlspecialchars($materia['nombre']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Docente</label>
                            <select name="docente_id" class="form-input no-icon" required>
                                <option value="">-- Seleccionar docente --</option>
                                <?php foreach ($docentes as $docente): ?>
                                    <option value="<?= $docente['id'] ?>"><?= htmlspecialchars($docente['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;">Agregar materia</button>
                    </form>
                </div>

                <div class="data-card" style="padding:0;">
                    <div style="padding:1rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.1);">
                        <h3 style="font-size:1.1rem; margin:0;">Materias habilitadas</h3>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="tbl">
                            <thead><tr><th>Materia</th><th>Docente</th><th style="text-align:right;">Acciones</th></tr></thead>
                            <tbody>
                                <?php if (empty($asignacionesCurso)): ?>
                                    <tr><td colspan="3" class="empty-row">Este curso aún no tiene materias habilitadas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($asignacionesCurso as $asignacion): ?>
                                        <tr>
                                            <td style="font-weight:600;"><?= htmlspecialchars($asignacion['materia_nombre']) ?></td>
                                            <td>
                                                <form method="POST" action="/edunexo/admin/asignaciones/single" style="display:flex; gap:.5rem; margin:0; align-items:center;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="cmd_id" value="<?= $asignacion['id'] ?>">
                                                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                                    <input type="hidden" name="materia_id" value="<?= $asignacion['materia_id'] ?>">
                                                    <select name="docente_id" class="form-input no-icon" style="padding:.4rem .75rem; margin:0; min-width:180px;" required>
                                                        <?php foreach ($docentes as $docente): ?>
                                                            <option value="<?= $docente['id'] ?>" <?= $docente['id'] == $asignacion['docente_id'] ? 'selected' : '' ?>><?= htmlspecialchars($docente['nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn-secondary btn-sm" title="Guardar docente">Guardar</button>
                                                </form>
                                            </td>
                                            <td style="text-align:right;">
                                                <form method="POST" action="/edunexo/admin/asignaciones/remove" style="margin:0;" onsubmit="return confirm('¿Quitar esta materia del curso? Se conservará el historial académico.')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="cmd_id" value="<?= $asignacion['id'] ?>">
                                                    <button type="submit" class="btn-secondary btn-sm" style="color:#ff8b8b;">Quitar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<script>
function mostrarCurso(cursoId) {
    document.querySelectorAll('.curso-section').forEach(el => el.style.display = 'none');
    const section = cursoId ? document.getElementById('curso-' + cursoId) : null;
    if (section) section.style.display = 'block';
}
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
