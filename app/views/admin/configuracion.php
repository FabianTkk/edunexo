<?php $pageTitle = 'Configuracion'; require __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <h2 class="fw-bold mb-1">Configuracion del Sistema</h2>
    <p class="text-muted mb-4">Estos datos afectan el envio de todos los mensajes del sistema.</p>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

    <div class="card bs-card rounded-4 shadow-sm" style="max-width:600px;">
        <div class="card-body p-4">
            <form method="POST" action="/edunexo/admin/configuracion/update">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-4">
                    <label class="form-label text-light fw-semibold">Nombre del Colegio</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="nombre_colegio"
                           value="<?= htmlspecialchars($config['nombre_colegio'] ?? '') ?>" required placeholder="Ej: Colegio Nacional San Jose">
                </div>

                <div class="mb-4">
                    <label class="form-label text-light fw-semibold">Telefono WhatsApp Remitente</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="telefono_wa_remitente"
                           value="<?= htmlspecialchars($config['telefono_wa_remitente'] ?? '') ?>" required placeholder="595981234567">
                    <div class="form-text text-secondary">Formato: 595 + 9 digitos.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-light fw-semibold">Instancia Evolution API</label>
                    <input type="text" class="form-control bg-dark text-light border-secondary" name="instancia_evolution"
                           value="<?= htmlspecialchars($config['instancia_evolution'] ?? '') ?>" placeholder="edunexo">
                    <div class="form-text text-secondary">Nombre de la instancia configurada en Evolution API.</div>
                </div>

                <div class="alert alert-warning bg-opacity-10 border-warning text-warning small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Cambiar el telefono o la instancia afecta el envio de todos los mensajes de WhatsApp.
                </div>

                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-floppy-fill me-1"></i> Guardar Configuracion
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/admin_footer.php'; ?>
