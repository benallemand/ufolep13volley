import { defineAsyncComponent } from 'vue';

/**
 * Planning de la semaine (issue #265, lot 2).
 * Remplace `js/view/timeslot/WeekScheduleGrid.js` — écran de consultation, sans
 * édition : la grille est utilisée sans `save-url` ni `delete-url`.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Planning de la semaine"
        entity-label="créneau"
        :columns="columns"
        fetch-url="/rest/action.php/timeslot/getWeekSchedule"/>
    `,
    data() {
        return {
            columns: [
                { key: 'gymnasium', label: 'Gymnase' },
                { key: 'dayOfWeek', label: 'Jour' },
                { key: 'startTime', label: 'Heure' },
                { key: 'team', label: 'Équipe' },
            ],
        };
    },
};
