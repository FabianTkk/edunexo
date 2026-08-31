<?php
// app/views/auth/login.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Iniciar sesión en EduNexo — Plataforma educativa integral">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar Sesión — EduNexo</title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
</head>
<body class="auth-page">

    <!-- Fondo animado -->
    <div class="auth-bg">
        <div class="auth-bg-orb"></div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Card de login -->
    <div class="auth-card">

        <!-- Branding -->
        <div class="auth-brand">
            <div class="auth-brand-icon">🎓</div>
            <span class="auth-brand-name">EduNexo</span>
        </div>

        <h1 class="auth-title">Bienvenido de nuevo</h1>
        <p class="auth-subtitle">Ingresá tus credenciales para continuar</p>

        <!-- Alertas -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">⚠️</span>
                <span><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert">
                <span class="alert-icon">✅</span>
                <span><?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form class="auth-form" method="POST" action="/edunexo/login" autocomplete="on" novalidate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Usuario -->
            <div class="form-group">
                <label class="form-label" for="username">Usuario</label>
                <div class="form-input-wrap">
                    <input
                        id="username"
                        class="form-input"
                        type="text"
                        name="username"
                        placeholder="Tu nombre de usuario"
                        autocomplete="username"
                        required
                        maxlength="80"
                        spellcheck="false"
                    >
                    <span class="form-input-icon">👤</span>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="form-input-wrap">
                    <input
                        id="password"
                        class="form-input"
                        type="password"
                        name="password"
                        placeholder="Tu contraseña"
                        autocomplete="current-password"
                        required
                        maxlength="128"
                    >
                    <span class="form-input-icon">🔒</span>
                    <button type="button" class="form-input-toggle" id="togglePassword" aria-label="Mostrar contraseña" title="Mostrar/ocultar contraseña">
                        👁️
                    </button>
                </div>
            </div>

            <!-- Botón -->
            <button type="submit" class="btn-primary" id="btn-login">
                <span>Iniciar Sesión</span>
                <span>→</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="auth-footer">
            ¿Primera vez aquí? Contactá al administrador del sistema.
        </div>

    </div>

    <script>
        // ── Partículas ───────────────────────────────────────────────────
        (function() {
            const container = document.getElementById('particles');
            const count = 18;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 4 + 2;
                p.style.cssText = `
                    left: ${Math.random() * 100}%;
                    width: ${size}px;
                    height: ${size}px;
                    animation-duration: ${Math.random() * 20 + 15}s;
                    animation-delay: ${Math.random() * 15}s;
                    opacity: ${Math.random() * 0.5 + 0.1};
                `;
                container.appendChild(p);
            }
        })();

        // ── Toggle contraseña ─────────────────────────────────────────
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('password');
        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', () => {
                const isPass = passInput.type === 'password';
                passInput.type    = isPass ? 'text' : 'password';
                toggleBtn.textContent = isPass ? '🙈' : '👁️';
            });
        }

        // ── Efecto ripple en botón ────────────────────────────────────
        document.getElementById('btn-login').addEventListener('click', function(e) {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => { this.style.transform = ''; }, 150);
        });
    </script>
</body>
</html>
