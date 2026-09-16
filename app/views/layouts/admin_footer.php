    </div><!-- /page-content -->
</main>
</div><!-- /dash-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════
   EduNexo SPA Router
   ═══════════════════════════════════════════════════════════ */

const content     = document.getElementById('page-content');
const topbarTitle = document.getElementById('topbar-title');
const loader      = document.getElementById('page-loader');
const navLinks    = document.querySelectorAll('[data-spa]');

const POST_ROUTES = ['/store','/update','/toggle','/bulk','/single','/delete','/logout'];

function isPostRoute(url) {
    return POST_ROUTES.some(r => url.includes(r));
}

function setActiveLink(url) {
    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (href && url.includes(href.replace('/edunexo', ''))) {
            link.classList.add('active');
        }
    });
}

async function loadPage(url, pushState = true) {
    if (isPostRoute(url)) return;

    loader.style.display = 'block';
    content.style.opacity = '0';
    content.style.transform = 'translateY(6px)';
    content.style.transition = 'opacity .15s ease, transform .15s ease';

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'  // envia cookies de sesion siempre
        });

        if (!res.ok) throw new Error('Error ' + res.status);

        const html = await res.text();

        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');

        // Detectar si el servidor devolvio la pagina de login.
        // OJO: se compara la clase real del <body> ya parseado (doc.body),
        // NUNCA con html.includes(...) sobre el texto crudo -- este mismo
        // script se incluye en todas las paginas admin, asi que el HTML
        // de CUALQUIER modulo siempre contiene el string 'class="auth-page"'
        // como literal de este archivo, y un test de texto plano matchea
        // consigo mismo siempre (por eso se "cerraba sesion" en todo modulo).
        if (doc.body && doc.body.classList.contains('auth-page')) {
            window.location.href = '/edunexo/login';
            return;
        }

        const newContent = doc.getElementById('page-content');
        const newTitle   = doc.getElementById('topbar-title');

        if (newContent) {
            content.innerHTML = newContent.innerHTML;
        } else {
            content.innerHTML = html;
        }

        if (newTitle) topbarTitle.textContent = newTitle.textContent;

        const pageTitle = doc.querySelector('title');
        if (pageTitle) document.title = pageTitle.textContent;

        if (pushState) history.pushState({ url }, '', url);

        setActiveLink(url);
        reinitBootstrap();
        reinitScripts();

        content.scrollIntoView({ behavior: 'instant', block: 'start' });

    } catch (err) {
        console.error('SPA load error:', err);
        window.location.href = url;
    } finally {
        loader.style.display = 'none';
        content.style.opacity = '1';
        content.style.transform = 'translateY(0)';
    }
}

function reinitBootstrap() {
    const tooltips = content.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
}

function reinitScripts() {
    const scripts = content.querySelectorAll('script');
    scripts.forEach(oldScript => {
        const newScript = document.createElement('script');
        newScript.textContent = oldScript.textContent;
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

// Los módulos contienen scripts propios para modales, búsquedas y formularios.
// Se usa navegación normal para que cada página inicie esos scripts en un contexto
// nuevo y no haya redeclaraciones globales al cambiar de módulo.
</script>
</body>
</html>
