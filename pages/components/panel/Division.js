export default {
    components: {
        'limit-date-navbar': () => import('../navbar/LimitDate.js'),
        'commission-member': () => import('../navbar/CommissionMember.js'),
        'rank-table': () => import('../table/Rank.js'),
        'matchs-table': () => import('../table/Matchs.js'),
        'to-schedule-matchs-table': () => import('../table/ToScheduleMatchs.js'),
    },
    template: `
      <div>
        <div class="flex flex-wrap gap-4 mb-4">
          <limit-date-navbar :key="'navbar-' + code_competition" :code_competition="code_competition" class="flex-1"></limit-date-navbar>
          <commission-member :key="'commission-' + code_competition + '-' + division" :code_competition="code_competition" :division="division" class="flex-none"></commission-member>
        </div>
        <rank-table :key="'rank-' + code_competition + '-' + division" :division="division" :code_competition="code_competition"></rank-table>
        <matchs-table :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-table>
        <to-schedule-matchs-table :key="'to-schedule-matchs-' + code_competition + '-' + division" :fetch-url="toScheduleMatchesFetchUrl"></to-schedule-matchs-table>
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
        },
        toScheduleMatchesFetchUrl() {
            return `/rest/action.php/matchmgr/getToScheduleMatches?competition=${this.code_competition}&division=${this.division}`;
        },
    }
};
