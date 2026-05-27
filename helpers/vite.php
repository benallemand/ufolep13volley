<?php
/**
 * Helper d'intégration Vite côté PHP.
 *
 * Deux modes :
 *
 *  - Mode dev (variable d'env APP_ENV=dev) : on pointe les <script>/<link>
 *    vers le dev server Vite (par défaut http://localhost:5173). HMR actif.
 *
 *  - Mode prod (défaut) : on lit `dist/.vite/manifest.json` produit par
 *    `npm run build` et on émet les <script>/<link> avec les fichiers hashés.
 *
 * Usage type dans une page PHP :
 *
 *     <?php require_once __DIR__ . '/../helpers/vite.php'; ?>
 *     <head>
 *         <?= vite_assets(['pages/home.js', 'css/app.css']) ?>
 *     </head>
 */

if (!function_exists('vite_is_dev')) {
    function vite_is_dev(): bool
    {
        $env = getenv('APP_ENV');
        if ($env === false) {
            $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        }
        return $env === 'dev';
    }
}

if (!function_exists('vite_dev_server_url')) {
    function vite_dev_server_url(): string
    {
        $url = getenv('VITE_DEV_SERVER_URL');
        if ($url === false || $url === '') {
            $url = $_ENV['VITE_DEV_SERVER_URL'] ?? $_SERVER['VITE_DEV_SERVER_URL'] ?? 'http://localhost:5173';
        }
        return rtrim($url, '/');
    }
}

if (!function_exists('vite_manifest')) {
    function vite_manifest(): array
    {
        static $manifest = null;
        if ($manifest !== null) {
            return $manifest;
        }
        $path = __DIR__ . '/../dist/.vite/manifest.json';
        if (!is_file($path)) {
            // Pas encore buildé : on retourne un manifest vide. Les balises émises
            // pointeront alors vers /dist/{entry} en best-effort pour ne pas casser
            // les pages en dev sans Vite.
            $manifest = [];
            return $manifest;
        }
        $raw = file_get_contents($path);
        $manifest = json_decode($raw, true) ?: [];
        return $manifest;
    }
}

if (!function_exists('vite_asset')) {
    /**
     * Émet la ou les balises HTML correspondant à une entrée Vite.
     *
     * En dev : pointe vers le dev server (HMR).
     * En prod : utilise le manifest.json (fichiers hashés + CSS associés).
     */
    function vite_asset(string $entry): string
    {
        if (vite_is_dev()) {
            $base = vite_dev_server_url();
            $html = '<script type="module" src="' . htmlspecialchars($base . '/@vite/client', ENT_QUOTES) . '"></script>' . "\n";
            $html .= '<script type="module" src="' . htmlspecialchars($base . '/' . ltrim($entry, '/'), ENT_QUOTES) . '"></script>';
            return $html;
        }

        $manifest = vite_manifest();
        if (!isset($manifest[$entry])) {
            // Fallback : on émet une référence directe pour aider au debug.
            return '<!-- vite_asset: entrée "' . htmlspecialchars($entry, ENT_QUOTES) . '" absente du manifest -->';
        }

        $info = $manifest[$entry];
        $html = '';

        // CSS importé par l'entrée JS
        if (!empty($info['css'])) {
            foreach ($info['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/dist/' . htmlspecialchars($cssFile, ENT_QUOTES) . '">' . "\n";
            }
        }

        $file = $info['file'] ?? null;
        if ($file === null) {
            return $html;
        }

        // Entrée CSS pure
        if (str_ends_with($file, '.css')) {
            $html .= '<link rel="stylesheet" href="/dist/' . htmlspecialchars($file, ENT_QUOTES) . '">';
            return $html;
        }

        // Entrée JS
        $html .= '<script type="module" src="/dist/' . htmlspecialchars($file, ENT_QUOTES) . '"></script>';

        // Modules importés (preload pour limiter les waterfalls réseau)
        if (!empty($info['imports'])) {
            foreach ($info['imports'] as $importedKey) {
                if (!isset($manifest[$importedKey]['file'])) {
                    continue;
                }
                $importedFile = $manifest[$importedKey]['file'];
                $html .= "\n" . '<link rel="modulepreload" href="/dist/' . htmlspecialchars($importedFile, ENT_QUOTES) . '">';
            }
        }

        return $html;
    }
}

if (!function_exists('vite_assets')) {
    /**
     * Émet plusieurs entrées d'un coup.
     *
     * @param string[] $entries
     */
    function vite_assets(array $entries): string
    {
        return implode("\n", array_map('vite_asset', $entries));
    }
}
