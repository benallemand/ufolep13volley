/**
 * Prochaine étape de signature pour l'équipe connectée (issue #486).
 *
 * Le responsable d'une équipe confondait la fiche équipes (signée avant le
 * match) et la feuille de match (signée après, avec le score) : il pensait
 * avoir tout signé. L'encadré dit à l'équipe ce qu'il lui reste à faire, avec
 * la date limite des 7 jours en championnat.
 */

/** Championnats : même liste que `MatchPenalty::CHAMPIONSHIPS`. */
const CHAMPIONSHIPS = ['m', 'f', 'mo'];
/** Délai de signature de la feuille de match : `MatchPenalty::UNSIGNED_SHEET_DELAY_DAYS`. */
const DELAY_DAYS = 7;
const DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

const pad = n => String(n).padStart(2, '0');

/**
 * Date limite de signature de la feuille de match (horaire du match + 7 jours,
 * fin de journée sans créneau connu), comme le relevé du cron ; null hors
 * championnat.
 * @returns {Date|null}
 */
export function matchSheetDeadline(match) {
    if (!CHAMPIONSHIPS.includes(match?.code_competition)) return null;
    const date = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(match.date_reception || '');
    if (!date) return null;
    const time = /^(\d{1,2}):(\d{2})/.exec(match.heure_reception || '') || [null, '23', '59'];
    const deadline = new Date(Number(date[3]), Number(date[2]) - 1, Number(date[1]),
        Number(time[1]), Number(time[2]));
    deadline.setDate(deadline.getDate() + DELAY_DAYS);
    return deadline;
}

export function formatDeadline(deadline) {
    return `${DAYS[deadline.getDay()]} ${pad(deadline.getDate())}/${pad(deadline.getMonth() + 1)}/${deadline.getFullYear()}`
        + ` à ${pad(deadline.getHours())}:${pad(deadline.getMinutes())}`;
}

/**
 * @param {object} match ligne de `matchs_view`
 * @param {number|null} teamId équipe au nom de laquelle le compte agit (`actingTeamId`)
 * @param {Date} now
 * @returns {{level: string, icon: string, title: string, detail?: string, deadline?: string}|null}
 */
export function nextSignatureStep(match, teamId, now = new Date()) {
    if (!match || teamId === null || teamId === undefined) return null;
    if (match.match_status !== 'CONFIRMED' || Number(match.certif) === 1) return null;
    const side = Number(match.id_equipe_dom) === Number(teamId) ? 'dom'
        : Number(match.id_equipe_ext) === Number(teamId) ? 'ext' : null;
    if (!side) return null;
    const other = side === 'dom' ? 'ext' : 'dom';
    const otherName = match[`equipe_${other}`];
    const signed = (kind, s) => Number(match[`is_sign_${kind}_${s}`]) === 1;

    const limit = matchSheetDeadline(match);
    const matchSheetPending = !(signed('match', 'dom') && signed('match', 'ext'));
    let deadline;
    let late = false;
    if (limit && matchSheetPending) {
        late = now > limit;
        deadline = late
            ? `Date limite dépassée : ${formatDeadline(limit)} (7 jours après le match, article 8 du règlement général).`
            : `Date limite de signature de la feuille de match par les deux équipes : ${formatDeadline(limit)}.`;
    }
    const step = (level, icon, title, detail) => ({level, icon, title, detail, deadline});

    // Pas de fiches équipes sur un forfait : le score est écrit par le serveur.
    if (Number(match.is_forfait) !== 1) {
        if (!signed('team', side)) {
            return step('warning', 'fa-signature', 'Il vous reste à signer la fiche équipes.',
                'Avant le match, une fois vos présents renseignés. Ce n\'est que la première signature : '
                + 'la feuille de match sera à signer après le match.');
        }
        if (!signed('team', other)) {
            return step('info', 'fa-hourglass-half', `En attente de la fiche équipes de ${otherName}.`,
                'Vous avez signé la fiche équipes. Après le match, il faudra saisir le score puis signer la feuille de match.');
        }
    }
    if (Number(match.is_match_score_filled) !== 1) {
        return step('info', 'fa-volleyball',
            'Après le match : saisir le score, puis signer la feuille de match.',
            'Les fiches équipes sont signées. La feuille de match est une seconde signature, à faire par chaque équipe.');
    }
    if (!signed('match', side)) {
        return step(late ? 'error' : 'warning', 'fa-signature',
            'Il vous reste à signer la feuille de match.',
            'Cliquez sur « signer » sur la ligne « Feuille de match ». C\'est une seconde signature, '
            + 'distincte de la fiche équipes signée avant le match.');
    }
    if (!signed('match', other)) {
        return step('info', 'fa-hourglass-half', `En attente de la feuille de match de ${otherName}.`,
            'Vous avez signé la feuille de match.');
    }
    if (Number(match[`is_survey_filled_${side}`]) !== 1) {
        return step('info', 'fa-square-poll-vertical', 'Il vous reste à remplir le sondage fair-play.',
            'La feuille de match est signée par les deux équipes.');
    }
    return step('success', 'fa-circle-check', 'Tout est fait pour ce match, merci !');
}
