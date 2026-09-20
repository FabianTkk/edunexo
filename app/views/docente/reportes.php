<?php
$pageTitle = 'Mis reportes';
require __DIR__ . '/../layouts/docente_header.php';
$editando = !empty($reporteEdicion);
?>

<div style="padding-top:1rem">
    <div style="margin-bottom:1.5rem"><h2><?= $editando ? 'Editar reporte' : 'Mis reportes' ?></h2><p style="color:var(--text-muted)">Podés editar o eliminar un reporte durante las primeras 48 horas.</p></div>
    <?php foreach(['success'=>'alert-success','error'=>'alert-error'] as $k=>$c): if(isset($_SESSION[$k])): ?><div class="alert <?= $c ?>"><?= htmlspecialchars($_SESSION[$k]); unset($_SESSION[$k]); ?></div><?php endif; endforeach; ?>

    <form method="POST" class="data-card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
        <?php if ($editando): ?><input type="hidden" name="reporte_id" value="<?= (int)$reporteEdicion['id'] ?>"><?php endif; ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem">
            <div class="form-group"><label class="form-label">Estudiante</label><select class="form-input no-icon" name="estudiante_id" required><option value="">Seleccionar</option><?php foreach($estudiantes as $e): ?><option value="<?= (int)$e['id'] ?>" <?= $editando && (int)$e['id']===(int)$reporteEdicion['estudiante_id']?'selected':'' ?>><?= htmlspecialchars($e['nombre_completo'].' — '.$e['curso']) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label class="form-label">Semana (cualquier día; se toma el lunes)</label><input class="form-input no-icon" type="date" name="periodo_semana" value="<?= htmlspecialchars($editando ? $reporteEdicion['periodo_semana'] : date('Y-m-d', strtotime('monday this week'))) ?>" required></div>
            <div class="form-group"><label class="form-label">Calificación</label><select class="form-input no-icon" name="calificacion_general"><?php foreach(['Logrado','En Proceso','Aun no logrado','No evaluado'] as $op): ?><option <?= ($editando ? $reporteEdicion['calificacion_general'] : 'Logrado')===$op?'selected':'' ?>><?= $op ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label class="form-label">Comportamiento</label><select class="form-input no-icon" name="comportamiento"><?php foreach(['Excelente','Bueno','Regular','Requiere Atencion'] as $op): ?><option <?= ($editando ? $reporteEdicion['comportamiento'] : 'Bueno')===$op?'selected':'' ?>><?= $op ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label class="form-label">Ausencias</label><input class="form-input no-icon" type="text" value="Se calculan desde la asistencia" disabled></div>
            <div class="form-group"><label class="form-label">Tareas incompletas</label><input class="form-input no-icon" type="number" min="0" name="tareas_incompletas" value="<?= (int)($editando ? $reporteEdicion['tareas_incompletas'] : 0) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Incidentes / observaciones</label><textarea class="form-input no-icon" name="incidentes"><?= htmlspecialchars($editando ? $reporteEdicion['incidentes_disciplinarios'] : '') ?></textarea></div>
        <div style="display:flex;gap:.75rem;align-items:center"><button class="btn-primary" style="width:auto"><?= $editando ? 'Guardar cambios' : 'Crear reporte' ?></button><?php if($editando): ?><a href="/edunexo/docente/reportes" class="btn-secondary">Cancelar</a><?php endif; ?></div>
    </form>

    <div class="data-card"><div class="data-card-header"><span class="data-card-title">Historial de mis reportes</span></div><div style="overflow-x:auto"><table class="tbl"><thead><tr><th>Estudiante</th><th>Semana</th><th>Calificación</th><th>Ausencias</th><th>Creado</th><th>Acciones</th></tr></thead><tbody>
        <?php if (!$reportes): ?><tr class="empty-row"><td colspan="6">Aún no creaste reportes.</td></tr><?php else: foreach($reportes as $r): $puedeEditar=(bool)$r['editable']; ?><tr>
            <td style="color:var(--text-primary);font-weight:600"><?= htmlspecialchars($r['nombre_completo']) ?></td><td><?= date('d/m/Y',strtotime($r['periodo_semana'])) ?></td><td><?= htmlspecialchars($r['calificacion_general']) ?></td><td><?= (int)($r['ausencias_reales'] ?? $r['dias_ausente']) ?></td><td><?= date('d/m/Y H:i',strtotime($r['created_at'])) ?></td>
            <td style="white-space:nowrap"><?php if($puedeEditar): ?><a class="btn-secondary btn-sm" href="/edunexo/docente/reportes?editar=<?= (int)$r['id'] ?>">Editar</a><form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este reporte?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="reporte_id" value="<?= (int)$r['id'] ?>"><button class="btn-secondary btn-sm" style="color:#ff9a9a">Eliminar</button></form><?php elseif(!empty($r['solicitud_pendiente'])): ?><span class="chip chip-proceso">Solicitud pendiente</span><?php else: ?><form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="accion" value="solicitar_cambio"><input type="hidden" name="reporte_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="motivo" value="Solicitud de modificación fuera del plazo de 48 horas."><button class="btn-secondary btn-sm">Solicitar cambio</button></form><?php endif; ?></td>
        </tr><?php endforeach; endif; ?></tbody></table></div></div>
</div>
<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
