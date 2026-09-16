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
                       value="<?= htmlspecialchars($config['nombre_colegio'] ?? '') ?>"
                       required placeholder="Ej: Centro Educativo La Amistad">
            </div>

            <div class="form-group">
                <label class="form-label">Telefono WhatsApp Remitente</label>
                <input type="text" class="form-input no-icon" name="telefono_wa_remitente"
                       value="<?= htmlspecialchars($config['telefono_wa_remitente'] ?? '') ?>"
                       required placeholder="595981234567">
                <div style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.4rem;">
                    Formato: 595 + 9 digitos. Ej: 595981234567
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Instancia Evolution API</label>
                <input type="text" class="form-input no-icon" name="instancia_evolution"
                       value="<?= htmlspecialchars($config['instancia_evolution'] ?? '') ?>"
                       placeholder="edunexo">
                <div style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.4rem;">
                    Nombre de la instancia configurada en Evolution API.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Directorio de Evolution API</label>
                <input type="text" class="form-input no-icon" name="directorio_evolution"
                       value="<?= htmlspecialchars($config['directorio_evolution'] ?? 'C:\laragon\www\evolution-api') ?>"
                       placeholder="C:\laragon\www\evolution-api" required>
                <div style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.4rem;">
                    Ruta completa a la carpeta de Evolution API en este equipo.
                </div>
            </div>

            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <span>Cambiar el telefono o la instancia afecta el envio de todos los mensajes de WhatsApp.</span>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0;">
                <i class="bi bi-floppy-fill" style="margin-right: 0.5rem;"></i> Guardar Configuracion
            </button>
        </form>
    </div>

    <!-- Instrucciones Evolution API -->
    <div class="data-card" style="max-width: 600px; margin-top: 0; border-color: hsla(350, 60%, 42%, 0.3);">
        <div style="display: flex; align-items: flex-start; gap: 1rem;">
            <div style="font-size: 1.8rem; flex-shrink: 0;">⚡</div>
            <div>
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.4rem 0;">
                    Como iniciar Evolution API
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0 0 0.75rem 0;">
                    Para iniciar el servidor de WhatsApp, ejecuta el archivo
                    <strong style="color: var(--accent);">iniciar-evolution.bat</strong>
                    con doble click desde la carpeta de Evolution API.
                </p>
                <div style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 0.6rem 1rem; font-size: 0.8rem; color: var(--text-secondary); font-family: monospace; word-break: break-all;">
                    <?= htmlspecialchars(($config['directorio_evolution'] ?? 'C:\laragon\www\evolution-api') . '\iniciar-evolution.bat') ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
