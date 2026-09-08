<?php
// app/views/admin/cursos.php
$pageTitle = 'Gestión de Cursos';
require __DIR__ . '/../layouts/admin_header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">Gestión de Cursos</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cursoModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Curso
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
                            <th>Turno</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cursos)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay cursos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cursos as $curso): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($curso['nombre']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-25 text-light">
                                            <?= htmlspecialchars($curso['turno']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($curso['activo']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="editCurso(<?= $curso['id'] ?>, '<?= htmlspecialchars(addslashes($curso['nombre'])) ?>', '<?= htmlspecialchars(addslashes($curso['turno'])) ?>')">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="/edunexo/admin/cursos/toggle" class="m-0 p-0 d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                                                <?php if ($curso['activo']): ?>
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
<div class="modal fade" id="cursoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nuevo Curso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="cursoForm" method="POST" action="/edunexo/admin/cursos/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="cursoId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del Curso</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre" id="cursoNombre" required placeholder="Ej: 1er Año A">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-light">Turno</label>
                        <select class="form-select bg-dark text-light border-secondary" name="turno" id="cursoTurno">
                            <option value="Mañana">Mañana</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noche">Noche</option>
                        </select>
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
    const modal = new bootstrap.Modal(document.getElementById('cursoModal'));
    const form = document.getElementById('cursoForm');
    const title = document.getElementById('modalTitle');

    function resetForm() {
        form.action = '/edunexo/admin/cursos/store';
        title.textContent = 'Nuevo Curso';
        document.getElementById('cursoId').value = '';
        document.getElementById('cursoNombre').value = '';
        document.getElementById('cursoTurno').value = 'Mañana';
    }

    function editCurso(id, nombre, turno) {
        form.action = '/edunexo/admin/cursos/update';
        title.textContent = 'Editar Curso';
        document.getElementById('cursoId').value = id;
        document.getElementById('cursoNombre').value = nombre;
        document.getElementById('cursoTurno').value = turno;
        modal.show();
    }
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
