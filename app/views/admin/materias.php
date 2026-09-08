<?php
// app/views/admin/materias.php
$pageTitle = 'Gestión de Materias';
require __DIR__ . '/../layouts/admin_header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">Gestión de Materias</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#materiaModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i> Nueva Materia
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

    <div class="card bs-card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Nombre</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materias)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No hay materias registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($materias as $materia): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($materia['nombre']) ?></td>
                                    <td>
                                        <?php if ($materia['activo']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="editMateria(<?= $materia['id'] ?>, '<?= htmlspecialchars(addslashes($materia['nombre'])) ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="/edunexo/admin/materias/toggle" class="m-0 p-0 d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $materia['id'] ?>">
                                                <?php if ($materia['activo']): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Desactivar">
                                                        <i class="bi bi-pause-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Activar">
                                                        <i class="bi bi-play-fill"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- Modal Form -->
<div class="modal fade" id="materiaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nueva Materia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="materiaForm" method="POST" action="/edunexo/admin/materias/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="materiaId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre de la Materia</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre" id="materiaNombre" required placeholder="Ej: Matemática">
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
    const modal = new bootstrap.Modal(document.getElementById('materiaModal'));
    const form = document.getElementById('materiaForm');
    const title = document.getElementById('modalTitle');

    function resetForm() {
        form.action = '/edunexo/admin/materias/store';
        title.textContent = 'Nueva Materia';
        document.getElementById('materiaId').value = '';
        document.getElementById('materiaNombre').value = '';
    }

    function editMateria(id, nombre) {
        form.action = '/edunexo/admin/materias/update';
        title.textContent = 'Editar Materia';
        document.getElementById('materiaId').value = id;
        document.getElementById('materiaNombre').value = nombre;
        modal.show();
    }
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
