<?php $pageTitle = 'Reportes por CI'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <a href="/edunexo/admin/reportes" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>
    <h2 class="fw-bold mb-4">Reportes — CI: <?= htmlspecialchars($_GET['ci'] ?? '') ?></h2>

    <?php if (empty($reportes)): ?>
        <div class="text-center py-5 text-muted">No se encontraron reportes para ese CI.</div>
    <?php else: ?>
    <div class="accordion" id="accReportes">
        <?php foreach ($reportes as $i => $r): ?>
        <div class="accordion-item bg-transparent border border-secondary border-opacity-25 mb-2 rounded-3 overflow-hidden">
            <h2 class="accordion-header">
                <button class="accordion-button <?= $i>0?'collapsed':'' ?> bg-dark text-light" type="button" data-bs-toggle="collapse" data-bs-target="#rep<?= $i ?>">
                    <span class="fw-semibold me-3"><?= htmlspecialchars($r['nombre_completo']) ?></span>
                    <span class="text-muted small">Semana: <?= date('d/m/Y', strtotime($r['periodo_semana'])) ?></span>
                    <?php if ($r['dias_ausente'] > 0): ?>
                        <span class="badge bg-warning bg-opacity-25 text-warning ms-3"><?= $r['dias_ausente'] ?> ausencias</span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="rep<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.05)">
                                <div class="text-muted small mb-1">Comportamiento</div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['comportamiento']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.05)">
                                <div class="text-muted small mb-1">Dias ausente</div>
                                <div class="fw-semibold"><?= $r['dias_ausente'] ?></div>
                            </div>
                        </div>
                        <?php if (!empty($r['materia_nombre'])): ?>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.05)">
                                <div class="text-muted small mb-1"><?= htmlspecialchars($r['materia_nombre']) ?> — <?= htmlspecialchars($r['tipo_nombre'] ?? '') ?></div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['eval_titulo'] ?? '') ?> — Nota: <?= htmlspecialchars($r['puntaje_obtenido'] ?? '—') ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($r['incidentes_disciplinarios'])): ?>
                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background:rgba(255,255,255,.05)">
                                <div class="text-muted small mb-1">Observaciones</div>
                                <div><?= htmlspecialchars($r['incidentes_disciplinarios']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
