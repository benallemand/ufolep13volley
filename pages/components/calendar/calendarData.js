/**
 * Normalisation des données de calendrier (issue #290).
 *
 * Deux sources, deux formats de date différents, et un composant de calendrier
 * n'accepte ni l'un ni l'autre. Ce module fait la traduction une seule fois,
 * pour que la timeline et le calendrier des matchs reçoivent la même chose :
 *
 *   agenda commission — `calendarevents/getCalendarEvents`
 *       date_start : 'jj/mm/aaaa' ou 'jj/mm/aaaa hh:mm'
 *       date_end   : idem, ou null pour un rendez-vous ponctuel
 *
 *   matchs — `matchmgr/getMesMatches`, `matchmgr/getMyClubMatches`
 *       date_reception  : 'jj/mm/aaaa'
 *       heure_reception : 'hh:mm', ou null (6 matchs sur 49 dans les données
 *                         réelles de 2025-2026 : le cas n'est pas théorique)
 *
 * Forme de sortie, commune :
 *   { id, title, source, allDay, startDate, endDate, time, endTime, … }
 * avec startDate/endDate en 'aaaa-mm-jj' et time/endTime en 'hh:mm'.
 */

/**
 * Sources de matchs et leur identité visuelle.
 *
 * L'agenda de la commission n'y figure pas : il n'est affiché que par
 * `SeasonTimeline`, qui colore ses lignes avec sa propre palette.
 */
export const SOURCES = {
    team: { label: 'Mes matchs', color: '#059669', border: '#047857' },
    club: { label: 'Matchs du club', color: '#d97706', border: '#b45309' },
};

/**
 * Saison en cours, au format '2025-2026'.
 *
 * MÊME RÈGLE que le back (`CalendarEvents::getCurrentSeason()`) : de janvier à
 * juin on est encore dans la saison ouverte en septembre de l'année
 * précédente ; dès juillet on bascule sur la suivante. Les deux doivent rester
 * alignés, sinon la home demanderait une saison que l'API ne servirait pas.
 */
export function currentSeason(now = new Date()) {
    const startYear = now.getMonth() <= 5 ? now.getFullYear() - 1 : now.getFullYear();
    return startYear + '-' + (startYear + 1);
}

/** Premier jour de la saison, en ISO — l'ancre des vues « saison ». */
export function seasonStart(season) {
    return season.slice(0, 4) + '-09-01';
}

/** Date JS -> 'aaaa-mm-jj', en heure locale (toISOString décalerait d'un jour). */
export function toIso(date) {
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return date.getFullYear() + '-' + m + '-' + d;
}

/** 'jj/mm/aaaa[ hh:mm]' -> { date: 'aaaa-mm-jj', time: 'hh:mm'|null }. */
export function parseFrenchDate(value) {
    if (!value) {
        return null;
    }
    const [datePart, timePart] = String(value).trim().split(' ');
    const m = datePart.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) {
        return null;
    }
    return { date: m[3] + '-' + m[2] + '-' + m[1], time: timePart || null };
}

/**
 * L'heure de fin de journée telle que la commission la saisit.
 *
 * `23:59` est la convention maison pour « toute la journée ». L'afficher
 * donnerait « Inscriptions à 23:59 », qui n'apprend rien — au même titre que
 * minuit, déjà neutralisé côté SQL par `getCalendarEvents()`.
 */
const FIN_DE_JOURNEE = '23:59';

/**
 * Agenda de la commission -> événements normalisés.
 *
 * Trois cas : une période s'il y a une date de fin, un rendez-vous horaire s'il
 * y a une heure signifiante, une journée entière sinon.
 */
export function adaptCalendarEvents(rows) {
    const events = [];
    for (const row of rows || []) {
        const start = parseFrenchDate(row.date_start);
        if (!start) {
            continue;
        }
        const end = parseFrenchDate(row.date_end);
        const timed = start.time && start.time !== FIN_DE_JOURNEE;
        events.push({
            id: 'ev-' + row.id,
            title: row.label,
            source: 'commission',
            allDay: !!end || !timed,
            startDate: start.date,
            endDate: end ? end.date : start.date,
            time: end ? null : (timed ? start.time : null),
            endTime: null,
        });
    }
    return events;
}

/** Durée d'affichage d'un match, faute de mieux : l'API ne donne pas de fin. */
const DUREE_MATCH_MINUTES = 90;

/**
 * Matchs -> événements normalisés.
 *
 * @param {Array} rows lignes de `matchs_view`
 * @param {string} source 'team' ou 'club'
 */
export function adaptMatches(rows, source) {
    const events = [];
    for (const row of rows || []) {
        const start = parseFrenchDate(row.date_reception);
        if (!start) {
            // Un match non encore programmé n'a pas de date : il n'a rien à
            // faire sur un calendrier.
            continue;
        }
        const home = (row.equipe_dom || '').trim();
        const away = (row.equipe_ext || '').trim();
        const played = row.score_equipe_dom !== null
            && row.score_equipe_dom !== undefined
            && String(row.score_equipe_dom) !== '';
        const heure = row.heure_reception || null;
        events.push({
            id: 'ma-' + row.id_match,
            title: home + ' - ' + away
                + (played ? ' (' + row.score_equipe_dom + '-' + row.score_equipe_ext + ')' : ''),
            source,
            // Sans heure de réception, le match est une « journée entière » :
            // le placer à 00:00 serait un horaire faux.
            allDay: !heure,
            startDate: start.date,
            endDate: start.date,
            time: heure,
            endTime: heure ? addMinutes(heure, DUREE_MATCH_MINUTES) : null,
            gymnasium: row.gymnasium || '',
            competition: row.libelle_competition || '',
            division: row.division || '',
            played,
            url: '/match.html?id_match=' + row.id_match,
        });
    }
    return events;
}

/**
 * Fusionne les matchs de l'équipe et ceux du club.
 *
 * `getMyClubMatches` renvoie AUSSI les matchs de l'équipe du responsable :
 * sans ce dédoublonnage, chaque match de l'équipe apparaîtrait deux fois, une
 * fois en vert et une fois en orange.
 */
export function mergeMatchSources(teamEvents, clubEvents) {
    const dejaVus = new Set(teamEvents.map((e) => e.id));
    return teamEvents.concat(clubEvents.filter((e) => !dejaVus.has(e.id)));
}

function addMinutes(hhmm, minutes) {
    const [h, m] = hhmm.split(':').map(Number);
    const total = h * 60 + m + minutes;
    const hh = String(Math.floor(total / 60) % 24).padStart(2, '0');
    const mm = String(total % 60).padStart(2, '0');
    return hh + ':' + mm;
}

/** Libellé de détail d'un événement, pour les infobulles. */
export function eventDetail(event) {
    return [event.time, event.gymnasium, event.competition]
        .filter(Boolean)
        .join(' — ');
}
