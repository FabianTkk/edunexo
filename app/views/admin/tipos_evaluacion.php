<?php
// app/views/admin/tipos_evaluacion.php
$pageTitle = 'Tipos de Evaluacion';
require __DIR__ . '/../layouts/admin_header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 fw-bold">Tipos de Evaluacion</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tipoModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Tipo
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
                            <th class="ps-4">Nombre</th>
                            <th>Peso (%)</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tipos)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay tipos de evaluacion.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tipos as $tipo): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($tipo['nombre']) ?></td>
                                    <td><?= htmlspecialchars($tipo['peso_porcentaje']) ?>%</td>
                                    <td>
                                        <?php if ($tipo['activo']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-25 text-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-sm btn-outline-info"
                                                    onclick="editTipo(<?= $tipo['id'] ?>, '<?= htmlspecialchars(addslashes($tipo['nombre'])) ?>', <?= $tipo['peso_porcentaje'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="/edunexo/admin/tipos_evaluacion/toggle" class="m-0 p-0 d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $tipo['id'] ?>">
                                                <?php if ($tipo['activo']): ?>
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

<!-- Modal -->
<div class="modal fade" id="tipoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nuevo Tipo de Evaluacion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="tipoForm" method="POST" action="/edunexo/admin/tipos_evaluacion/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="tipoId" value="">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre" id="tipoNombre" required placeholder="Ej: Trabajo Practico">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Peso / Porcentaje (%)</label>
                        <input type="number" step="0.01" min="1" max="100" class="form-control bg-dark text-light border-secondary" name="peso_porcentaje" id="tipoPeso" required>
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
    const modal = new bootstrap.Modal(document.getElementById('tipoModal'));
    const form  = document.getElementById('tipoForm');
    const title = document.getElementById('modalTitle');

    function resetForm() {
        form.action = '/edunexo/admin/tipos_evaluacion/store';
        title.textContent = 'Nuevo Tipo de Evaluacion';
        document.getElementById('tipoId').value    = '';
        document.getElementById('tipoNombre').value = '';
        document.getElementById('tipoPeso').value   = '';
    }

    function editTipo(id, nombre, peso) {
        form.action = '/edunexo/admin/tipos_evaluacion/update';
        title.textContent = 'Editar Tipo de Evaluacion';
        document.getElementById('tipoId').value    = id;
        document.getElementById('tipoNombre').value = nombre;
        document.getElementById('tipoPeso').value   = peso;
        modal.show();
    }
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
