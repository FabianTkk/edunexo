<?php
// app/views/docente/materias.php
$pageTitle = 'Mis Materias';
require __DIR__ . '/../layouts/docente_header.php';
?>

<style>
    .materia-card {
        background-color: #1a1d2d;
        border: 1px solid rgba(255,255,255,0.1);
        transition: 0.3s ease;
    }
    .materia-card:hover {
        transform: translateY(-5px);
        border-color: rgba(99,120,255,0.4);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
</style>

<div class="container py-5">
    <div class="mb-5 text-center">
        <h2 class="fw-bold mb-2">Mis Materias Asignadas</h2>
        <p class="text-muted">Aca podes ver todos los cursos en los que das clases y acceder a las evaluaciones.</p>
    </div>

    <?php if (empty($asignaciones)): ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-x display-1 text-muted mb-3 d-block opacity-50"></i>
            <h4 class="text-secondary">No tenes materias asignadas</h4>
            <p class="text-muted">Contacta al administrador si crees que esto es un error.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($asignaciones as $asig): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 materia-card p-3 rounded-4">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="badge bg-primary bg-opacity-25 text-info px-3 py-2 rounded-pill">
                                    <i class="bi bi-people-fill me-1"></i> <?= htmlspecialchars($asig['curso']) ?>
                                </div>
                                <span class="badge bg-secondary bg-opacity-25 text-light">
                                    <?= htmlspecialchars($asig['turno'] ?? '') ?>
                                </span>
                            </div>
                            <h4 class="card-title fw-bold mt-2 mb-1 text-light">
                                <?= htmlspecialchars($asig['materia']) ?>
                            </h4>
                            <?php if (!empty($asig['docente_nombre'])): ?>
                                <div style="color: var(--accent); font-size: 0.85rem; margin-bottom: 0.3rem;">
                                    <i class="bi bi-person-fill"></i> <?= htmlspecialchars($asig['docente_nombre']) ?>
                                </div>
                            <?php endif; ?>
                            <p class="card-text text-muted small mb-4">
                                Gestion de evaluaciones y notas para este curso.
                            </p>
                            <div class="mt-auto">
                                <a href="/edunexo/docente/evaluaciones?cmd_id=<?= $asig['id'] ?>" class="btn btn-primary w-100 fw-medium">
                                    Ver Evaluaciones <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
