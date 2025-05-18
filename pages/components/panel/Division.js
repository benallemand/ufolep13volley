export default {
    components: {
        'navbar-limit-date': () => import('../navbar/LimitDate.js'),
        'rank-panel': () => import('./Rank.js'),
        'matchs-panel': () => import('./Matchs.js'),
    },
    template: `
      <div>
        <navbar-limit-date :key="'navbar-' + code_competition" :code_competition="code_competition"></navbar-limit-date>
        <rank-panel :key="'rank-' + code_competition + '-' + division" :division="division" :code_competition="code_competition"></rank-panel>
        <matchs-panel :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-panel>
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
