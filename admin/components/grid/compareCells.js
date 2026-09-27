/**
 * Comparaison de deux cellules pour un tri par colonne, en ordre croissant.
 *
 * Partagée par `AdminGrid` et par le détail des indicateurs (issue #340) : les
 * deux affichent des lignes brutes venues du serveur, sans type de colonne
 * déclaré, et doivent trier nombres, dates françaises et texte chacun à sa
 * façon.
 *
 * @returns {number} négatif si va passe avant vb, positif après, 0 si égaux
 */
export function compareCells(va, vb) {
    const na = Number(va), nb = Number(vb);
    if (va !== '' && vb !== '' && !Number.isNaN(na) && !Number.isNaN(nb)) {
        return na - nb;
    }
    // Dates francaises : comparees chronologiquement (issue #296).
    const da = sortableDate(va), db = sortableDate(vb);
    if (da !== null && db !== null) {
        return da.localeCompare(db);
    }
    return String(va ?? '').localeCompare(String(vb ?? ''), 'fr');
}

/**
 * Une date `jj/mm/aaaa[ hh:mm[:ss]]` -> clef triable `aaaammjjhhmmss`.
 *
 * Sans cela, une colonne de date se triait comme du texte : `02/12/2025`
 * passait avant `15/11/2025` (issue #296). Le defaut touchait 18
 * colonnes sur 13 ecrans ; le corriger ici les couvre toutes, et un
 * futur ecran en profite sans rien declarer.
 *
 * La conversion n'est appliquee que si les DEUX valeurs comparees sont
 * des dates francaises : une colonne texte ne peut donc pas basculer
 * par accident. Les colonnes deja en ISO (`aaaa-mm-jj hh:mm:ss`,
 * `calendar_events`) se triaient correctement en texte et ne passent
 * pas par ici.
 *
 * @returns {string|null} null si la valeur n'est pas une date francaise
 */
export function sortableDate(value) {
    if (value === null || value === undefined) {
        return null;
    }
    const m = String(value).trim()
        .match(/^(\d{2})\/(\d{2})\/(\d{4})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
    if (!m) {
        return null;
    }
    return m[3] + m[2] + m[1] + (m[4] || '00') + (m[5] || '00') + (m[6] || '00');
}
