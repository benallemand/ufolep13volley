export default {
    components: {
        'limit-date-navbar': () => import('../navbar/LimitDate.js'),
        'matchs-list': () => import('../list/Matchs.js'),
    },
    template: `
      <div>
        <limit-date-navbar :key="'navbar-' + code_competition" :code_competition="code_competition"></limit-date-navbar>
        <matchs-list :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-list>
      </div>
    `,
    data() {
        return {
            division: 1,
            code_competition: this.$route.params.code_competition,
        };
    },
    watch: {
        '$route.params': {
            handler(newParams) {
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
