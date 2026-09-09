import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import multiMonthPlugin from '@fullcalendar/multimonth';
import frLocale from '@fullcalendar/core/locales/fr';
import { onError } from '../../../toaster.js';
import {
    SOURCES,
    adaptMatches,
    currentSeason,
    seasonStart,
    mergeMatchSources,
    toIso,
    eventDetail,
} from './calendarData.js';

/**
 * Calendrier des matchs du responsable (issue #290).
 *
 * Deux sources, cochables : les matchs de son équipe (`getMesMatches`) et ceux
 * de son club (`getMyClubMatches`). Les deux endpoints sont en `user` dans
 * `rest/access.php` et se restreignent côté serveur à la session — le front ne
 * choisit pas le périmètre, il ne fait que l'afficher.
 *
 * **Uniquement les matchs.** L'agenda de la commission n'est pas superposé ici :
 * il est affiché juste en dessous par `SeasonTimeline`, sous une forme qui lui
 * convient mieux. L'y remettre serait afficher deux fois la même chose.
 *
 * FullCalendar et non un composant maison : les matchs sont nombreux (jusqu'à
 * une cinquantaine pour un club de trois équipes) et demandent trois lectures
 * différentes — la saison d'un coup d'œil, le mois en cours, et la liste de ce
 * qui arrive. Écrire ces trois vues à la main, avec le débordement de cases,
 * les infobulles et le clavier, n'aurait aucun intérêt.
 *
 * Complément des listes existantes (`/team_matchs`, `/club_matchs`), qui gardent
 * leurs filtres métier et les actions de report : celles-ci répondent à « que
 * dois-je faire », ce calendrier à « quand est-ce que je joue ».
 */
