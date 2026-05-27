import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';

/**
 * Multi-page app : on déclare ici toutes les entrées qui doivent être bundlées.
 *
 * Deux types d'entrées :
 *
 *  1. Fichiers .js purs (pour les pages .php existantes) — Vite produit un
 *     fichier hashé dans dist/assets/ et une entrée dans manifest.json. La
 *     page .php utilise le helper PHP `vite_asset()` pour émettre la bonne
 *     balise <script> avec le hash courant.
 *
 *  2. Fichiers .html (pour les pages HTML pures) — Vite parse le HTML, bundle
 *     les <script type="module"> et <link rel="stylesheet"> qu'il trouve, et
 *     produit un dist/.../page.html avec les scripts/links remplacés par les
 *     bundles hashés. Apache .htaccess rewrite ces URLs vers dist/.
 */
const entries = {
    // CSS global (Tailwind + DaisyUI + libs tierces)
    'css/app': 'src/css/app.css',

    // Pages .php (entrées JS, chargées via vite_asset() côté PHP)
    'live':        'live.js',
    'match':       'match.js',
    'survey':      'survey.js',
    'team_sheets': 'team_sheets.js',

    // Pages .html (entrées HTML natives, Vite remplace les scripts inline)
    'pages/home':    'pages/home.html',
    'pages/my_page': 'pages/my_page.html',
    'admin/matches': 'admin/matches.html',
};

export default defineConfig({
    root: __dirname,
    base: '/dist/',
    plugins: [vue()],
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
