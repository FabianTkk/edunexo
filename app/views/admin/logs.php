<?php $pageTitle = 'Logs de Validacion'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Logs de Validacion</h2>

    <!-- Totales -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bs-card rounded-4 p-3 text-center">
                <div class="text-muted small mb-1">Total intentos</div>
                <div class="fw-bold fs-3"><?= $totales['total'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bs-card rounded-4 p-3 text-center">
                <div class="text-success small mb-1">Exitosos</div>
                <div class="fw-bold fs-3 text-success"><?= $totales['exitosos'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bs-card rounded-4 p-3 text-center">
                <div class="text-danger small mb-1">Fallidos</div>
                <div class="fw-bold fs-3 text-danger"><?= $totales['fallidos'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" action="/edunexo/admin/logs" class="card bs-card rounded-4 shadow-sm mb-4">
        <div class="card-body d-flex gap-3 flex-wrap align-items-end">
            <div>
                <label class="form-label text-light small mb-1">Resultado</label>
                <select name="resultado" class="form-select bg-dark text-light border-secondary" style="width:auto">
                    <option value="">Todos</option>
                    <option value="exitoso" <?= ($_GET['resultado']??'')==='exitoso'?'selected':'' ?>>Exitoso</option>
                    <option value="fallido" <?= ($_GET['resultado']??'')==='fallido'?'selected':'' ?>>Fallido</option>
                </select>
            </div>
            <div>
                <label class="form-label text-light small mb-1">Desde</label>
                <input type="date" name="desde" class="form-control bg-dark text-light border-secondary" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label text-light small mb-1">Hasta</label>
                <input type="date" name="hasta" class="form-control bg-dark text-light border-secondary" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/edunexo/admin/logs" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <div class="card bs-card rounded-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bs-table mb-0">
                    <thead><tr>
                        <th class="ps-4">Estudiante</th>
                        <th>CI</th>
                        <th>Telefono intentado</th>
                        <th>Resultado</th>
                        <th>Fecha y hora</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay logs para mostrar.</td></tr>
                    <?php else: foreach ($logs as $l): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($l['nombre_completo']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($l['ci']) ?></td>
                            <td><?= htmlspecialchars($l['telefono_intentado']) ?></td>
                            <td>
                                <?php if ($l['resultado']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success"><i class="bi bi-check-circle me-1"></i>Exitoso</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger"><i class="bi bi-x-circle me-1"></i>Fallido</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?= date('d/m/Y H:i:s', strtotime($l['fecha_intento'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
