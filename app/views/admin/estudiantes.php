<?php $pageTitle = 'Estudiantes'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Estudiantes</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#estudianteModal" onclick="resetForm()">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Estudiante
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

    <div class="card bs-card rounded-4 shadow-sm mb-3">
        <div class="card-body pb-2">
            <input type="text" id="buscar" class="form-control bg-dark text-light border-secondary" placeholder="Buscar por CI o nombre...">
        </div>
    </div>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0" id="tablaEst">
                    <thead><tr>
                        <th class="ps-4">CI</th>
                        <th>Nombre</th>
                        <th>Curso</th>
                        <th>Tutor</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($estudiantes as $e): ?>
                        <tr>
                            <td class="ps-4 text-secondary"><?= htmlspecialchars($e['ci']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($e['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($e['curso_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($e['tutor_nombre'] ?? '—') ?></td>
                            <td>
                                <?php if ($e['estado'] === 'activo'): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="editEst(<?= $e['id'] ?>,'<?= addslashes($e['ci']) ?>','<?= addslashes($e['nombre_completo']) ?>',<?= $e['curso_id'] ?? 0 ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="/edunexo/admin/estudiantes/toggle" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $e['estado']==='activo' ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                            <i class="bi <?= $e['estado']==='activo' ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                                        </button>
                                    </form>
                                </div>
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
<div class="modal fade" id="estudianteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:#1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nuevo Estudiante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="estForm" method="POST" action="/edunexo/admin/estudiantes/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="eId" value="">
                    <div class="mb-3">
                        <label class="form-label text-light">CI</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="ci" id="eCi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre completo</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre_completo" id="eNombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Curso</label>
                        <select class="form-select bg-dark text-light border-secondary" name="curso_id" id="eCurso" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['turno'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Tutor (opcional)</label>
                        <select class="form-select bg-dark text-light border-secondary" name="tutor_id" id="eTutor">
                            <option value="0">-- Sin tutor --</option>
                            <?php foreach ($tutores as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre_completo']) ?></option>
                            <?php endforeach; ?>
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
const modal = new bootstrap.Modal(document.getElementById('estudianteModal'));
const form  = document.getElementById('estForm');

function resetForm() {
    form.action = '/edunexo/admin/estudiantes/store';
    document.getElementById('modalTitle').textContent = 'Nuevo Estudiante';
    document.getElementById('eId').value = '';
    document.getElementById('eCi').value = '';
    document.getElementById('eNombre').value = '';
    document.getElementById('eCurso').value = '';
    document.getElementById('eTutor').value = '0';
}

function editEst(id, ci, nombre, curso_id) {
    form.action = '/edunexo/admin/estudiantes/update';
    document.getElementById('modalTitle').textContent = 'Editar Estudiante';
    document.getElementById('eId').value = id;
    document.getElementById('eCi').value = ci;
    document.getElementById('eNombre').value = nombre;
    document.getElementById('eCurso').value = curso_id;
    modal.show();
}

document.getElementById('buscar').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaEst tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
