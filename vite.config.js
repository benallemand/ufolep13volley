import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';

/**
 * Multi-page app : on déclare ici toutes les entrées qui ont besoin d'être
 * bundlées. Chaque entrée produit un fichier hashé dans dist/ + une entrée
 * dans dist/.vite/manifest.json que le helper PHP vite_asset() utilise pour
 * émettre les <script>/<link> dans les <head> des pages PHP.
 *
 * Pour PR 1 (outillage) on ne bundle que l'entrée CSS — ça valide la chaîne
 * Tailwind/PostCSS/DaisyUI et permet aux pages converties à partir de PR 3
 * d'avoir leurs styles. Les entrées JS seront ajoutées une par une au fur et
 * à mesure que les pages sont migrées Vue 2 → Vue 3 + bundlées.
 *
 * Entrées JS à ajouter dans PR 3 (template — décommenter au fil de l'eau) :
 *
 *   'pages/home':    'pages/home.js',
 *   'pages/my_page': 'pages/my_page.js',
 *   'live':          'live.js',
 *   'match':         'match.js',
 *   'survey':        'survey.js',
 *   'team_sheets':   'team_sheets.js',
 *   'admin/matches': 'admin/matches.js',
 */
const entries = {
    'css/app': 'src/css/app.css',
};

export default defineConfig({
    root: __dirname,
    base: '/dist/',
    plugins: [vue()],
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
