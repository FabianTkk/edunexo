<?php $pageTitle = 'Reportes — ' . htmlspecialchars($curso['nombre'] ?? ''); require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <a href="/edunexo/admin/reportes" class="btn-secondary btn-sm" style="display: inline-flex; width: auto; margin-bottom: 1.5rem;">
        <i class="bi bi-arrow-left" style="margin-right: 0.5rem;"></i> Volver
    </a>
    
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem 0;"><?= htmlspecialchars($curso['nombre']) ?></h2>
        <p style="color: var(--text-muted); margin: 0;">Reportes del curso</p>
    </div>

    <!-- Filtros -->
    <div class="data-card" style="margin-bottom: 2rem;">
        <form method="GET" action="/edunexo/admin/reportes/curso" style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
            <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Filtrar por</label>
                <select name="filtro" class="form-input no-icon" style="width: auto; min-width: 150px; margin-bottom: 0;">
                    <option value="semana" <?= ($_GET['filtro']??'semana')==='semana'?'selected':'' ?>>Semana</option>
                    <option value="mes"    <?= ($_GET['filtro']??'')==='mes'?'selected':'' ?>>Mes</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Fecha</label>
                <input type="date" name="fecha" class="form-input no-icon" style="width: auto; margin-bottom: 0;" value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d')) ?>">
            </div>
            
            <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
        </form>
    </div>

    <div class="data-card">
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>CI</th>
                        <th>Semana</th>
                        <th>Ausencias</th>
                        <th>Comportamiento</th>
                        <th>Docente</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($reportes)): ?>
                    <tr class="empty-row"><td colspan="6">No hay reportes para este filtro.</td></tr>
                <?php else: foreach ($reportes as $r): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($r['nombre_completo']) ?></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($r['ci']) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['periodo_semana'])) ?></td>
                        <td>
                            <?php if ($r['dias_ausente'] > 0): ?>
                                <span class="chip chip-noeval"><?= $r['dias_ausente'] ?> dias</span>
                            <?php else: ?>
                                <span class="chip chip-logrado"><?= $r['dias_ausente'] ?> dias</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['comportamiento']) ?></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($r['docente_nombre'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
