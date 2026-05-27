export default {
    components: {
        'matchs-table': Vue.defineAsyncComponent(() => import('../table/Matchs.js')),
    },
    template: `
        <matchs-table :key="last-results" :fetch-url="matchesFetchUrl"></matchs-table>
    `,
    computed: {
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getLastResults`;
        }
    }
};
