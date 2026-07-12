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
            // Événements par saison — à migrer en base de données (issue #253)
            eventsBySeason: {
                '2025-2026': [
                    {date_start: '03/09/2025 19:30', date_end: null, label: 'Réunion calendrier'},
                    {date_start: '03/09/2025 23:59', date_end: '03/10/2025 23:59', label: 'Inscriptions championnats'},
                    {date_start: '03/09/2025 23:59', date_end: '28/11/2025 23:59', label: 'Inscriptions coupe 4x4 Khoury Hanna'},
                    {date_start: '06/10/2025 19:30', date_end: null, label: 'Réunion début de saison'},
                    {date_start: '09/10/2025 00:00', date_end: '13/10/2025 23:59', label: 'Tournoi(s) de bienvenue'},
                    {date_start: '03/11/2025 00:00', date_end: '19/12/2025 23:59', label: 'Championnats'},
                    {date_start: '20/12/2025 00:00', date_end: '04/01/2026 23:59', label: 'Vacances'},
                    {date_start: '05/01/2026 00:00', date_end: '09/01/2026 23:59', label: 'Reports'},
                    {date_start: '07/01/2026 19:30', date_end: null, label: 'Tirage des coupes'},
                    {date_start: '12/01/2026 19:30', date_end: null, label: 'Réunion fin de demi-saison'},
                    {date_start: '19/01/2026 00:00', date_end: '13/02/2026 23:59', label: 'Coupes'},
                    {date_start: '14/02/2026 00:00', date_end: '27/02/2026 23:59', label: 'Vacances'},
                    {date_start: '02/03/2026 00:00', date_end: '10/04/2026 23:59', label: 'Championnats'},
                    {date_start: '11/04/2026 00:00', date_end: '26/04/2026 23:59', label: 'Vacances'},
                    {date_start: '27/04/2026 00:00', date_end: '29/05/2026 23:59', label: 'Championnats'},
                    {date_start: '16/06/2026 19:30', date_end: null, label: 'Réunion fin de saison'},
                    {date_start: '01/06/2026 00:00', date_end: '19/06/2026 23:59', label: 'Phases finales Coupes'},
                    {date_start: '26/06/2026 19:30', date_end: null, label: 'Finales + récompenses à Marignane'}
                ],
                '2026-2027': [
                    {date_start: '02/09/2026 19:30', date_end: null, label: 'Réunion calendrier'},
                    {date_start: '02/09/2026 23:59', date_end: '02/10/2026 23:59', label: 'Inscriptions championnats'},
                    {date_start: '02/09/2026 23:59', date_end: '27/11/2026 23:59', label: 'Inscriptions coupe 4x4 Khoury Hanna'},
                    {date_start: '05/10/2026 19:30', date_end: null, label: 'Réunion début de saison'},
                    {date_start: '08/10/2026 00:00', date_end: '12/10/2026 23:59', label: 'Tournoi(s) de bienvenue'},
                    {date_start: '02/11/2026 00:00', date_end: '18/12/2026 23:59', label: 'Championnats'},
                    {date_start: '11/11/2026', date_end: null, label: 'Férié / pont'},
                    {date_start: '19/12/2026 00:00', date_end: '03/01/2027 23:59', label: 'Vacances'},
                    {date_start: '04/01/2027 00:00', date_end: '08/01/2027 23:59', label: 'Reports'},
                    {date_start: '06/01/2027 19:30', date_end: null, label: 'Tirage des coupes'},
                    {date_start: '11/01/2027 19:30', date_end: null, label: 'Réunion fin de demi-saison'},
                    {date_start: '18/01/2027 00:00', date_end: '05/02/2027 23:59', label: 'Coupes'},
                    {date_start: '08/02/2027 00:00', date_end: '12/02/2027 23:59', label: 'Reports'},
                    {date_start: '20/02/2027 00:00', date_end: '07/03/2027 23:59', label: 'Vacances'},
                    {date_start: '08/03/2027 00:00', date_end: '16/04/2027 23:59', label: 'Championnats'},
                    {date_start: '29/03/2027', date_end: null, label: 'Férié / pont'},
                    {date_start: '17/04/2027 00:00', date_end: '02/05/2027 23:59', label: 'Vacances'},
                    {date_start: '03/05/2027 00:00', date_end: '14/05/2027 23:59', label: 'Coupes - 1/8 de finale'},
                    {date_start: '17/05/2027 00:00', date_end: '21/05/2027 23:59', label: 'Coupes - 1/4 de finale'},
                    {date_start: '24/05/2027 00:00', date_end: '28/05/2027 23:59', label: 'Coupes - 1/2 finales'},
                    {date_start: '06/05/2027', date_end: null, label: 'Férié / pont'},
                    {date_start: '07/05/2027', date_end: null, label: 'Férié / pont'},
                    {date_start: '17/05/2027', date_end: null, label: 'Férié / pont'},
                    {date_start: '31/05/2027 00:00', date_end: '04/06/2027 23:59', label: 'Championnats'},
                    {date_start: '07/06/2027 00:00', date_end: '11/06/2027 23:59', label: 'Reports'},
                    {date_start: '15/06/2027 19:30', date_end: null, label: 'Réunion fin de saison'},
                    {date_start: '25/06/2027 19:30', date_end: null, label: 'Finales + récompenses'}
                ]
            }
        };
    },
    computed: {
        currentSeason() {
            const now = new Date();
            // Même règle que AnnualCalendar : de juillet à décembre, la saison
            // affichée est celle qui démarre en septembre de l'année en cours
            const startYear = now.getMonth() <= 5 ? now.getFullYear() - 1 : now.getFullYear();
            return `${startYear}-${startYear + 1}`;
        },
        importantEvents() {
            return this.eventsBySeason[this.currentSeason] || [];
        }
    }
};
