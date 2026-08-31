<?php
// app/views/docente/evaluaciones.php
$pageTitle = 'Evaluaciones — ' . htmlspecialchars($info['materia'] ?? '');
require __DIR__ . '/../layouts/docente_header.php';
?>

<div class="container py-5">

    <a href="/edunexo/docente/materias" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Volver a Materias
    </a>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Evaluaciones</h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($info['curso'] ?? '') ?> &mdash; <?= htmlspecialchars($info['materia'] ?? '') ?>
            </p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#evaluacionModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i> Nueva Evaluacion
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Titulo</th>
                            <th>Tipo</th>
                            <th>Pts. Max.</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($evaluaciones)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay evaluaciones creadas aun.</td></tr>
                        <?php else: ?>
                            <?php foreach ($evaluaciones as $eval): ?>
                                <tr>
                                    <td class="ps-4 text-secondary"><?= date('d/m/Y', strtotime($eval['fecha'])) ?></td>
                                    <td class="fw-semibold text-light"><?= htmlspecialchars($eval['titulo']) ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-25 text-info"><?= htmlspecialchars($eval['tipo_nombre']) ?></span></td>
                                    <td><?= htmlspecialchars($eval['puntaje_maximo']) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="/edunexo/docente/notas?eval_id=<?= $eval['id'] ?>" class="btn btn-sm btn-success" title="Calificar">
                                                <i class="bi bi-journal-check"></i> Calificar
                                            </a>
                                            <button class="btn btn-sm btn-outline-info"
                                                    onclick="editEvaluacion(<?= $eval['id'] ?>, <?= $eval['tipo_evaluacion_id'] ?>, '<?= htmlspecialchars(addslashes($eval['titulo'])) ?>', '<?= $eval['fecha'] ?>', <?= $eval['puntaje_maximo'] ?>, '<?= htmlspecialchars(addslashes($eval['descripcion'] ?? '')) ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="/edunexo/docente/evaluaciones/delete" class="m-0 p-0 d-inline"
                                                  onsubmit="return confirm('Eliminar esta evaluacion borrara todas las notas asociadas. Continuar?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="cmd_id" value="<?= $cmd_id ?>">
                                                <input type="hidden" name="id" value="<?= $eval['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
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
</div>

<!-- Modal Nueva/Editar Evaluacion -->
<div class="modal fade" id="evaluacionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nueva Evaluacion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="evaluacionForm" method="POST" action="/edunexo/docente/evaluaciones/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="evaluacionId" value="">
                    <input type="hidden" name="cmd_id" value="<?= $cmd_id ?>">

                    <div class="mb-3">
                        <label class="form-label text-light">Tipo de Evaluacion</label>
                        <select class="form-select bg-dark text-light border-secondary" name="tipo_evaluacion_id" id="evaluacionTipo" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= $t['peso_porcentaje'] ?>%)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light">Titulo</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="titulo" id="evaluacionTitulo" required placeholder="Ej: Trabajo Practico 1">
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-light">Fecha</label>
                            <input type="date" class="form-control bg-dark text-light border-secondary" name="fecha" id="evaluacionFecha" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Puntaje Max.</label>
                            <input type="number" step="0.01" class="form-control bg-dark text-light border-secondary" name="puntaje_maximo" id="evaluacionPuntaje" required value="10">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light">Descripcion (Opcional)</label>
                        <textarea class="form-control bg-dark text-light border-secondary" name="descripcion" id="evaluacionDescripcion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = new bootstrap.Modal(document.getElementById('evaluacionModal'));
    const form  = document.getElementById('evaluacionForm');
    const title = document.getElementById('modalTitle');

    function resetForm() {
        form.action = '/edunexo/docente/evaluaciones/store';
        title.textContent = 'Nueva Evaluacion';
        document.getElementById('evaluacionId').value          = '';
        document.getElementById('evaluacionTipo').value        = '';
        document.getElementById('evaluacionTitulo').value      = '';
        document.getElementById('evaluacionFecha').value       = '<?= date('Y-m-d') ?>';
        document.getElementById('evaluacionPuntaje').value     = '10';
        document.getElementById('evaluacionDescripcion').value = '';
    }

    function editEvaluacion(id, tipo_id, titulo, fecha, puntaje, descripcion) {
        form.action = '/edunexo/docente/evaluaciones/update';
        title.textContent = 'Editar Evaluacion';
        document.getElementById('evaluacionId').value          = id;
        document.getElementById('evaluacionTipo').value        = tipo_id;
        document.getElementById('evaluacionTitulo').value      = titulo;
        document.getElementById('evaluacionFecha').value       = fecha;
        document.getElementById('evaluacionPuntaje').value     = puntaje;
        document.getElementById('evaluacionDescripcion').value = descripcion;
        modal.show();
    }
</script>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
