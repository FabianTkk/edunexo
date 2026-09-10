<?php
// app/views/admin/asignaciones.php
$pageTitle = 'Asignación Docentes';
require __DIR__ . '/../layouts/admin_header.php';
?>
<style>.curso-section { display: none; }</style>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem 0;">Asignación de Docentes a Cursos</h2>
        <p style="color: var(--text-muted); margin: 0;">Seleccioná un curso para asignar los docentes por materia o hacer una asignación masiva.</p>
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

    <!-- STEP 1: Seleccionar Curso -->
    <div class="data-card" style="margin-bottom: 2rem; border: 1px solid rgba(99,120,255,0.3);">
        <label class="form-label" style="font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem;">1. Seleccionar Curso</label>
        <select class="form-input no-icon" style="font-size: 1.1rem; padding: 0.75rem 1rem;" id="selectCurso" onchange="mostrarCurso(this.value)">
            <option value="">-- Elegir un curso --</option>
            <?php foreach ($cursos as $curso): ?>
                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nombre'] . ' (' . $curso['turno'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- SECTIONS PARA CADA CURSO (Ocultas por defecto) -->
    <div id="cursoPanels">
        <?php foreach ($cursos as $curso): ?>
            <div class="curso-section" id="curso-<?= $curso['id'] ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- ASIGNACION MASIVA -->
                    <div>
                        <div class="data-card h-100" style="padding: 0;">
                            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <h6 style="margin: 0; color: #ffd97d; font-size: 1.1rem; font-weight: 600;">
                                    <i class="bi bi-lightning-fill" style="margin-right: 0.5rem;"></i> Asignación Masiva
                                </h6>
                            </div>
                            <div style="padding: 1.5rem;">
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Asignar un docente a <strong>todas las materias</strong> de este curso.</p>
                                <form method="POST" action="/edunexo/admin/asignaciones/bulk">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                    
                                    <div class="form-group">
                                        <select name="docente_id" class="form-input no-icon" required>
                                            <option value="">-- Seleccionar Docente --</option>
                                            <?php foreach ($docentes as $docente): ?>
                                                <option value="<?= $docente['id'] ?>"><?= htmlspecialchars($docente['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); width: 100%; margin-top: 0.5rem;" onclick="return confirm('¿Asignar a todas las materias del curso?')">
                                        Asignar a Todas
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ASIGNACION INDIVIDUAL (Tabla) -->
                    <div style="grid-column: span 2 / span 2;">
                        <div class="data-card h-100" style="padding: 0;">
                            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <h6 style="margin: 0; color: #6378ff; font-size: 1.1rem; font-weight: 600;">
                                    <i class="bi bi-list-task" style="margin-right: 0.5rem;"></i> Asignación por Materia
                                </h6>
                            </div>
                            <div style="overflow-x:auto;">
                                <table class="tbl">
                                    <thead>
                                        <tr>
                                            <th>Materia</th>
                                            <th>Docente Asignado</th>
                                            <th style="text-align: right;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materias as $materia): 
                                            $cmd = $asignaciones[$curso['id']][$materia['id']] ?? null;
                                            $cmd_id = $cmd ? $cmd['id'] : 0;
                                            $assigned_docente_id = $cmd ? $cmd['docente_id'] : 0;
                                        ?>
                                            <tr>
                                                <td style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($materia['nombre']) ?></td>
                                                <td>
                                                    <form method="POST" action="/edunexo/admin/asignaciones/single" style="display: flex; gap: 0.5rem; margin: 0; align-items: center;">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="cmd_id" value="<?= $cmd_id ?>">
                                                        <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                                        <input type="hidden" name="materia_id" value="<?= $materia['id'] ?>">
                                                        
                                                        <select name="docente_id" class="form-input no-icon" style="padding: 0.4rem 0.75rem; min-width: 200px; margin-bottom: 0; flex: 1;" required>
                                                            <option value="">-- Sin asignar --</option>
                                                            <?php foreach ($docentes as $docente): ?>
                                                                <option value="<?= $docente['id'] ?>" <?= ($docente['id'] == $assigned_docente_id) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($docente['nombre']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        
                                                        <button type="submit" class="btn-secondary btn-sm" style="color: #879fff; border-color: rgba(99,120,255,0.3); margin-top: 0; white-space: nowrap;" title="Guardar cambios">
                                                            Guardar
                                                        </button>
                                                    </form>
                                                </td>
                                                <td></td> <!-- Placeholder for grid alignment with form -->
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> <!-- row -->

            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function mostrarCurso(cursoId) {
        // Ocultar todos
        document.querySelectorAll('.curso-section').forEach(el => {
            el.style.display = 'none';
        });
        
        // Mostrar el seleccionado
        if (cursoId) {
            const section = document.getElementById('curso-' + cursoId);
            if (section) section.style.display = 'block';
        }
    }
</script>
<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
