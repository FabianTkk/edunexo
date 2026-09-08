<?php $pageTitle = 'Tutores'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Tutores</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tutorModal" onclick="resetForm()">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Tutor
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0">
                    <thead><tr>
                        <th class="ps-4">Nombre</th>
                        <th>Telefono</th>
                        <th>Estudiantes vinculados</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($tutores as $t): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($t['nombre_completo']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($t['telefono']) ?></td>
                            <td>
                                <?php if (empty($t['estudiantes'])): ?>
                                    <span class="text-muted small">Sin estudiantes</span>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($t['estudiantes'] as $est): ?>
                                        <span class="badge bg-primary bg-opacity-25 text-info"><?= htmlspecialchars($est['nombre_completo']) ?></span>
                                    <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-info"
                                    onclick="editTutor(<?= $t['id'] ?>,'<?= addslashes($t['nombre_completo']) ?>','<?= addslashes($t['telefono']) ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="tutorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:#1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nuevo Tutor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="tutorForm" method="POST" action="/edunexo/admin/tutores/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="tId" value="">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre completo</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre_completo" id="tNombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Telefono</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="telefono" id="tTelefono" required placeholder="595981234567">
                        <div class="form-text text-secondary">Formato: 595 + 9 digitos. Ej: 595981234567</div>
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
const modal = new bootstrap.Modal(document.getElementById('tutorModal'));
const form  = document.getElementById('tutorForm');

function resetForm() {
    form.action = '/edunexo/admin/tutores/store';
    document.getElementById('modalTitle').textContent = 'Nuevo Tutor';
    document.getElementById('tId').value = '';
    document.getElementById('tNombre').value = '';
    document.getElementById('tTelefono').value = '';
}

function editTutor(id, nombre, telefono) {
    form.action = '/edunexo/admin/tutores/update';
    document.getElementById('modalTitle').textContent = 'Editar Tutor';
    document.getElementById('tId').value = id;
    document.getElementById('tNombre').value = nombre;
    document.getElementById('tTelefono').value = telefono;
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
