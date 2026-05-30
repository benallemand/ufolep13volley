import axios from 'axios';

/**
 * Garde d'authentification/autorisation côté client.
 *
 * Remplace les contrôles d'accès qui étaient auparavant faits en PHP en tête
 * des pages live.php / match.php / survey.php / team_sheets.php. Le but est que
 * ces pages deviennent du HTML statique pur : toute la logique d'accès passe
 * désormais par l'API REST (/rest/action.php/...).
 *
 * NB : ces gardes sont de l'UX (rediriger l'utilisateur non autorisé). La
 * vraie sécurité reste assurée côté backend par les endpoints REST eux-mêmes
 * (lecture ET écriture vérifient le profil / l'appartenance à l'équipe).
 */

/**
 * Récupère l'utilisateur connecté, ou null si non connecté.
 * @returns {Promise<Object|null>}
 */
export async function getCurrentUser() {
    try {
        const {data} = await axios.get('/rest/action.php/usermanager/getCurrentUserDetails');
        return data && data.id_user ? data : null;
    } catch (e) {
        return null;
    }
}

/**
 * Redirige vers la page de login en conservant l'URL courante + la raison.
 * @param {string} reason
 */
export function redirectToLogin(reason = 'Vous devez être connecté pour accéder à cette page') {
    const redirect = encodeURIComponent(window.location.href);
    window.location.href = `/pages/home.html#/login?redirect=${redirect}&reason=${encodeURIComponent(reason)}`;
}

/**
 * Exige que l'utilisateur connecté ait l'un des profils autorisés.
 * Redirige vers le login sinon. Retourne l'utilisateur si OK, null sinon.
 * @param {string[]} allowedProfiles
 * @returns {Promise<Object|null>}
 */
export async function requireProfile(allowedProfiles) {
    const user = await getCurrentUser();
    if (!user || !allowedProfiles.includes(user.profile_name)) {
        redirectToLogin("Vous n'avez pas le profil suffisant pour accéder à cette page !");
        return null;
    }
    return user;
}

/**
 * Vérifie côté serveur que l'utilisateur a le droit de lire/éditer ce match.
 * @param {string|number} idMatch
 * @returns {Promise<boolean>}
 */
export async function isMatchReadAllowed(idMatch) {
    try {
        const {data} = await axios.get(
            `/rest/action.php/matchmgr/getMatchReadAccess?id_match=${encodeURIComponent(idMatch)}`
        );
        return !!(data && data.allowed);
    } catch (e) {
        return false;
    }
}

/**
 * Garde complète d'une page de gestion de match : profil + autorisation match.
 * Redirige et retourne null si refus ; retourne l'utilisateur si OK.
 * @param {string|number} idMatch
 * @param {string[]} allowedProfiles
 * @returns {Promise<Object|null>}
 */
export async function requireMatchAccess(idMatch, allowedProfiles) {
    const user = await requireProfile(allowedProfiles);
    if (!user) {
        return null;
    }
    if (!idMatch) {
        redirectToLogin('id_match non défini !');
        return null;
    }
    if (!(await isMatchReadAllowed(idMatch))) {
        redirectToLogin("Vous n'êtes pas autorisé à accéder à ce match !");
        return null;
    }
    return user;
}
