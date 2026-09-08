    </div><!-- /page-content -->
</main>
</div><!-- /dash-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════
   EduNexo SPA Router
   Carga el contenido via fetch sin recargar sidebar/topbar
   ═══════════════════════════════════════════════════════════ */

const content    = document.getElementById('page-content');
const topbarTitle = document.getElementById('topbar-title');
const loader     = document.getElementById('page-loader');
const navLinks   = document.querySelectorAll('[data-spa]');

// Rutas que son POST (formularios) — no interceptar con SPA
const POST_ROUTES = [
    '/store', '/update', '/toggle', '/bulk', '/single', '/delete', '/logout'
];

function isPostRoute(url) {
    return POST_ROUTES.some(r => url.includes(r));
}

function setActiveLink(url) {
    navLinks.forEach(link => {
        link.classList.remove('active');
        // Comparar el href con la url actual
        const href = link.getAttribute('href');
        if (href && url.includes(href.replace('/edunexo', ''))) {
            link.classList.add('active');
        }
    });
}

async function loadPage(url, pushState = true) {
    if (isPostRoute(url)) return;

    loader.style.display = 'block';

    // Fade out suave
    content.style.opacity = '0';
    content.style.transform = 'translateY(6px)';
    content.style.transition = 'opacity .15s ease, transform .15s ease';

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error('Error ' + res.status);

        const html = await res.text();

        // Parsear el HTML para extraer solo el contenido y el titulo
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Extraer el contenido del wrapper
        const newContent = doc.getElementById('page-content');
        const newTitle   = doc.getElementById('topbar-title');

        if (newContent) {
            content.innerHTML = newContent.innerHTML;
        } else {
            // Fallback: si la respuesta es solo el fragmento
            content.innerHTML = html;
        }

        // Actualizar titulo del topbar
        if (newTitle) {
            topbarTitle.textContent = newTitle.textContent;
        }

        // Actualizar titulo del navegador
        const pageTitle = doc.querySelector('title');
        if (pageTitle) document.title = pageTitle.textContent;

        // Actualizar URL del navegador
        if (pushState) {
            history.pushState({ url }, '', url);
        }

        // Actualizar link activo en sidebar
        setActiveLink(url);

        // Re-inicializar Bootstrap (modales, tooltips) en el nuevo contenido
        reinitBootstrap();

        // Re-adjuntar scripts inline del nuevo contenido
        reinitScripts();

        // Scroll al top del contenido
        content.scrollIntoView({ behavior: 'instant', block: 'start' });

    } catch (err) {
        console.error('SPA load error:', err);
        // Fallback a navegacion normal si algo falla
        window.location.href = url;
    } finally {
        loader.style.display = 'none';
        // Fade in
        content.style.opacity = '1';
        content.style.transform = 'translateY(0)';
    }
}

function reinitBootstrap() {
    // Re-inicializar tooltips
    const tooltips = content.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
}

function reinitScripts() {
    // Re-ejecutar scripts inline del contenido cargado
    const scripts = content.querySelectorAll('script');
    scripts.forEach(oldScript => {
        const newScript = document.createElement('script');
        newScript.textContent = oldScript.textContent;
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
}

// Interceptar clicks en links del sidebar
navLinks.forEach(link => {
    link.addEventListener('click', e => {
        const href = link.getAttribute('href');
        if (!href || isPostRoute(href)) return;
        e.preventDefault();
        loadPage(href);
    });
});

// Manejar boton atras/adelante del navegador
window.addEventListener('popstate', e => {
    if (e.state && e.state.url) {
        loadPage(e.state.url, false);
    }
});

// Guardar estado inicial
history.replaceState({ url: window.location.href }, '', window.location.href);
</script>
</body>
</html>
