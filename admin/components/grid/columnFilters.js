import { sortableDate } from './compareCells.js';

/**
 * Filtres par colonne de la grille (issue #310).
 *
 * La recherche globale cherche chaque terme dans toutes les colonnes : « 3 »
 * trouvait la division 3, mais aussi un 3 dans un nom d'équipe ou une licence.
 * Chaque colonne reçoit donc son propre filtre, sous l'en-tête, sans que les
 * écrans aient à le déclarer — le type se déduit des valeurs affichées :
 *
 *   - 'date'   : toutes les valeurs sont des dates jj/mm/aaaa → plage du … au …
 *   - 'select' : peu de valeurs distinctes (≤ SELECT_MAX) → liste
 *   - 'text'   : sinon → « contient » (égalité pour un nombre dans une
 *                cellule numérique : « 3 » ne trouve pas la division 13)
 *
 * Une colonne peut forcer son type (`filter: 'text' | 'select' | 'date'`) ou
 * s'exclure (`filter: false`). Les colonnes vignette et icônes sont exclues.
 * Le filtre porte sur la valeur AFFICHÉE (après `format`) : c'est ce que voit
 * l'administrateur, « oui »/« non » plutôt que 1/0.
 */
export const SELECT_MAX = 20;

/** Type de filtre d'une colonne, ou null si elle n'en a pas. */
export function filterKind(column, displayedValues) {
    if (column.filter === false || column.image || column.links || !column.label) {
        return null;
    }
    if (typeof column.filter === 'string') {
        return column.filter;
    }
    const present = displayedValues.filter((v) => v !== '');
    if (present.length && present.every((v) => sortableDate(v) !== null)) {
        return 'date';
    }
    const distinct = new Set(present);
    return distinct.size > 0 && distinct.size <= SELECT_MAX ? 'select' : 'text';
}

/** Valeurs distinctes, triées, pour une liste. */
export function selectOptions(displayedValues) {
    return [...new Set(displayedValues.filter((v) => v !== ''))]
        .sort((a, b) => a.localeCompare(b, 'fr', { numeric: true }));
}

/** Aucun critère saisi pour ce filtre. */
export function isEmptyFilter(value) {
    if (value === null || value === undefined || value === '') {
        return true;
    }
    if (typeof value === 'object') {
        return !value.from && !value.to;
    }
    return false;
}

/**
 * La valeur affichée satisfait-elle le filtre ?
 * @param {string} kind 'text' | 'select' | 'date'
 * @param {string} displayed valeur affichée de la cellule
 * @param {*} value critère : texte, valeur de la liste, ou { from, to } (aaaa-mm-jj)
 */
export function matchesFilter(kind, displayed, value) {
    if (isEmptyFilter(value)) {
        return true;
    }
    if (kind === 'select') {
        return displayed === value;
    }
    if (kind === 'date') {
        const key = sortableDate(displayed);
        if (key === null) {
            return false;
        }
        const day = key.slice(0, 8);
        const from = value.from ? value.from.replace(/-/g, '') : null;
        const to = value.to ? value.to.replace(/-/g, '') : null;
        return (!from || day >= from) && (!to || day <= to);
    }
    const term = String(value).trim().toLowerCase();
    // Un nombre cherché dans une cellule numérique : égalité stricte, sinon
    // « 3 » trouverait aussi la division 13.
    if (/^\d+$/.test(term) && /^\d+$/.test(displayed)) {
        return Number(displayed) === Number(term);
    }
    return displayed.toLowerCase().includes(term);
}
