import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'today-matches': defineAsyncComponent(() => import('./TodayMatches.js')),
        'news': defineAsyncComponent(() => import('../table/News.js')),
        'photos': defineAsyncComponent(() => import('../carousel/Photos.js')),
        'annual-calendar': defineAsyncComponent(() => import('../calendar/AnnualCalendar.js')),
    },
    template: `
      <div class="flex flex-col items-center gap-8 px-2">
        <today-matches/>
        <news/>
        <annual-calendar :events="importantEvents" :season="currentSeason"/>
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
            const now = new Date();
            // Même règle que AnnualCalendar : de juillet à décembre, la saison
            // affichée est celle qui démarre en septembre de l'année en cours
            const startYear = now.getMonth() <= 5 ? now.getFullYear() - 1 : now.getFullYear();
            return `${startYear}-${startYear + 1}`;
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl, { params: { season: this.currentSeason } })
                .then((response) => {
                    this.importantEvents = Array.isArray(response.data) ? response.data : [];
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
