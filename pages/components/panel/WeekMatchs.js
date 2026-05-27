import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'matchs-table': defineAsyncComponent(() => import('../table/Matchs.js')),
    },
    template: `
        <matchs-table :key="week-matchs" :fetch-url="matchesFetchUrl"></matchs-table>
    `,
    computed: {
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getWeekMatches`;
        }
    }
};
