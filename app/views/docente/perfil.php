<?php
$pageTitle = 'Mi Perfil';
require __DIR__ . '/../layouts/docente_header.php';
?>

<div class="welcome-banner"><div class="welcome-text"><h1>Mi perfil</h1><p>Mantene actualizados tus datos de contacto.</p></div><div class="welcome-emoji"><i class="bi bi-person-circle"></i></div></div>
<?php if (!empty($_SESSION['success']) || !empty($_SESSION['error'])): ?><div class="alert <?= !empty($_SESSION['error']) ? 'alert-error' : 'alert-success' ?>"><?= htmlspecialchars($_SESSION['error'] ?? $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success'], $_SESSION['error']); ?></div><?php endif; ?>
<div class="data-card" style="max-width:720px"><div class="data-card-header"><span class="data-card-title">Datos de la cuenta</span></div>
<form method="POST" action="/edunexo/docente/perfil" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" value="<?= htmlspecialchars($perfil['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Usuario</label><input class="form-control" value="<?= htmlspecialchars($perfil['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly></div>
    <div class="col-12"><label class="form-label">Correo electronico</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($perfil['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-12"><button class="btn btn-primary" style="width:auto;margin:0"><i class="bi bi-check-lg"></i> Guardar cambios</button></div>
</form></div>
<?php require __DIR__ . '/../layouts/docente_footer.php'; ?>
