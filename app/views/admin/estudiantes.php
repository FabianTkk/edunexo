<?php $pageTitle = 'Estudiantes'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Estudiantes</h2>
    <button class="btn-primary" style="width: auto; padding: 0.75rem 1.25rem;" onclick="openModal(); resetForm()">
        <i class="bi bi-person-plus-fill" style="margin-right: 0.5rem;"></i> Nuevo Estudiante
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
        <input type="text" id="buscar" class="form-input" placeholder="Buscar por CI o nombre...">
    </div>
</div>

<div class="data-card">
    <div style="overflow-x:auto;">
        <table class="tbl" id="tablaEst">
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Nombre</th>
                    <th>Curso</th>
                    <th>Tutores</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($estudiantes as $e): ?>
                <tr>
                    <td style="color: var(--text-muted);"><?= htmlspecialchars($e['ci']) ?></td>
                    <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($e['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($e['curso_nombre'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($e['tutores_nombres'] ?? '—') ?></td>
                    <td>
                        <?php if ($e['estado'] === 'activo'): ?>
                            <span class="chip chip-logrado">Activo</span>
                        <?php else: ?>
                            <span class="chip chip-noeval">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <button class="btn-secondary btn-sm btn-icon" style="color: #879fff; border-color: rgba(99,120,255,0.3);"
                                data-tutores="<?= htmlspecialchars($e['tutor_ids'] ?? '') ?>"
                                onclick="editEst(<?= (int)$e['id'] ?>,<?= \App\Helpers\SecurityHelper::jsArg($e['ci']) ?>,<?= \App\Helpers\SecurityHelper::jsArg($e['nombre_completo']) ?>,<?= (int)($e['curso_id'] ?? 0) ?>,this.dataset.tutores)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="/edunexo/admin/estudiantes/toggle" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn-secondary btn-sm btn-icon" 
                                    style="<?= $e['estado']==='activo' ? 'color: #ffd97d; border-color: rgba(255,160,60,0.3);' : 'color: #7dffa9; border-color: rgba(60,200,120,0.3);' ?>" 
                                    title="<?= $e['estado']==='activo' ? 'Desactivar' : 'Activar' ?>">
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

<!-- Modal Personalizado -->
<div class="modal-overlay" id="estudianteModal">
    <div class="custom-modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Nuevo Estudiante</div>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="estForm" method="POST" action="/edunexo/admin/estudiantes/store">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="eId" value="">
                
                <div class="form-group">
                    <label class="form-label">CI</label>
                    <input type="text" class="form-input no-icon" name="ci" id="eCi" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input no-icon" name="nombre_completo" id="eNombre" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Curso</label>
                    <select class="form-input no-icon" name="curso_id" id="eCurso" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['turno'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tutores (opcional, puede marcar varios)</label>
                    <div style="max-height: 160px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 0.5rem 0.75rem;">
                        <?php if (empty($tutores)): ?>
                            <span style="color: var(--text-muted); font-size: 0.9rem;">No hay tutores activos cargados.</span>
                        <?php else: ?>
                            <?php foreach ($tutores as $t): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0; cursor: pointer;">
                                    <input type="checkbox" class="tutor-check" name="tutor_ids[]" value="<?= (int)$t['id'] ?>">
                                    <span><?= htmlspecialchars($t['nombre_completo']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
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
const modalOverlay = document.getElementById('estudianteModal');
const form = document.getElementById('estForm');

function openModal() {
    modalOverlay.classList.add('show');
}

function closeModal() {
    modalOverlay.classList.remove('show');
}

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});

function marcarTutores(csv) {
    const ids = (csv || '').split(',').filter(Boolean);
    document.querySelectorAll('#estForm .tutor-check').forEach(cb => {
        cb.checked = ids.includes(cb.value);
    });
}

function resetForm() {
    form.action = '/edunexo/admin/estudiantes/store';
    document.getElementById('modalTitle').textContent = 'Nuevo Estudiante';
    document.getElementById('eId').value = '';
    document.getElementById('eCi').value = '';
    document.getElementById('eNombre').value = '';
    document.getElementById('eCurso').value = '';
    marcarTutores('');
}

function editEst(id, ci, nombre, curso_id, tutores) {
    form.action = '/edunexo/admin/estudiantes/update';
    document.getElementById('modalTitle').textContent = 'Editar Estudiante';
    document.getElementById('eId').value = id;
    document.getElementById('eCi').value = ci;
    document.getElementById('eNombre').value = nombre;
    document.getElementById('eCurso').value = curso_id;
    marcarTutores(tutores);
    openModal();
}

document.getElementById('buscar').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaEst tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
