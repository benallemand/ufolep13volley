import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';

/**
 * Mesure d'audience Matomo, auto-hébergée sur stats.ufolep13volley.org.
 *
 * Injectée au build plutôt que recopiée dans les six pages : les entrées HTML
 * n'ont aucun partiel commun, et six copies d'un snippet dériveraient à la
 * première modification.
 *
 * Trois partis pris :
 *
 *  - **L'administration n'est pas mesurée.** L'usage interne n'a rien à faire
 *    dans des statistiques d'audience publique.
 *
 *  - **Le suivi est servi depuis `stat.js` / `stat.php`**, copies de
 *    `matomo.js` / `matomo.php` faites côté serveur. Les noms d'origine sont
 *    dans toutes les listes de filtrage et coûtent 20 à 30 % des mesures. Des
 *    copies, pas des renommages : Matomo continue d'utiliser ses propres
 *    fichiers en interne et ses mises à jour ne cassent pas.
 *
 *  - **Rien n'est envoyé hors production.** Le dev local et la démo biggyben
 *    servent le même bundle ; sans ce garde-fou ils pollueraient les chiffres.
 *
 * `disableCookies` place la mesure dans les conditions d'exemption de
 * consentement de la CNIL, avec l'anonymisation d'IP réglée côté Matomo : pas
 * de bandeau cookies à afficher.
 */
const MATOMO_URL = 'https://stats.ufolep13volley.org/';
const MATOMO_SITE_ID = '1';
const MATOMO_HOSTS = ['ufolep13volley.org', 'www.ufolep13volley.org'];

function matomoTracking() {
    const snippet = `
var _paq = window._paq = window._paq || [];
if (${JSON.stringify(MATOMO_HOSTS)}.indexOf(location.hostname) !== -1) {
    _paq.push(['disableCookies']);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function () {
        var u = ${JSON.stringify(MATOMO_URL)};
        _paq.push(['setTrackerUrl', u + 'stat.php']);
        _paq.push(['setSiteId', ${JSON.stringify(MATOMO_SITE_ID)}]);
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.async = true; g.src = u + 'stat.js'; s.parentNode.insertBefore(g, s);
    })();
}`.trim();

    return {
        name: 'matomo-tracking',
        transformIndexHtml: {
            order: 'post',
            handler(html, ctx) {
                // ctx.filename est absolu (antislashs sous Windows), ctx.path
                // relatif : on normalise avant de tester.
                const file = String(ctx.filename || ctx.path || '').replace(/\\/g, '/');
                if (file.includes('/admin/')) return html;
                return {
                    html,
                    tags: [{ tag: 'script', children: snippet, injectTo: 'head' }],
                };
            },
        },
    };
}

/**
 * Multi-page app : on déclare ici toutes les entrées qui doivent être bundlées.
 *
 * Toutes les pages sont des entrées HTML natives : Vite parse le HTML, bundle
 * les <script type="module"> et <link rel="stylesheet"> qu'il trouve, et
 * produit un dist/.../page.html avec les scripts/links remplacés par les
 * bundles hashés. Apache .htaccess rewrite ces URLs publiques vers dist/.
 * (Plus aucune page n'est servie par PHP — issue #228.)
 */
const entries = {
    // CSS global (Tailwind + DaisyUI + libs tierces)
    'css/app': 'src/css/app.css',

    // Pages .html (entrées HTML natives, Vite remplace les scripts inline).
    // Plus aucune page n'est servie par PHP : le PHP ne répond qu'à l'API REST
    // (/rest/) et aux endpoints AJAX (issue #228).
    'live':          'live.html',
    'match':         'match.html',
    'survey':        'survey.html',
    'team_sheets':   'team_sheets.html',
    'pages/home':    'pages/home.html',
    'pages/my_page': 'pages/my_page.html',
    'admin/matches': 'admin/matches.html',
    'admin/index':   'admin/index.html',
};

export default defineConfig({
    root: __dirname,
    base: '/dist/',
    plugins: [vue(), matomoTracking()],
    resolve: {
        alias: {
            // Notre code utilise des templates en string (`template: '...'`) dans
            // les composants Options API. Il faut donc le build "esm-bundler"
            // qui inclut le compilateur de templates runtime, pas le runtime-only.
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: Object.fromEntries(
                Object.entries(entries).map(([name, file]) => [name, resolve(__dirname, file)])
            ),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        origin: 'http://localhost:5173',
    },
});
