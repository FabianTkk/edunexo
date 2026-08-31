<?php
// app/views/admin/asignaciones.php
$pageTitle = 'Asignación Docentes';
require __DIR__ . '/../layouts/admin_header.php';
?>
<style>.curso-section { display: none; }</style>

<div class="container py-5">
    <div class="mb-4">
        <h2 class="mb-0 fw-bold">Asignación de Docentes a Cursos</h2>
        <p class="text-muted">Seleccioná un curso para asignar los docentes por materia o hacer una asignación masiva.</p>
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

    <!-- STEP 1: Seleccionar Curso -->
    <div class="card shadow-sm mb-4 border-primary border-opacity-50">
        <div class="card-body">
            <label class="form-label text-light fw-bold">1. Seleccionar Curso</label>
            <select class="form-select form-select-lg bg-dark text-light border-secondary" id="selectCurso" onchange="mostrarCurso(this.value)">
                <option value="">-- Elegir un curso --</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nombre'] . ' (' . $curso['turno'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- SECTIONS PARA CADA CURSO (Ocultas por defecto) -->
    <div id="cursoPanels">
        <?php foreach ($cursos as $curso): ?>
            <div class="curso-section" id="curso-<?= $curso['id'] ?>">
                
                <div class="row g-4">
                    <!-- ASIGNACION MASIVA -->
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent pt-3 pb-2">
                                <h6 class="mb-0 text-warning"><i class="bi bi-lightning-fill me-1"></i> Asignación Masiva</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Asignar un docente a <strong>todas las materias</strong> de este curso.</p>
                                <form method="POST" action="/edunexo/admin/asignaciones/bulk">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                    
                                    <div class="mb-3">
                                        <select name="docente_id" class="form-select bg-dark text-light border-secondary" required>
                                            <option value="">-- Seleccionar Docente --</option>
                                            <?php foreach ($docentes as $docente): ?>
                                                <option value="<?= $docente['id'] ?>"><?= htmlspecialchars($docente['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Asignar a todas las materias del curso?')">
                                        Asignar a Todas
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ASIGNACION INDIVIDUAL (Tabla) -->
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent pt-3 pb-2">
                                <h6 class="mb-0 text-info"><i class="bi bi-list-task me-1"></i> Asignación por Materia</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Materia</th>
                                                <th>Docente Asignado</th>
                                                <th class="text-end pe-4">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($materias as $materia): 
                                                $cmd = $asignaciones[$curso['id']][$materia['id']] ?? null;
                                                $cmd_id = $cmd ? $cmd['id'] : 0;
                                                $assigned_docente_id = $cmd ? $cmd['docente_id'] : 0;
                                            ?>
                                                <tr>
                                                    <td class="ps-4 fw-medium text-light"><?= htmlspecialchars($materia['nombre']) ?></td>
                                                    <td>
                                                        <form method="POST" action="/edunexo/admin/asignaciones/single" class="d-flex gap-2 m-0 p-0">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="cmd_id" value="<?= $cmd_id ?>">
                                                            <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                                            <input type="hidden" name="materia_id" value="<?= $materia['id'] ?>">
                                                            
                                                            <select name="docente_id" class="form-select form-select-sm bg-dark text-light border-secondary" required style="min-width: 200px;">
                                                                <option value="">-- Sin asignar --</option>
                                                                <?php foreach ($docentes as $docente): ?>
                                                                    <option value="<?= $docente['id'] ?>" <?= ($docente['id'] == $assigned_docente_id) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($docente['nombre']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            
                                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar cambios">
                                                                Guardar
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td></td> <!-- The button is inside the form on the previous column to avoid layout issues -->
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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
