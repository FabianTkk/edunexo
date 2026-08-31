<?php
// app/views/auth/register.php
// URL discreta — accesible en /edunexo/register — no linkada desde el login
$oldData = $_SESSION['reg_old'] ?? [];
unset($_SESSION['reg_old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crear cuenta en EduNexo">
    <meta name="robots" content="noindex, nofollow">
    <title>Crear Cuenta — EduNexo</title>
    <link rel="stylesheet" href="/edunexo/public/css/style.css">
</head>
<body class="auth-page">

    <!-- Fondo animado -->
    <div class="auth-bg">
        <div class="auth-bg-orb"></div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Card de registro -->
    <div class="auth-card" style="max-width: 480px;">

        <!-- Branding -->
        <div class="auth-brand">
            <div class="auth-brand-icon">🎓</div>
            <span class="auth-brand-name">EduNexo</span>
        </div>

        <h1 class="auth-title">Crear una cuenta</h1>
        <p class="auth-subtitle">Completá los datos para registrarte en la plataforma</p>

        <!-- Alertas -->
        <?php if (isset($_SESSION['reg_error'])): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon">⚠️</span>
                <span><?php echo $_SESSION['reg_error']; unset($_SESSION['reg_error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario de registro -->
        <form class="auth-form" method="POST" action="/edunexo/register" autocomplete="on" novalidate id="reg-form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Nombre completo -->
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre completo</label>
                <div class="form-input-wrap">
                    <input
                        id="nombre"
                        class="form-input"
                        type="text"
                        name="nombre"
                        placeholder="Ej: María García"
                        autocomplete="name"
                        required
                        maxlength="100"
                        value="<?php echo htmlspecialchars($oldData['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <span class="form-input-icon">✏️</span>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico</label>
                <div class="form-input-wrap">
                    <input
                        id="email"
                        class="form-input"
                        type="email"
                        name="email"
                        placeholder="tu@email.com"
                        autocomplete="email"
                        required
                        maxlength="150"
                        value="<?php echo htmlspecialchars($oldData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <span class="form-input-icon">📧</span>
                </div>
            </div>

            <!-- Nombre de usuario -->
            <div class="form-group">
                <label class="form-label" for="usuario">Nombre de usuario</label>
                <div class="form-input-wrap">
                    <input
                        id="usuario"
                        class="form-input"
                        type="text"
                        name="usuario"
                        placeholder="Ej: mgarcia_2025"
                        autocomplete="username"
                        required
                        minlength="3"
                        maxlength="30"
                        pattern="^[a-zA-Z0-9_]+$"
                        value="<?php echo htmlspecialchars($oldData['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        spellcheck="false"
                    >
                    <span class="form-input-icon">👤</span>
                </div>
                <small style="font-size:.74rem;color:var(--text-muted)">Solo letras, números y guiones bajos. Entre 3 y 30 caracteres.</small>
            </div>

            <div class="auth-divider">contraseña</div>

            <!-- Contraseña -->
            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="form-input-wrap">
                    <input
                        id="password"
                        class="form-input"
                        type="password"
                        name="password"
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password"
                        required
                        minlength="8"
                        maxlength="128"
                        id="password"
                    >
                    <span class="form-input-icon">🔒</span>
                    <button type="button" class="form-input-toggle" id="togglePass1" aria-label="Mostrar contraseña">👁️</button>
                </div>
                <!-- Indicador de fuerza -->
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <!-- Confirmar contraseña -->
            <div class="form-group">
                <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                <div class="form-input-wrap">
                    <input
                        id="password_confirm"
                        class="form-input"
                        type="password"
                        name="password_confirm"
                        placeholder="Repetí tu contraseña"
                        autocomplete="new-password"
                        required
                        maxlength="128"
                    >
                    <span class="form-input-icon">🔐</span>
                    <button type="button" class="form-input-toggle" id="togglePass2" aria-label="Mostrar contraseña">👁️</button>
                </div>
                <div id="match-msg" style="font-size:.75rem;margin-top:.2rem;"></div>
            </div>

            <!-- Botón -->
            <button type="submit" class="btn-primary" id="btn-register">
                <span>Crear Cuenta</span>
                <span>🚀</span>
            </button>
        </form>

        <!-- Footer — link al login -->
        <div class="auth-footer">
            ¿Ya tenés cuenta? <a href="/edunexo/login">Iniciá sesión</a>
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

        // ── Toggle contraseñas ────────────────────────────────────────
        function addToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const inp = document.getElementById(inputId);
            if (btn && inp) {
                btn.addEventListener('click', () => {
                    const isPass = inp.type === 'password';
                    inp.type = isPass ? 'text' : 'password';
                    btn.textContent = isPass ? '🙈' : '👁️';
                });
            }
        }
        addToggle('togglePass1', 'password');
        addToggle('togglePass2', 'password_confirm');

        // ── Indicador de fuerza de contraseña ────────────────────────
        const passInput  = document.getElementById('password');
        const strengthBar = document.getElementById('strength-bar');
        const strengthLabel = document.getElementById('strength-label');

        passInput.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 8)                score++;
            if (/[A-Z]/.test(val))              score++;
            if (/[0-9]/.test(val))              score++;
            if (/[^A-Za-z0-9]/.test(val))       score++;

            strengthBar.className = 'password-strength-bar';
            if (val.length === 0) {
                strengthBar.style.width = '0';
                strengthLabel.textContent = '';
            } else if (score <= 1) {
                strengthBar.classList.add('strength-weak');
                strengthLabel.style.color = 'var(--error)';
                strengthLabel.textContent = '⚠ Contraseña débil';
            } else if (score === 2 || score === 3) {
                strengthBar.classList.add('strength-medium');
                strengthLabel.style.color = 'var(--warning)';
                strengthLabel.textContent = '● Contraseña media';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthLabel.style.color = 'var(--success)';
                strengthLabel.textContent = '✓ Contraseña fuerte';
            }
        });

        // ── Validación de coincidencia de contraseñas ─────────────────
        const confirmInput = document.getElementById('password_confirm');
        const matchMsg = document.getElementById('match-msg');

        function checkMatch() {
            if (!confirmInput.value) { matchMsg.textContent = ''; return; }
            if (passInput.value === confirmInput.value) {
                matchMsg.textContent = '✓ Las contraseñas coinciden';
                matchMsg.style.color = 'var(--success)';
            } else {
                matchMsg.textContent = '✗ Las contraseñas no coinciden';
                matchMsg.style.color = 'var(--error)';
            }
        }

        passInput.addEventListener('input', checkMatch);
        confirmInput.addEventListener('input', checkMatch);

        // ── Validación usuario (solo chars válidos) ───────────────────
        const usuarioInput = document.getElementById('usuario');
        usuarioInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });
    </script>
</body>
</html>
