<?php $pageTitle = 'Reportes'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Todos los Reportes</h2>
        <p class="text-muted mb-0">Selecciona un curso para ver los reportes, o busca por CI del alumno.</p>
    </div>

    <!-- Busqueda por CI -->
    <div class="card bs-card rounded-4 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/edunexo/admin/reportes/ci" class="d-flex gap-2">
                <input type="text" name="ci" class="form-control bg-dark text-light border-secondary" placeholder="Buscar por CI del alumno..." value="<?= htmlspecialchars($_GET['ci'] ?? '') ?>">
                <button type="submit" class="btn btn-outline-primary px-4"><i class="bi bi-search me-1"></i> Buscar</button>
            </form>
        </div>
    </div>

    <!-- Cards por curso -->
    <h5 class="text-secondary mb-3 fw-semibold">Reportes por Curso</h5>
    <?php if (empty($cursos)): ?>
        <div class="text-center py-5 text-muted">No hay cursos activos con reportes.</div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($cursos as $c): ?>
        <div class="col-md-4 col-lg-3">
            <a href="/edunexo/admin/reportes/curso?curso_id=<?= $c['id'] ?>" class="text-decoration-none">
                <div class="card bs-card rounded-4 h-100 p-3" style="transition:.2s;cursor:pointer;" onmouseover="this.style.borderColor='rgba(99,120,255,.5)'" onmouseout="this.style.borderColor='rgba(255,255,255,.1)'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary bg-opacity-25 text-info"><?= htmlspecialchars($c['turno']) ?></span>
                        <span class="badge bg-success bg-opacity-25 text-success"><?= $c['total_reportes'] ?> reportes</span>
                    </div>
                    <h5 class="fw-bold text-light mb-0"><?= htmlspecialchars($c['nombre']) ?></h5>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
