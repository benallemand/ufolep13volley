import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'limit-date-navbar': defineAsyncComponent(() => import('../navbar/LimitDate.js')),
        'commission-member': defineAsyncComponent(() => import('../navbar/CommissionMember.js')),
        'rank-table': defineAsyncComponent(() => import('../table/Rank.js')),
        'matchs-table': defineAsyncComponent(() => import('../table/Matchs.js')),
        'to-schedule-matchs-table': defineAsyncComponent(() => import('../table/ToScheduleMatchs.js')),
    },
    template: `
      <div>
        <!-- En-tête : championnat / division + navigation -->
        <div class="flex items-center justify-between gap-2 mb-3">
          <router-link v-if="prevDivision !== null"
                       :to="'/divisions/' + code_competition + '/' + prevDivision"
                       class="btn btn-sm btn-ghost gap-1" title="Division précédente">
            <span>«</span><span class="hidden sm:inline">Div. {{ prevDivision }}</span>
          </router-link>
          <span v-else class="btn btn-sm btn-ghost btn-disabled invisible">«</span>

          <div class="text-center">
            <div class="text-xs uppercase tracking-wide text-base-content/50">{{ competitionLabel }}</div>
            <h2 class="text-xl font-bold text-primary">Division {{ division }}</h2>
          </div>

          <router-link v-if="nextDivision !== null"
                       :to="'/divisions/' + code_competition + '/' + nextDivision"
                       class="btn btn-sm btn-ghost gap-1" title="Division suivante">
            <span class="hidden sm:inline">Div. {{ nextDivision }}</span><span>»</span>
          </router-link>
          <span v-else class="btn btn-sm btn-ghost btn-disabled invisible">»</span>
        </div>

        <!-- Accès rapide à toutes les divisions -->
        <div v-if="divisions.length > 1" class="flex flex-wrap justify-center gap-1 mb-4">
          <router-link v-for="d in divisions" :key="d"
                       :to="'/divisions/' + code_competition + '/' + d"
                       class="btn btn-xs"
                       :class="d === division ? 'btn-primary' : 'btn-outline btn-primary'">
            D{{ d }}
          </router-link>
        </div>

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
            divisions: [],
            competitionLabel: '',
        };
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                const compChanged = this.code_competition !== newParams.code_competition;
                this.division = newParams.division;
                this.code_competition = newParams.code_competition;
                if (compChanged || !this.divisions.length) {
                    this.fetchDivisions();
                }
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
        currentIndex() {
            return this.divisions.indexOf(this.division);
        },
        prevDivision() {
            return this.currentIndex > 0 ? this.divisions[this.currentIndex - 1] : null;
        },
        nextDivision() {
            return (this.currentIndex >= 0 && this.currentIndex < this.divisions.length - 1)
                ? this.divisions[this.currentIndex + 1] : null;
        },
    },
    methods: {
        compareDivisions(a, b) {
            const na = parseInt(a, 10);
            const nb = parseInt(b, 10);
            if (na !== nb) return na - nb;
            return String(a).localeCompare(String(b)); // ex. 7a avant 7b
        },
        fetchDivisions() {
            axios
                .get('/rest/action.php/rank/getDivisions')
                .then((response) => {
                    const rows = response.data.filter((x) => x.code_competition === this.code_competition);
                    this.competitionLabel = rows.length ? rows[0].libelle_competition : this.code_competition;
                    this.divisions = rows.map((x) => x.division).sort(this.compareDivisions);
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des divisions :", error);
                });
        },
    },
};
