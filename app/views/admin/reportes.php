<?php $pageTitle = 'Reportes'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem 0;">Todos los Reportes</h2>
        <p style="color: var(--text-muted); margin: 0;">Selecciona un curso para ver los reportes, o busca por CI del alumno.</p>
    </div>

    <!-- Busqueda por CI -->
    <div class="data-card" style="margin-bottom: 2rem;">
        <form method="GET" action="/edunexo/admin/reportes/ci" style="display: flex; gap: 0.5rem; margin: 0;">
            <div class="form-input-wrap" style="flex: 1; margin: 0;">
                <i class="bi bi-search form-input-icon"></i>
                <input type="text" name="ci" class="form-input" style="margin: 0;" placeholder="Buscar por CI del alumno..." value="<?= htmlspecialchars($_GET['ci'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-primary" style="width: auto; margin: 0; padding: 0 1.5rem;">
                Buscar
            </button>
        </form>
    </div>

    <!-- Cards por curso -->
    <h5 style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Reportes por Curso</h5>
    
    <?php if (empty($cursos)): ?>
        <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">No hay cursos activos con reportes.</div>
    <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
        <?php foreach ($cursos as $c): ?>
        <a href="/edunexo/admin/reportes/curso?curso_id=<?= $c['id'] ?>" style="text-decoration: none; display: block;">
            <div class="data-card" style="height: 100%; transition: 0.2s; cursor: pointer; display: flex; flex-direction: column;" 
                 onmouseover="this.style.borderColor='rgba(99,120,255,0.5)'" 
                 onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <span class="chip chip-docente"><?= htmlspecialchars($c['turno']) ?></span>
                    <span class="chip chip-logrado"><?= $c['total_reportes'] ?> reportes</span>
                </div>
                <h5 style="font-weight: 700; color: var(--text-primary); margin: 0; font-size: 1.1rem;"><?= htmlspecialchars($c['nombre']) ?></h5>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
