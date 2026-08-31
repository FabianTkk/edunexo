<?php
// app/views/docente/notas.php
$pageTitle = 'Calificar: ' . htmlspecialchars($evaluacion['titulo'] ?? '');
require __DIR__ . '/../layouts/docente_header.php';
?>

<style>
    .sticky-save {
        position: sticky;
        bottom: 0;
        background: rgba(26,29,45,0.97);
        backdrop-filter: blur(10px);
        padding: 1rem 2rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        z-index: 10;
    }
</style>

<div class="container py-5">

    <a href="/edunexo/docente/evaluaciones?cmd_id=<?= $evaluacion['curso_materia_docente_id'] ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Volver a Evaluaciones
    </a>

    <div class="mb-4">
        <h2 class="mb-1 fw-bold">Calificar: <?= htmlspecialchars($evaluacion['titulo']) ?></h2>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($evaluacion['curso_nombre']) ?> &mdash; <?= htmlspecialchars($evaluacion['materia_nombre']) ?>
            &nbsp;<span class="badge bg-primary bg-opacity-25 text-info">Max: <?= htmlspecialchars($evaluacion['puntaje_maximo']) ?> pts</span>
        </p>
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

    <form method="POST" action="/edunexo/docente/notas/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="evaluacion_id" value="<?= $evaluacion['id'] ?>">
        <input type="hidden" name="cmd_id" value="<?= $evaluacion['curso_materia_docente_id'] ?>">

        <div class="card shadow-sm mb-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Estudiante</th>
                                <th>CI</th>
                                <th style="width:160px;">Puntaje Obtenido</th>
                                <th class="pe-4">Observacion (Opcional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($estudiantes)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No hay estudiantes activos en este curso.</td></tr>
                            <?php else: ?>
                                <?php foreach ($estudiantes as $est): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-light"><?= htmlspecialchars($est['nombre_completo']) ?></td>
                                        <td class="text-secondary"><?= htmlspecialchars($est['ci']) ?></td>
                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   max="<?= $evaluacion['puntaje_maximo'] ?>"
                                                   class="form-control bg-dark text-light border-secondary"
                                                   name="notas[<?= $est['estudiante_id'] ?>][puntaje]"
                                                   value="<?= htmlspecialchars($est['puntaje_obtenido'] ?? '') ?>"
                                                   placeholder="Ej: 8.5">
                                        </td>
                                        <td class="pe-4">
                                            <input type="text"
                                                   class="form-control bg-dark text-light border-secondary"
                                                   name="notas[<?= $est['estudiante_id'] ?>][observacion]"
                                                   value="<?= htmlspecialchars($est['observacion'] ?? '') ?>"
                                                   placeholder="Comentario...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($estudiantes)): ?>
            <div class="sticky-save d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Guarda los cambios antes de salir.
                </span>
                <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">
                    <i class="bi bi-floppy-fill me-2"></i> Guardar Notas
                </button>
            </div>
        <?php endif; ?>
    </form>

</div>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
