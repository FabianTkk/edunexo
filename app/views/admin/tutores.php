<?php $pageTitle = 'Tutores'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Tutores</h2>
    <button class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem;" onclick="openModal(); resetForm()">
        <i class="bi bi-person-plus-fill" style="margin-right: 0.5rem;"></i> Nuevo Tutor
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

<div class="data-card">
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Telefono</th>
                    <th>Estado</th>
                    <th>Estudiantes vinculados</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tutores as $t): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($t['nombre_completo']) ?></td>
                    <td style="color: var(--text-muted);"><?= htmlspecialchars($t['telefono']) ?></td>
                    <td>
                        <span class="chip <?= $t['activo'] ? 'chip-logrado' : 'chip-no-log' ?>">
                            <?= $t['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (empty($t['estudiantes'])): ?>
                            <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Sin estudiantes</span>
                        <?php else: ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            <?php foreach ($t['estudiantes'] as $est): ?>
                                <span class="chip chip-docente"><?= htmlspecialchars($est['nombre_completo']) ?></span>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <button class="btn-secondary btn-sm btn-icon" style="color: #879fff; border-color: rgba(99,120,255,0.3);"
                            onclick="editTutor(<?= $t['id'] ?>,'<?= addslashes($t['nombre_completo']) ?>','<?= addslashes($t['telefono']) ?>')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="/edunexo/admin/tutores/toggle" style="display:inline; margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="btn-secondary btn-sm btn-icon"
                                    title="<?= $t['activo'] ? 'Desactivar' : 'Activar' ?>">
                                <i class="bi <?= $t['activo'] ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Personalizado -->
<div class="modal-overlay" id="tutorModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nuevo Tutor</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="tutorForm" method="POST" action="/edunexo/admin/tutores/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="tId" value="">
                
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input no-icon" name="nombre_completo" id="tNombre" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-input no-icon" name="telefono" id="tTelefono" required placeholder="595981234567">
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Formato: 595 + 9 digitos. Ej: 595981234567</div>
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
const modalOverlay = document.getElementById('tutorModal');
const form = document.getElementById('tutorForm');

function openModal() {
    modalOverlay.classList.add('show');
}

function closeModal() {
    modalOverlay.classList.remove('show');
}

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});

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
    openModal();
}
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
