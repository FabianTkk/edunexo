<?php
// app/views/admin/cursos.php
$pageTitle = 'Gestión de Cursos';
require __DIR__ . '/../layouts/admin_header.php';
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Gestión de Cursos</h2>
    <button class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem;" onclick="openModal(); resetForm()">
        <i class="bi bi-plus-lg" style="margin-right: 0.5rem;"></i> Nuevo Curso
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
                    <th>Turno</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cursos)): ?>
                    <tr class="empty-row"><td colspan="4">No hay cursos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($curso['nombre']) ?></td>
                            <td>
                                <span class="chip chip-noeval"><?= htmlspecialchars($curso['turno']) ?></span>
                            </td>
                            <td>
                                <?php if ($curso['activo']): ?>
                                    <span class="chip chip-logrado">Activo</span>
                                <?php else: ?>
                                    <span class="chip chip-no-log">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <button class="btn-secondary btn-sm btn-icon" style="color: #879fff; border-color: rgba(99,120,255,0.3);"
                                            onclick="editCurso(<?= $curso['id'] ?>, '<?= htmlspecialchars(addslashes($curso['nombre'])) ?>', '<?= htmlspecialchars(addslashes($curso['turno'])) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="/edunexo/admin/cursos/toggle" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                                        <?php if ($curso['activo']): ?>
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
<div class="modal-overlay" id="cursoModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nuevo Curso</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="cursoForm" method="POST" action="/edunexo/admin/cursos/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="cursoId" value="">
                
                <div class="form-group">
                    <label class="form-label">Nombre del Curso</label>
                    <input type="text" class="form-input no-icon" name="nombre" id="cursoNombre" required placeholder="Ej: 1er Año A">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Turno</label>
                    <select class="form-input no-icon" name="turno" id="cursoTurno">
                        <option value="Mañana">Mañana</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Noche">Noche</option>
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
    const modalOverlay = document.getElementById('cursoModal');
    const form = document.getElementById('cursoForm');
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
        openModal();
    }
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
