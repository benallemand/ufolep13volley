<?php
/**
 * Reglages appliques AVANT toute ouverture de session (issue #292).
 *
 * A inclure en tete de chaque point d'entree PHP susceptible d'ouvrir une
 * session. Les ~95 appels a `session_start()` disperses dans les classes sont
 * en aval : ils heritent de ces parametres sans avoir a etre touches, du moment
 * que le bootstrap est passe avant.
 *
 * POURQUOI EN PHP ET PAS EN CONFIGURATION
 *
 * Le `php.ini` de la production est gere par OVH. Les deux contournements
 * habituels ne couvrent chacun qu'un seul SAPI :
 *
 *   - `.user.ini`            : lu par PHP-FPM / CGI, ignore par mod_php ;
 *   - `.htaccess php_flag`   : lu par mod_php, provoque une 500 en FPM.
 *
 * Notre image Docker est `php:8.1-apache`, donc mod_php ; OVH mutualise est en
 * FPM. Aucun des deux fichiers ne vaudrait pour les deux environnements, et
 * l'on validerait sur biggyben un comportement different de la production.
 * `session.cookie_httponly` etant `PHP_INI_ALL`, le reglage a l'execution est,
 * lui, identique partout.
 */

if (!function_exists('ufolep_is_https')) {
    /**
     * La requete du VISITEUR est-elle en HTTPS ?
     *
     * Attention, c'est le piege de ce lot : en local comme sur biggyben, Caddy
     * fait `reverse_proxy php:80` et parle a PHP en clair. `$_SERVER['HTTPS']`
     * vaut donc NULL meme quand le visiteur est en HTTPS — verifie sur la
     * stack. Se fier a cette seule variable poserait `secure = false` derriere
     * le proxy ; la forcer a `true` casserait les sessions en dev sur
     * http://localhost. On regarde donc aussi l'en-tete pose par le proxy.
     */
    function ufolep_is_https(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        if ($https !== '' && strtolower($https) !== 'off') {
            return true;
        }
        if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}

if (!defined('UFOLEP_BOOTSTRAPPED')) {
    define('UFOLEP_BOOTSTRAPPED', true);

    // Une session deja ouverte ne peut plus etre reconfiguree : le cookie est
    // parti. Le cas ne devrait pas se produire (le bootstrap est en tete de
    // point d'entree), mais autant ne pas emettre de warning si un ordre
    // d'inclusion change un jour.
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => ufolep_is_https(),
            // Le coeur du correctif : sans lui, un script parvenu a s'executer
            // sur une page du site lit `document.cookie` et emporte la session.
            'httponly' => true,
            // Les navigateurs appliquent deja Lax par defaut ; l'ecrire rend
            // l'intention lisible et ne depend plus de leur bon vouloir.
            'samesite' => 'Lax',
        ]);
    }
}
