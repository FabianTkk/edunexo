<?php $pageTitle = 'Logs de Validacion'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 1.5rem 0;">Logs de Validacion</h2>

    <!-- Totales -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="data-card" style="text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%;">
            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">Total intentos</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary);"><?= $totales['total'] ?? 0 ?></div>
        </div>
        
        <div class="data-card" style="text-align: center; border: 1px solid rgba(125,255,169,0.2); display: flex; flex-direction: column; justify-content: center; height: 100%;">
            <div style="color: #7dffa9; font-size: 0.9rem; margin-bottom: 0.5rem;">Exitosos</div>
            <div style="font-size: 2rem; font-weight: 700; color: #7dffa9;"><?= $totales['exitosos'] ?? 0 ?></div>
        </div>
        
        <div class="data-card" style="text-align: center; border: 1px solid rgba(255,107,107,0.2); display: flex; flex-direction: column; justify-content: center; height: 100%;">
            <div style="color: #ff6b6b; font-size: 0.9rem; margin-bottom: 0.5rem;">Fallidos</div>
            <div style="font-size: 2rem; font-weight: 700; color: #ff6b6b;"><?= $totales['fallidos'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="data-card" style="margin-bottom: 2rem;">
        <form method="GET" action="/edunexo/admin/logs" style="display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Resultado</label>
                <select name="resultado" class="form-input no-icon" style="width: auto; min-width: 150px; margin-bottom: 0;">
                    <option value="">Todos</option>
                    <option value="exitoso" <?= ($_GET['resultado']??'')==='exitoso'?'selected':'' ?>>Exitoso</option>
                    <option value="fallido" <?= ($_GET['resultado']??'')==='fallido'?'selected':'' ?>>Fallido</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Desde</label>
                <input type="date" name="desde" class="form-input no-icon" style="width: auto; margin-bottom: 0;" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.9rem;">Hasta</label>
                <input type="date" name="hasta" class="form-input no-icon" style="width: auto; margin-bottom: 0;" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
            </div>
            
            <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
                <a href="/edunexo/admin/logs" class="btn-secondary" style="width: auto; text-decoration: none; display: flex; align-items: center; justify-content: center; height: 100%;">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="data-card">
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>CI</th>
                        <th>Telefono intentado</th>
                        <th>Resultado</th>
                        <th>Fecha y hora</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr class="empty-row"><td colspan="5">No hay logs para mostrar.</td></tr>
                <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($l['nombre_completo']) ?></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($l['ci']) ?></td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($l['telefono_intentado']) ?></td>
                        <td>
                            <?php if ($l['resultado']): ?>
                                <span class="chip chip-logrado"><i class="bi bi-check-circle" style="margin-right: 0.25rem;"></i>Exitoso</span>
                            <?php else: ?>
                                <span class="chip chip-noeval"><i class="bi bi-x-circle" style="margin-right: 0.25rem;"></i>Fallido</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-muted);"><?= date('d/m/Y H:i:s', strtotime($l['fecha_intento'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
