<?php $pageTitle = 'Usuarios'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Usuarios del Sistema</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal" onclick="resetForm()">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
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
            <input type="text" id="buscar" class="form-control bg-dark text-light border-secondary" placeholder="Buscar por nombre o usuario...">
        </div>
    </div>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0" id="tablaUsuarios">
                    <thead><tr>
                        <th class="ps-4">Nombre</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registrado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td class="text-secondary">@<?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                            <td>
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-25 text-info">Docente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['activo']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="editUsuario(<?= $u['id'] ?>,'<?= addslashes($u['nombre']) ?>','<?= addslashes($u['username']) ?>','<?= addslashes($u['email'] ?? '') ?>','<?= $u['rol'] ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="/edunexo/admin/usuarios/toggle" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $u['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="bi <?= $u['activo'] ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
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
<div class="modal fade" id="usuarioModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:#1a1d2d;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title" id="modalTitle">Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="usuarioForm" method="POST" action="/edunexo/admin/usuarios/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" id="uid" value="">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre completo</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre" id="uNombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Usuario</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" name="username" id="uUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Email</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" name="email" id="uEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Contrasena <span id="passHint" class="text-muted small">(obligatorio)</span></label>
                        <input type="password" class="form-control bg-dark text-light border-secondary" name="password" id="uPassword">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Rol</label>
                        <select class="form-select bg-dark text-light border-secondary" name="rol" id="uRol">
                            <option value="docente">Docente</option>
                            <option value="admin">Administrador</option>
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
const modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
const form  = document.getElementById('usuarioForm');

function resetForm() {
    form.action = '/edunexo/admin/usuarios/store';
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
    document.getElementById('uid').value = '';
    document.getElementById('uNombre').value = '';
    document.getElementById('uUsername').value = '';
    document.getElementById('uEmail').value = '';
    document.getElementById('uPassword').value = '';
    document.getElementById('uRol').value = 'docente';
    document.getElementById('passHint').textContent = '(obligatorio)';
}

function editUsuario(id, nombre, username, email, rol) {
    form.action = '/edunexo/admin/usuarios/update';
    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('uid').value = id;
    document.getElementById('uNombre').value = nombre;
    document.getElementById('uUsername').value = username;
    document.getElementById('uEmail').value = email;
    document.getElementById('uPassword').value = '';
    document.getElementById('uRol').value = rol;
    document.getElementById('passHint').textContent = '(dejar vacio para no cambiar)';
    modal.show();
}

document.getElementById('buscar').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