export default {
    components: { FullCalendar },
    template: `
      <div>
        <p class="text-xl mb-1">Calendrier des matchs</p>
        <p class="text-sm opacity-70 mb-4">
          Vos rencontres et celles de votre club. Cliquez sur un match pour
          ouvrir sa fiche.
        </p>

        <div class="flex flex-wrap gap-3 items-center mb-4">
          <div class="join">
            <button v-for="v in views" :key="v.key"
                    class="btn btn-sm join-item"
                    :class="view === v.key ? 'btn-primary' : 'btn-outline'"
                    @click="setView(v.key)">{{ v.label }}</button>
          </div>

          <label v-for="key in ['team', 'club']" :key="key"
                 class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" class="checkbox checkbox-sm" v-model="shown[key]"/>
            <span class="inline-block w-3 h-3 rounded"
                  :style="{ background: sources[key].color }"></span>
            <span class="text-sm">{{ sources[key].label }}</span>
          </label>

          <span v-if="isLoading" class="loading loading-spinner loading-sm"></span>
          <span v-else class="text-xs opacity-60 ml-auto">
            {{ displayedEvents.length }} match(s)
          </span>
        </div>

        <div class="bg-base-100 border border-base-300 rounded p-2">
          <full-calendar ref="calendar" :options="options"/>
        </div>
      </div>
    `,
    data() {
        return {
            isLoading: false,
            teamMatches: [],
            clubMatches: [],
            view: 'month',
            views: [
                { key: 'season', label: 'Saison' },
                { key: 'month', label: 'Mois' },
                { key: 'agenda', label: 'À venir' },
            ],
            sources: SOURCES,
            shown: { team: true, club: true },
        };
    },
    computed: {
        season() {
            return currentSeason();
        },
        allEvents() {
            return mergeMatchSources(
                adaptMatches(this.teamMatches, 'team'),
                adaptMatches(this.clubMatches, 'club'),
            );
        },
        displayedEvents() {
            return this.allEvents.filter((e) => this.shown[e.source]);
        },
        /**
         * Un match tombe-t-il un samedi ou un dimanche ?
         *
         * Les 939 matchs de la saison 2025-2026 se jouent tous du lundi au
         * vendredi : masquer les week-ends gagne donc deux colonnes sur sept
         * sans rien cacher. Mais le masquage ne peut pas être écrit en dur — ce
         * que faisait `AnnualCalendar.js` — car un match programmé un samedi
         * deviendrait invisible.
         */
        hasWeekendMatch() {
            return this.displayedEvents.some((e) => {
                const day = new Date(e.startDate + 'T00:00:00').getDay();
                return day === 0 || day === 6;
            });
        },
        fullCalendarEvents() {
            return this.displayedEvents.map((e) => {
                const source = this.sources[e.source];
                return {
                    id: e.id,
                    title: e.title,
                    start: e.time ? e.startDate + 'T' + e.time : e.startDate,
                    // Un match tient sur une journée : sans heure de réception,
                    // `start` seul suffit et FullCalendar le pose sur ce jour.
                    end: e.endTime ? e.startDate + 'T' + e.endTime : undefined,
                    allDay: e.allDay,
                    backgroundColor: source.color,
                    borderColor: source.border,
                    extendedProps: e,
                };
            });
        },
        options() {
            const isSeason = this.view === 'season';
            return {
                plugins: [dayGridPlugin, listPlugin, multiMonthPlugin],
                locale: frLocale,
                initialView: this.fullCalendarView,
                initialDate: this.initialDate,
                headerToolbar: isSeason
                    ? { left: '', center: 'title', right: '' }
                    : { left: 'prev,next today', center: 'title', right: '' },
                views: {
                    // Vue sur mesure : les dix mois de la saison, de septembre
                    // à juin, comme la timeline de l'agenda juste en dessous.
                    seasonMonths: {
                        type: 'multiMonth',
                        duration: { months: 10 },
                        multiMonthMinWidth: 260,
                        multiMonthMaxColumns: 3,
                    },
                    // « À venir » : la liste des trois prochains mois, plutôt
                    // que du seul mois courant, qui peut être vide (vacances).
                    upcoming: {
                        type: 'list',
                        duration: { months: 3 },
                    },
                },
                events: this.fullCalendarEvents,
                height: 'auto',
                firstDay: 1,
                weekends: this.hasWeekendMatch,
                eventDisplay: isSeason ? 'list-item' : 'block',
                dayMaxEvents: isSeason ? 2 : 4,
                displayEventTime: !isSeason,
                eventTimeFormat: { hour: '2-digit', minute: '2-digit' },
                noEventsText: 'Aucun match sur cette période',
                eventDidMount: (arg) => {
                    const detail = eventDetail(arg.event.extendedProps);
                    arg.el.title = arg.event.extendedProps.title
                        + (detail ? ' — ' + detail : '');
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    const target = info.event.extendedProps.url;
                    if (target) {
                        window.location.href = target;
                    }
                },
            };
        },
        fullCalendarView() {
            if (this.view === 'season') {
                return 'seasonMonths';
            }
            if (this.view === 'agenda') {
                return 'upcoming';
            }
            return 'dayGridMonth';
        },
        /**
         * Date d'ouverture des vues.
         *
         * En vue saison, on ancre sur septembre. Sinon on ouvre sur aujourd'hui,
         * SAUF si le jour est hors saison — en juillet ou en août, la vue mois
         * s'ouvrirait sur un mois vide.
         */
        initialDate() {
            const debut = seasonStart(this.season);
            if (this.view === 'season') {
                return debut;
            }
            const today = toIso(new Date());
            const fin = (Number(this.season.slice(0, 4)) + 1) + '-07-01';
            return (today >= debut && today < fin) ? today : debut;
        },
    },
    created() {
        this.fetch();
    },
    methods: {
        fetch() {
            this.isLoading = true;
            Promise.all([
                axios.get('/rest/action.php/matchmgr/getMesMatches')
                    .then(({ data }) => { this.teamMatches = Array.isArray(data) ? data : []; })
                    // Un responsable de club n'a pas d'équipe propre : cet
                    // appel peut échouer légitimement, sans que le calendrier
                    // du club en souffre.
                    .catch(() => { this.teamMatches = []; }),
                axios.get('/rest/action.php/matchmgr/getMyClubMatches')
                    .then(({ data }) => { this.clubMatches = Array.isArray(data) ? data : []; })
                    .catch((error) => { this.clubMatches = []; onError(this, error); }),
            ]).finally(() => { this.isLoading = false; });
        },
        setView(key) {
            this.view = key;
            const api = this.$refs.calendar.getApi();
            api.changeView(this.fullCalendarView);
            api.gotoDate(this.initialDate);
        },
    },
};
