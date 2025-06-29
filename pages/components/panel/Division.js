export default {
    components: {
        'limit-date-navbar': () => import('../navbar/LimitDate.js'),
        'rank-table': () => import('../table/Rank.js'),
        'matchs-table': () => import('../table/Matchs.js'),
    },
    template: `
      <div>
        <limit-date-navbar :key="'navbar-' + code_competition" :code_competition="code_competition"></limit-date-navbar>
        <rank-table :key="'rank-' + code_competition + '-' + division" :division="division" :code_competition="code_competition"></rank-table>
        <matchs-table :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-table>
      </div>
    `,
    data() {
        return {
            division: this.$route.params.division,
            code_competition: this.$route.params.code_competition,
        };
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                this.division = newParams.division;
                this.code_competition = newParams.code_competition;
            },
            immediate: true
        }
    },
    computed: {
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getMatches?competition=${this.code_competition}&division=${this.division}`;
        }
    }
};
