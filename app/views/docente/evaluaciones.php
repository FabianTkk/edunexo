<?php
// app/views/docente/evaluaciones.php
$pageTitle = 'Evaluaciones — ' . htmlspecialchars($info['materia'] ?? '');
require __DIR__ . '/../layouts/docente_header.php';
?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <!-- Volver -->
    <div style="margin-bottom: 1rem;">
        <a href="/edunexo/docente/materias" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.8rem; font-size: 0.875rem;">
            <i class="bi bi-arrow-left"></i> Volver a Materias
        </a>
    </div>

    <!-- Encabezado -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.3rem 0;">Evaluaciones de la Materia</h2>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem;">
                <strong style="color: var(--text-primary);"><?= htmlspecialchars($info['materia'] ?? '') ?></strong> &mdash; <?= htmlspecialchars($info['curso'] ?? '') ?> (Turno <?= htmlspecialchars($info['turno'] ?? 'Mañana') ?>)
            </p>
        </div>

        <div>
            <button type="button" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.4rem; margin: 0;" onclick="resetForm()">
                <i class="bi bi-plus-lg"></i> Nueva Evaluación
            </button>
        </div>
    </div>

    <!-- Mensajes de alerta -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tabla de Evaluaciones -->
    <div class="data-card">
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Puntaje Máx.</th>
                        <th>Descripción</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluaciones)): ?>
                        <tr class="empty-row"><td colspan="6">No hay evaluaciones creadas para esta materia todavía. Haz clic en "Nueva Evaluación" para registrar una.</td></tr>
                    <?php else: ?>
                        <?php foreach ($evaluaciones as $eval): ?>
                            <tr>
                                <td style="color: var(--text-muted); font-size: 0.88rem; white-space: nowrap;">
                                    <i class="bi bi-calendar-event me-1"></i> <?= date('d/m/Y', strtotime($eval['fecha'])) ?>
                                </td>
                                <td style="font-weight: 600; color: var(--text-primary);">
                                    <?= htmlspecialchars($eval['titulo']) ?>
                                </td>
                                <td>
                                    <span class="chip chip-docente">
                                        <?= htmlspecialchars($eval['tipo_nombre']) ?> (<?= (float)$eval['peso_porcentaje'] ?>%)
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #22c55e;">
                                    <?= htmlspecialchars($eval['puntaje_maximo']) ?> pts
                                </td>
                                <td style="color: var(--text-muted); font-size: 0.85rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= !empty($eval['descripcion']) ? htmlspecialchars($eval['descripcion']) : '<span style="opacity: 0.4;">—</span>' ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">
                                        <!-- Calificar notas -->
                                        <a href="/edunexo/docente/notas?eval_id=<?= $eval['id'] ?>" class="btn-primary btn-sm" style="background-color: #22c55e; border-color: #22c55e; color: #fff; padding: 0.25rem 0.6rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Cargar calificaciones para esta evaluación">
                                            <i class="bi bi-journal-check"></i> Calificar
                                        </a>

                                        <!-- Editar -->
                                        <button type="button" class="btn-secondary btn-sm btn-icon" title="Editar evaluación"
                                                data-id="<?= (int)$eval['id'] ?>"
                                                data-tipo="<?= (int)$eval['tipo_evaluacion_id'] ?>"
                                                data-titulo="<?= htmlspecialchars($eval['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-fecha="<?= htmlspecialchars($eval['fecha'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-puntaje="<?= htmlspecialchars($eval['puntaje_maximo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-descripcion="<?= htmlspecialchars($eval['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="editEvaluacion(this)">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Eliminar -->
                                        <form method="POST" action="/edunexo/docente/evaluaciones/delete" style="margin: 0; display: inline;"
                                              onsubmit="return confirm('¿Estás seguro de eliminar esta evaluación? Si ya tiene notas cargadas, podrían perderse.')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="cmd_id" value="<?= (int)$cmd_id ?>">
                                            <input type="hidden" name="id" value="<?= (int)$eval['id'] ?>">
                                            <button type="submit" class="btn-secondary btn-sm btn-icon" style="color: #ef4444; border-color: rgba(239,68,68,0.3);" title="Eliminar evaluación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nativo EduNexo para Crear / Editar Evaluación -->
<div class="modal-overlay" id="evaluacionModal">
    <div class="custom-modal" style="max-width: 550px;">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nueva Evaluación</div>
            <button class="modal-close" type="button" onclick="closeEvaluacionModal()">&times;</button>
        </div>
        <form id="evaluacionForm" method="POST" action="/edunexo/docente/evaluaciones/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="evaluacionId" value="">
                <input type="hidden" name="cmd_id" value="<?= (int)$cmd_id ?>">

                <div class="form-group">
                    <label class="form-label">Tipo de Evaluación <span style="color: #ef4444;">*</span></label>
                    <select class="form-input no-icon" name="tipo_evaluacion_id" id="evaluacionTipo" required>
                        <option value="">-- Seleccionar Tipo --</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= (int)$t['id'] ?>">
                                <?= htmlspecialchars($t['nombre']) ?> (<?= (float)$t['peso_porcentaje'] ?>% del promedio)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Título de la Evaluación <span style="color: #ef4444;">*</span></label>
                    <input type="text" class="form-input no-icon" name="titulo" id="evaluacionTitulo" required placeholder="Ej: Trabajo Práctico 1: Álgebra">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Fecha de Evaluación <span style="color: #ef4444;">*</span></label>
                        <input type="date" class="form-input no-icon" name="fecha" id="evaluacionFecha" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Puntaje Máximo <span style="color: #ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0.1" max="1000" class="form-input no-icon" name="puntaje_maximo" id="evaluacionPuntaje" required value="10">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Descripción u Observaciones (Opcional)</label>
                    <textarea class="form-input no-icon" name="descripcion" id="evaluacionDescripcion" rows="2" placeholder="Detalles sobre temas evaluados o criterios de corrección..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" class="btn-secondary" onclick="closeEvaluacionModal()">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnGuardarEvaluacion" style="width: auto; margin-top: 0;">
                    <i class="bi bi-floppy-fill me-1"></i> Guardar Evaluación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEvaluacionModal() {
    const el = document.getElementById('evaluacionModal');
    if (el) el.classList.add('show');
}

function closeEvaluacionModal() {
    const el = document.getElementById('evaluacionModal');
    if (el) el.classList.remove('show');
}

// Cerrar al hacer clic en el fondo oscuro
document.getElementById('evaluacionModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEvaluacionModal();
});

function resetForm() {
    const form = document.getElementById('evaluacionForm');
    form.action = '/edunexo/docente/evaluaciones/store';
    document.getElementById('modalTitle').textContent = 'Nueva Evaluación';
    document.getElementById('evaluacionId').value          = '';
    document.getElementById('evaluacionTipo').value        = '';
    document.getElementById('evaluacionTitulo').value      = '';
    document.getElementById('evaluacionFecha').value       = '<?= date('Y-m-d') ?>';
    document.getElementById('evaluacionPuntaje').value     = '10';
    document.getElementById('evaluacionDescripcion').value = '';
    openEvaluacionModal();
}

function editEvaluacion(btn) {
    const d = btn.dataset;
    const form = document.getElementById('evaluacionForm');
    form.action = '/edunexo/docente/evaluaciones/update';
    document.getElementById('modalTitle').textContent = 'Editar Evaluación';
    document.getElementById('evaluacionId').value          = d.id;
    document.getElementById('evaluacionTipo').value        = d.tipo;
    document.getElementById('evaluacionTitulo').value      = d.titulo;
    document.getElementById('evaluacionFecha').value       = d.fecha;
    document.getElementById('evaluacionPuntaje').value     = d.puntaje;
    document.getElementById('evaluacionDescripcion').value = d.descripcion;
    openEvaluacionModal();
}
</script>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
