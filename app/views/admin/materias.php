<?php
// app/views/admin/materias.php
$pageTitle = 'Gestión de Materias';
require __DIR__ . '/../layouts/admin_header.php';
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Gestión de Materias</h2>
    <button class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem;" onclick="openModal(); resetForm()">
        <i class="bi bi-plus-lg" style="margin-right: 0.5rem;"></i> Nueva Materia
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill alert-icon"></i>
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="data-card">
    <div style="overflow-x:auto;">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materias)): ?>
                    <tr class="empty-row"><td colspan="3">No hay materias registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($materias as $materia): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($materia['nombre']) ?></td>
                            <td>
                                <?php if ($materia['activo']): ?>
                                    <span class="chip chip-logrado">Activa</span>
                                <?php else: ?>
                                    <span class="chip chip-no-log">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <button class="btn-secondary btn-sm btn-icon" style="color: #879fff; border-color: rgba(99,120,255,0.3);"
                                            onclick="editMateria(<?= $materia['id'] ?>, '<?= htmlspecialchars(addslashes($materia['nombre'])) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="/edunexo/admin/materias/toggle" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= $materia['id'] ?>">
                                        <?php if ($materia['activo']): ?>
                                            <button type="submit" class="btn-secondary btn-sm btn-icon" style="color: #ffd97d; border-color: rgba(255,160,60,0.3);" title="Desactivar">
                                                <i class="bi bi-pause-fill"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn-secondary btn-sm btn-icon" style="color: #7dffa9; border-color: rgba(60,200,120,0.3);" title="Activar">
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

<!-- Modal Personalizado -->
<div class="modal-overlay" id="materiaModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nueva Materia</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="materiaForm" method="POST" action="/edunexo/admin/materias/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="materiaId" value="">
                
                <div class="form-group">
                    <label class="form-label">Nombre de la Materia</label>
                    <input type="text" class="form-input no-icon" name="nombre" id="materiaNombre" required placeholder="Ej: Matemática">
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
    const modalOverlay = document.getElementById('materiaModal');
    const form = document.getElementById('materiaForm');
    const title = document.getElementById('modalTitle');

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
        openModal();
    }
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
