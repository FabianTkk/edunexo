<?php $pageTitle = 'Reportes por CI'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<style>
.custom-accordion-item {
    background: var(--bg-card);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
    overflow: hidden;
}
.custom-accordion-header {
    padding: 1rem 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    background: transparent;
    transition: background 0.2s;
}
.custom-accordion-header:hover {
    background: rgba(255,255,255,0.05);
}
.custom-accordion-content {
    display: none;
    padding: 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
}
.custom-accordion-content.show {
    display: block;
}
.stat-box {
    background: rgba(255,255,255,0.05);
    padding: 1rem;
    border-radius: 0.5rem;
    height: 100%;
}
.stat-box-label {
    color: var(--text-muted);
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}
.stat-box-value {
    font-weight: 600;
    color: var(--text-primary);
}
</style>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <a href="/edunexo/admin/reportes" class="btn-secondary btn-sm" style="display: inline-flex; width: auto; margin-bottom: 1.5rem;">
        <i class="bi bi-arrow-left" style="margin-right: 0.5rem;"></i> Volver
    </a>
    
    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 1.5rem 0;">Reportes — CI: <?= htmlspecialchars($_GET['ci'] ?? '') ?></h2>

    <?php if (empty($reportes)): ?>
        <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">No se encontraron reportes para ese CI.</div>
    <?php else: ?>
    <div>
        <?php foreach ($reportes as $i => $r): ?>
        <div class="custom-accordion-item">
            <div class="custom-accordion-header" onclick="toggleAccordion('rep<?= $i ?>')">
                <span style="font-weight: 600; margin-right: 1.5rem; color: var(--text-primary); flex: 1;">
                    <?= htmlspecialchars($r['nombre_completo']) ?>
                </span>
                <span style="color: var(--text-muted); font-size: 0.9rem;">
                    Semana: <?= date('d/m/Y', strtotime($r['periodo_semana'])) ?>
                </span>
                <?php if ($r['dias_ausente'] > 0): ?>
                    <span class="chip chip-noeval" style="margin-left: 1rem;"><?= $r['dias_ausente'] ?> ausencias</span>
                <?php endif; ?>
                <i class="bi bi-chevron-down" style="margin-left: 1rem; color: var(--text-muted); transition: transform 0.3s;" id="icon-rep<?= $i ?>"></i>
            </div>
            
            <div id="rep<?= $i ?>" class="custom-accordion-content <?= $i === 0 ? 'show' : '' ?>">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="stat-box">
                        <div class="stat-box-label">Comportamiento</div>
                        <div class="stat-box-value"><?= htmlspecialchars($r['comportamiento']) ?></div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-box-label">Días ausente</div>
                        <div class="stat-box-value"><?= $r['dias_ausente'] ?></div>
                    </div>
                    
                    <?php if (!empty($r['materia_nombre'])): ?>
                    <div class="stat-box">
                        <div class="stat-box-label"><?= htmlspecialchars($r['materia_nombre']) ?> — <?= htmlspecialchars($r['tipo_nombre'] ?? '') ?></div>
                        <div class="stat-box-value"><?= htmlspecialchars($r['eval_titulo'] ?? '') ?> — Nota: <?= htmlspecialchars($r['puntaje_obtenido'] ?? '—') ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($r['incidentes_disciplinarios'])): ?>
                <div class="stat-box">
                    <div class="stat-box-label">Observaciones</div>
                    <div class="stat-box-value" style="font-weight: normal; line-height: 1.5;"><?= nl2br(htmlspecialchars($r['incidentes_disciplinarios'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    // Initialize first accordion icon if it's open
    window.addEventListener('DOMContentLoaded', () => {
        const firstIcon = document.getElementById('icon-rep0');
        if (firstIcon) firstIcon.style.transform = 'rotate(180deg)';
    });

    function toggleAccordion(id) {
        const content = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        
        if (content.classList.contains('show')) {
            content.classList.remove('show');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.classList.add('show');
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
