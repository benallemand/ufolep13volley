import { defineAsyncComponent } from 'vue';
import { adaptCalendarEvents, currentSeason } from '../calendar/calendarData.js';

export default {
    components: {
        'today-matches': defineAsyncComponent(() => import('./TodayMatches.js')),
        'news': defineAsyncComponent(() => import('../table/News.js')),
        'photos': defineAsyncComponent(() => import('../carousel/Photos.js')),
        // Timeline de saison depuis #290, en remplacement d'AnnualCalendar.js :
        // l'agenda est fait de periodes longues, qui noyaient les grilles
        // mensuelles du composant precedent.
        'season-timeline': defineAsyncComponent(() => import('../calendar/SeasonTimeline.js')),
    },
    template: `
      <div class="flex flex-col items-center gap-8 px-2">
        <today-matches/>
        <news/>
        <season-timeline :events="importantEvents" :season="currentSeason"/>
        <photos/>
      </div>
    `,
    data() {
        return {
            // Alimente depuis la base (issue #253) : ces evenements etaient
            // codes en dur ici, toute retouche demandait un deploiement.
            importantEvents: [],
            fetchUrl: "/rest/action.php/calendarevents/getCalendarEvents"
        };
    },
    computed: {
        currentSeason() {
            // Regle partagee avec le back (CalendarEvents::getCurrentSeason) :
            // de janvier a juin, la saison affichee est celle ouverte en
            // septembre de l'annee precedente.
            return currentSeason();
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl, { params: { season: this.currentSeason } })
                .then((response) => {
                    const rows = Array.isArray(response.data) ? response.data : [];
                    this.importantEvents = adaptCalendarEvents(rows);
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement du calendrier:", error);
                });
        }
    },
    created() {
        this.fetch();
    }
};
