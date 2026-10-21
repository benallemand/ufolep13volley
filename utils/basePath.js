import axios from 'axios';

/**
 * Chemin sous lequel le site est servi : '' à la racine d'un domaine (prod
 * OVH, dev, e2e), '/ufolep13volley' derrière le reverse proxy de biggyben, qui
 * retire ce préfixe avant de passer la requête au serveur.
 *
 * Il est déduit de l'adresse du bundle lui-même : Vite range tout le JS sous
 * `assets/` (base relative, voir `vite.config.js`), donc ce qui précède
 * `/assets/` dans l'URL du module est le préfixe. Aucun réglage à faire d'un
 * environnement à l'autre, et le même bundle sert les deux à la fois.
 *
 * Servi par le serveur de dev Vite, le module n'est pas sous `assets/` : on
 * retombe sur la racine.
 */
export const BASE_PATH = (() => {
    const match = new URL(import.meta.url).pathname.match(/^(.*?)\/(?:dist\/)?assets\//);
    return match ? match[1] : '';
})();

// axios préfixe sa baseURL devant toute URL qui n'est pas absolue : les
// quelque 300 appels '/rest/action.php/...' et '/ajax/...' en profitent sans
// être touchés. Une baseURL vide est ignorée, la racine ne change donc pas.
axios.defaults.baseURL = BASE_PATH;

/**
 * Préfixe un chemin du site ('/pages/home.html', '/images/...') par BASE_PATH.
 * Tout le reste (URL absolue, mailto:, chemin relatif, ancre) passe tel quel.
 * @param {string} path
 * @returns {string}
 */
export function url(path) {
    if (typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//')) {
        return path;
    }
    return BASE_PATH + path;
}

/**
 * Plugin Vue : expose `$url()` aux templates de toute l'application, pour les
 * liens et images en dur (`:href="$url('/pages/home.html')"`).
 */
export default {
    install(app) {
        app.config.globalProperties.$url = url;
    },
};
