<?php
/**
 * Router pour le serveur PHP intégré
 * Bloque l'accès aux fichiers sensibles
 * 
 * Usage: php -S 0.0.0.0:8080 -t C:\Users\benal\PhpstormProjects\ufolep13volley router.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Liste des patterns de fichiers à bloquer
$blockedPatterns = [
    '/^\/\.env/',           // .env, .env.local, .env.prod, etc.
    '/^\/\.git/',           // .git directory
    '/^\/\.htaccess/',      // .htaccess
    '/^\/\.htpasswd/',      // .htpasswd
    '/^\/composer\.json/',  // composer.json
    '/^\/composer\.lock/',  // composer.lock
    '/^\/composer\.phar/',  // composer.phar
    '/^\/docker-compose/',  // docker-compose.yml
    '/^\/Dockerfile/',      // Dockerfile
    '/\.sql$/',             // fichiers SQL
    '/\.log$/',             // fichiers log
    '/\.bak$/',             // fichiers backup
    '/^\/vendor\//',        // vendor directory (sauf si nécessaire)
    '/^\/\.idea\//',        // IDE config
    '/^\/\.run\//',         // Run configurations
];

foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $path)) {
        http_response_code(403);
        echo "403 Forbidden - Access Denied";
        exit;
    }
}

// Laisser le serveur PHP gérer normalement les autres requêtes
return false;
