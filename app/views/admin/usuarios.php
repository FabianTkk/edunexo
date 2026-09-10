<?php $pageTitle = 'Usuarios'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Usuarios del Sistema</h2>
    <button class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem;" onclick="openModal(); resetForm()">
        <i class="bi bi-person-plus-fill" style="margin-right: 0.5rem;"></i> Nuevo Usuario
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill alert-icon"></i>
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

<div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem;">
    <div class="form-input-wrap">
        <i class="bi bi-search form-input-icon"></i>
        <input type="text" id="buscar" class="form-input" placeholder="Buscar por nombre o usuario...">
    </div>
</div>

<div class="data-card">
    <div style="overflow-x:auto;">
        <table class="tbl" id="tablaUsuarios">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registrado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($u['nombre']) ?></td>
                    <td>@<?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td>
                        <span class="chip chip-<?= $u['rol'] ?>"><?= $u['rol'] === 'admin' ? 'Admin' : 'Docente' ?></span>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                            <span class="chip chip-logrado">Activo</span>
                        <?php else: ?>
                            <span class="chip chip-no-log">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <button class="btn-secondary btn-sm btn-icon" style="color: #879fff; border-color: rgba(99,120,255,0.3);" 
                                onclick="editUsuario(<?= $u['id'] ?>,'<?= addslashes($u['nombre']) ?>','<?= addslashes($u['username']) ?>','<?= addslashes($u['email'] ?? '') ?>','<?= $u['rol'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="/edunexo/admin/usuarios/toggle" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-secondary btn-sm btn-icon" 
                                    style="<?= $u['activo'] ? 'color: #ffd97d; border-color: rgba(255,160,60,0.3);' : 'color: #7dffa9; border-color: rgba(60,200,120,0.3);' ?>" 
                                    title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
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

<!-- Modal Personalizado -->
<div class="modal-overlay" id="usuarioModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nuevo Usuario</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="usuarioForm" method="POST" action="/edunexo/admin/usuarios/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="uid" value="">
                
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input no-icon" name="nombre" id="uNombre" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <input type="text" class="form-input no-icon" name="username" id="uUsername" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input no-icon" name="email" id="uEmail">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contrasena <span id="passHint" style="font-weight:normal; text-transform:none;">(obligatorio)</span></label>
                    <input type="password" class="form-input no-icon" name="password" id="uPassword">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select class="form-input no-icon" name="rol" id="uRol">
                        <option value="docente">Docente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const modalOverlay = document.getElementById('usuarioModal');
const form = document.getElementById('usuarioForm');

function openModal() {
    modalOverlay.classList.add('show');
}

function closeModal() {
    modalOverlay.classList.remove('show');
}

// Cerrar modal al clickear fuera de el
modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});

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
    openModal();
}

document.getElementById('buscar').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
