<?php $pageTitle = 'Configuracion'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div style="margin-bottom: 2rem; padding-top: 1rem;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem 0;">Configuracion del Sistema</h2>
        <p style="color: var(--text-muted); margin: 0;">Estos datos afectan el envio de todos los mensajes del sistema.</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); endif; ?>
        
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

    <div class="data-card" style="max-width: 600px;">
        <form method="POST" action="/edunexo/admin/configuracion/update">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Nombre del Colegio</label>
                <input type="text" class="form-input no-icon" name="nombre_colegio"
                       value="<?= htmlspecialchars($config['nombre_colegio'] ?? '') ?>" required placeholder="Ej: Colegio Nacional San Jose">
            </div>

            <div class="form-group">
                <label class="form-label">Telefono WhatsApp Remitente</label>
                <input type="text" class="form-input no-icon" name="telefono_wa_remitente"
                       value="<?= htmlspecialchars($config['telefono_wa_remitente'] ?? '') ?>" required placeholder="595981234567">
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Formato: 595 + 9 digitos.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Instancia Evolution API</label>
                <input type="text" class="form-input no-icon" name="instancia_evolution"
                       value="<?= htmlspecialchars($config['instancia_evolution'] ?? '') ?>" placeholder="edunexo">
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Nombre de la instancia configurada en Evolution API.</div>
            </div>

            <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <span>Cambiar el telefono o la instancia afecta el envio de todos los mensajes de WhatsApp.</span>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0;">
                <i class="bi bi-floppy-fill" style="margin-right: 0.5rem;"></i> Guardar Configuracion
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
