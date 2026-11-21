/**
 * Étapes de la page de match unique (issues #486, #490).
 *
 * 1. présents (liste commune aux deux équipes) → 2. fiche équipes : la
 * première signature fige les présents → 3. le match (live scoring, forfait)
 * → 4. score, saisissable dès la première signature des présents → 5. feuille
 * de match : la première signature fige le score → 6. sondage fair-play.
 *
 * Chaque équipe peut annuler sa signature : la fiche équipes tant que
 * personne n'a signé la feuille de match, la feuille de match tant que
 * l'autre équipe ne l'a pas signée. Mêmes règles que `MatchMgr`.
 */

/** Championnats : même liste que `MatchPenalty::CHAMPIONSHIPS`. */
const CHAMPIONSHIPS = ['m', 'f', 'mo'];
/** Délai de signature de la feuille de match : `MatchPenalty::UNSIGNED_SHEET_DELAY_DAYS`. */
const DELAY_DAYS = 7;
const DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

export const STEP_KEYS = ['presents', 'fiche', 'match', 'score', 'feuille', 'sondage'];

const pad = n => String(n).padStart(2, '0');
const on = v => Number(v) === 1;

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

/** 'dom' / 'ext' : le côté de l'équipe ; null si elle ne joue pas le match. */
export function sideOf(match, teamId) {
    if (teamId === null || teamId === undefined) return null;
    if (Number(match?.id_equipe_dom) === Number(teamId)) return 'dom';
    if (Number(match?.id_equipe_ext) === Number(teamId)) return 'ext';
    return null;
}

/** État partagé, dérivé d'une ligne de `matchs_view`. */
export function matchState(match) {
    const m = match || {};
    // la première signature de la fiche équipes fige les présents
    const frozen = on(m.is_sign_team_dom) || on(m.is_sign_team_ext);
    const scoreFilled = on(m.is_match_score_filled);
    return {
        confirmed: m.match_status === 'CONFIRMED',
        certified: on(m.certif),
        // `is_forfait` ne vaut 1 qu'une fois les deux feuilles signées : un score
        // saisi sans présents figés ne peut venir que d'un forfait déclaré
        forfeit: on(m.is_forfait) || (scoreFilled && !frozen),
        playersFilled: on(m.is_match_player_filled),
        frozen,
        scoreFilled,
        // la première signature de la feuille de match fige le score
        scoreFrozen: on(m.is_sign_match_dom) || on(m.is_sign_match_ext),
        closed: on(m.is_sign_match_dom) && on(m.is_sign_match_ext),
        team: {dom: on(m.is_sign_team_dom), ext: on(m.is_sign_team_ext)},
        sheet: {dom: on(m.is_sign_match_dom), ext: on(m.is_sign_match_ext)},
        survey: {dom: on(m.is_survey_filled_dom), ext: on(m.is_survey_filled_ext)},
    };
}

/**
 * Étape en cours pour une équipe (`side`), ou pour la commission (`side`
 * null) : la première chose qui reste à faire. 'done' quand tout est fait.
 */
export function currentStep(match, side) {
    const s = matchState(match);
    if (!s.frozen && !s.forfeit) return s.playersFilled ? 'fiche' : 'presents';
    if (side && !s.team[side] && !s.forfeit && !s.scoreFilled) return 'fiche';
    if (!s.scoreFilled) return 'score';
    if (side ? !s.sheet[side] : !s.closed) return 'feuille';
    if (side && !s.survey[side]) return 'sondage';
    if (side && !s.team[side] && !s.forfeit) return 'fiche';
    return 'done';
}

/**
 * Ce que l'équipe connectée doit faire maintenant, pour l'encadré « À faire ».
 * @returns {{level: string, icon: string, title: string, detail?: string, deadline?: string}|null}
 */
export function nextSignatureStep(match, teamId, now = new Date()) {
    if (!match) return null;
    const side = sideOf(match, teamId);
    if (!side) return null;
    const s = matchState(match);
    if (!s.confirmed || s.certified) return null;
    const other = side === 'dom' ? 'ext' : 'dom';
    const otherName = match[`equipe_${other}`];

    const limit = matchSheetDeadline(match);
    let deadline;
    let late = false;
    if (limit && !s.closed) {
        late = now > limit;
        deadline = late
            ? `Date limite dépassée : ${formatDeadline(limit)} (7 jours après le match, article 8 du règlement général).`
            : `Date limite de signature de la feuille de match par les deux équipes : ${formatDeadline(limit)}.`;
    }
    const step = (level, icon, title, detail) => ({level, icon, title, detail, deadline});

    switch (currentStep(match, side)) {
        case 'presents':
            return step('warning', 'fa-users', 'Il vous reste à renseigner les présents.',
                "Liste commune : cochez les joueurs présents des deux équipes, puis enregistrez.");
        case 'fiche':
            return s.frozen
                ? step('warning', 'fa-signature', 'Il vous reste à confirmer les présents.',
                    `${otherName} a signé la fiche équipes : vérifiez les présents et signez à votre tour.`)
                : step('warning', 'fa-signature', 'Il vous reste à signer la fiche équipes.',
                    "Signer fige les présents des deux équipes et permet de saisir le score. "
                    + "La feuille de match sera à signer après le match.");
        case 'score':
            return step('info', 'fa-volleyball', 'Après le match : saisissez le score.',
                'Ou suivez-le en direct avec le live scoring. La feuille de match se signe ensuite.');
        case 'feuille':
            return step(late ? 'error' : 'warning', 'fa-signature', 'Il vous reste à signer la feuille de match.',
                "C'est une seconde signature, distincte de la fiche équipes : elle valide le score.");
        case 'sondage':
            return step('info', 'fa-square-poll-vertical', 'Il vous reste à remplir le sondage fair-play.',
                s.sheet[other] ? 'La feuille de match est signée par les deux équipes.'
                    : `En attente de la feuille de match de ${otherName}.`);
        default:
            return s.closed
                ? step('success', 'fa-circle-check', 'Tout est fait pour ce match, merci !')
                : step('info', 'fa-hourglass-half', `En attente de la feuille de match de ${otherName}.`,
                    'Vous avez fait tout ce qui vous revenait.');
    }
}
