/**
 * Formulaire d'édition : valeurs de départ et données postées.
 *
 * Extrait d'`AdminEditModal` pour l'édition en masse (issue #309), qui doit
 * poster EXACTEMENT ce que poste l'édition d'une ligne : tous les champs
 * déclarés par l'écran, plus l'identifiant. Les méthodes PHP écrivent chaque
 * colonne reçue (`Generic::save()`), et le routeur les appelle en arguments
 * nommés : n'envoyer que les champs modifiés viderait les autres, ou ferait un
 * 500 sur un paramètre obligatoire absent.
 *
 * Voir `AdminEditModal` pour la description d'un champ.
 */

/** Valeurs du formulaire, pour les champs déclarés, à partir d'une ligne. */
export function initialForm(fields, record) {
    // On part des champs déclarés pour que Vue voie toutes les clés, puis on
    // recouvre avec les valeurs du record.
    const form = {};
    for (const field of fields) {
        form[field.name] = field.type === 'checkbox' ? false : '';
    }
    for (const [k, v] of Object.entries(record)) {
        form[k] = v;
    }
    for (const field of fields) {
        if (field.type === 'checkbox') {
            form[field.name] = toCheckbox(form[field.name]);
        } else if (field.type === 'date') {
            form[field.name] = toInputDate(form[field.name], field.dateFormat);
        } else if (field.type === 'datetime') {
            form[field.name] = toInputDateTime(form[field.name]);
        }
    }
    return form;
}

/**
 * Données postées pour un formulaire.
 *
 * @param {Array} fields champs déclarés par l'écran
 * @param {Object} form valeurs du formulaire (`initialForm`, puis saisie)
 * @param {Object} record ligne d'origine, qui porte l'identifiant
 * @param {string} idField nom de l'identifiant
 * @param {Function} [fileFor] (field) => File|undefined pour les champs fichier
 */
export function buildFormData(fields, form, record, idField, fileFor = () => undefined) {
    const formData = new FormData();
    for (const field of fields) {
        if (field.type === 'file') {
            const chosen = fileFor(field);
            // Rien de choisi : on n'envoie pas la cle, sinon PHP recevrait un
            // fichier vide et ecraserait l'existant.
            if (chosen) {
                formData.append(field.name, chosen);
            }
            continue;
        }
        const value = form[field.name];
        let sent = value ?? '';
        if (field.type === 'checkbox') {
            sent = value ? '1' : '0';
        } else if (field.type === 'date') {
            sent = fromInputDate(value, field.dateFormat);
        } else if (field.type === 'datetime') {
            sent = fromInputDateTime(value);
        }
        formData.append(field.name, sent);
    }
    // L'identifiant est TOUJOURS envoyé, vide à la création : c'est ce que
    // faisait le champ caché `id` des formulaires ExtJS, et plusieurs méthodes
    // le déclarent en paramètre obligatoire (`Club::saveClub($id, ...)`). Côté
    // base, `Generic::save()` fait un INSERT sur une valeur vide et un UPDATE
    // sinon.
    formData.append(idField, record[idField] ?? '');
    return formData;
}

/**
 * Normalise une valeur de case a cocher venue de l'API.
 *
 * Indispensable : selon la colonne, l'API renvoie l'entier `0` ou la chaine
 * `'0'` (mysqli ne type pas toutes les colonnes de la meme facon —
 * `news.is_disabled` sort en `'0'`, `matchs_view.certif` en `0`). Or
 * `Boolean('0')` vaut `true` : sans cette conversion, une news active
 * s'affichait cochee « desactivee ».
 */
export function toCheckbox(value) {
    if (value === '' || value === null || value === undefined) {
        return false;
    }
    if (typeof value === 'boolean') {
        return value;
    }
    const s = String(value).trim().toLowerCase();
    if (s === 'on' || s === 'true') {
        return true;
    }
    const n = Number(s);
    return Number.isNaN(n) ? Boolean(s) : n !== 0;
}

/**
 * API -> `aaaa-mm-jj` (input natif).
 *
 * Le format de l'API dépend de la colonne : la plupart des écrans parlent en
 * `jj/mm/aaaa` (`STR_TO_DATE(?, '%d/%m/%Y')`), mais `news` stocke et rend de
 * l'ISO. D'où `dateFormat: 'iso'`, qui court-circuite la conversion.
 */
export function toInputDate(value, dateFormat) {
    if (!value) {
        return '';
    }
    if (dateFormat === 'iso') {
        return String(value).slice(0, 10);
    }
    const m = String(value).match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    return m ? `${m[3]}-${m[2]}-${m[1]}` : String(value);
}

/** `aaaa-mm-jj` (input natif) -> API. */
export function fromInputDate(value, dateFormat) {
    if (!value) {
        return '';
    }
    if (dateFormat === 'iso') {
        return String(value);
    }
    const m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : String(value);
}

/**
 * `aaaa-mm-jj hh:mm:ss` (API) -> `aaaa-mm-jjThh:mm` (input natif).
 * Une heure à zéro signifie « journée entière » et doit être conservée telle
 * quelle : c'est ce que lit la home pour ne pas afficher d'heure.
 */
export function toInputDateTime(value) {
    if (!value) {
        return '';
    }
    const m = String(value).match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
    return m ? `${m[1]}T${m[2]}` : String(value);
}

/** `aaaa-mm-jjThh:mm` (input natif) -> `aaaa-mm-jj hh:mm:00` (API). */
export function fromInputDateTime(value) {
    if (!value) {
        return '';
    }
    const m = String(value).match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})$/);
    return m ? `${m[1]} ${m[2]}:00` : String(value);
}
