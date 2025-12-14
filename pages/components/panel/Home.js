export default {
    components: {
        'news': () => import('../table/News.js'),
        'photos': () => import('../carousel/Photos.js'),
        'annual-calendar': () => import('../calendar/AnnualCalendar.js'),
    },
    template: `
      <div class="flex flex-col items-center gap-8">
        <news/>
        <annual-calendar :events="importantEvents"/>
        <photos/>
      </div>
    `,
    data() {
        return {
            importantEvents: [
                {date_start: '03/09/2025 19:30', date_end: null, label: 'Réunion calendrier'},
                {date_start: '03/09/2025 23:59', date_end: '03/10/2025 23:59', label: 'Inscriptions championnats'},
                {date_start: '03/09/2025 23:59', date_end: '28/11/2025 23:59', label: 'Inscriptions coupe 4x4 Khoury Hanna'},
                {date_start: '06/10/2025 19:30', date_end: null, label: 'Réunion début de saison'},
                {date_start: '09/10/2025 00:00', date_end: '13/10/2025 23:59', label: 'Tournoi(s) de bienvenue'},
                {date_start: '03/11/2025 00:00', date_end: '19/12/2025 23:59', label: 'Championnats'},
                {date_start: '20/12/2025 00:00', date_end: '04/01/2026 23:59', label: 'Vacances'},
                {date_start: '05/01/2026 00:00', date_end: '09/01/2026 23:59', label: 'Reports'},
                {date_start: '05/01/2026 19:30', date_end: null, label: 'Tirage des coupes'},
                {date_start: '12/01/2026 19:30', date_end: null, label: 'Réunion fin de demi-saison'},
                {date_start: '19/01/2026 00:00', date_end: '13/02/2026 23:59', label: 'Coupes'},
                {date_start: '14/02/2026 00:00', date_end: '27/02/2026 23:59', label: 'Vacances'},
                {date_start: '02/03/2026 00:00', date_end: '10/04/2026 23:59', label: 'Championnats'},
                {date_start: '11/04/2026 00:00', date_end: '26/04/2026 23:59', label: 'Vacances'},
                {date_start: '27/04/2026 00:00', date_end: '15/05/2026 23:59', label: 'Championnats'},
                {date_start: '18/05/2026 00:00', date_end: '22/05/2026 23:59', label: 'Reports'},
                {date_start: '26/05/2026 19:30', date_end: null, label: 'Réunion fin de saison'},
                {date_start: '01/06/2026 00:00', date_end: '19/06/2026 23:59', label: 'Phases finales Coupes'},
                {date_start: '26/06/2026 19:00', date_end: null, label: 'Finales + récompenses'}
            ]
        };
    },
    computed: {}
};
