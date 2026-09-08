<?php $pageTitle = 'Reportes — ' . htmlspecialchars($curso['nombre'] ?? ''); require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <a href="/edunexo/admin/reportes" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>
    <h2 class="fw-bold mb-1"><?= htmlspecialchars($curso['nombre']) ?></h2>
    <p class="text-muted mb-4">Reportes del curso</p>

    <!-- Filtros -->
    <form method="GET" action="/edunexo/admin/reportes/curso" class="card bs-card rounded-4 shadow-sm mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-end">
            <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
            <div>
                <label class="form-label text-light small mb-1">Filtrar por</label>
                <select name="filtro" class="form-select bg-dark text-light border-secondary" style="width:auto">
                    <option value="semana" <?= ($_GET['filtro']??'semana')==='semana'?'selected':'' ?>>Semana</option>
                    <option value="mes"    <?= ($_GET['filtro']??'')==='mes'?'selected':'' ?>>Mes</option>
                </select>
            </div>
            <div>
                <label class="form-label text-light small mb-1">Fecha</label>
                <input type="date" name="fecha" class="form-control bg-dark text-light border-secondary" value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d')) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0">
                    <thead><tr>
                        <th class="ps-4">Alumno</th>
                        <th>CI</th>
                        <th>Semana</th>
                        <th>Ausencias</th>
                        <th>Comportamiento</th>
                        <th>Docente</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($reportes)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay reportes para este filtro.</td></tr>
                    <?php else: foreach ($reportes as $r): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($r['nombre_completo']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($r['ci']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['periodo_semana'])) ?></td>
                            <td><span class="badge <?= $r['dias_ausente'] > 0 ? 'bg-warning bg-opacity-25 text-warning' : 'bg-success bg-opacity-25 text-success' ?>"><?= $r['dias_ausente'] ?> dias</span></td>
                            <td><?= htmlspecialchars($r['comportamiento']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($r['docente_nombre'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
