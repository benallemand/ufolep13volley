// Classement général d'une coupe — qualification pour les phases finales (issue #266).
// Remplace rank_for_cup.php + js/{rank_for_cup,controller/rank_for_cup,
// view/grid/rank_for_cup,store/rank_for_cup,model/rank_for_cup}.js (ExtJS).
const QUALIFIED_COUNT = 16;

export default {
    template: `
      <div>
        <div class="text-center mb-3">
          <div class="text-xs uppercase tracking-wide text-base-content/50">{{ competitionLabel }}</div>
          <h2 class="text-xl font-bold text-primary">Classement général</h2>
        </div>

        <div class="alert alert-success mb-4 py-2">
          <i class="fas fa-trophy"></i>
          <span>Les {{ qualifiedCount }} premiers sont qualifiés pour les phases finales.</span>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
          <input v-model.trim="searchQuery"
                 type="text"
                 class="input input-bordered input-sm w-full sm:w-64"
                 placeholder="Rechercher une équipe, une poule…"/>
          <span class="text-sm text-base-content/60">
            {{ filteredRanks.length }} équipe(s)
          </span>
        </div>

        <div v-if="isLoading" class="flex justify-center py-8">
          <span class="loading loading-spinner loading-lg text-primary"></span>
        </div>

        <div v-else-if="!ranks.length" class="alert alert-warning">
          <i class="fas fa-circle-info"></i>
          <span>Aucun classement disponible pour cette compétition.</span>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-xs md:table-sm table-pin-rows">
            <thead>
            <!-- En-têtes groupés, comme la grille ExtJS d'origine -->
            <tr>
              <th rowspan="2" class="text-center align-bottom">#</th>
              <th rowspan="2" class="align-bottom">Équipe</th>
              <th colspan="3" class="text-center border-x border-base-300">Poule</th>
              <th colspan="3" class="text-center border-x border-base-300">Pondération</th>
              <th colspan="10" class="hidden md:table-cell text-center border-l border-base-300">Détails</th>
            </tr>
            <tr>
              <th class="text-center border-l border-base-300">Rang</th>
              <th class="text-center">Matchs</th>
              <th class="text-center border-r border-base-300">Poule</th>
              <th class="text-center">Pts</th>
              <th class="text-center">Diff. sets</th>
              <th class="text-center border-r border-base-300">Diff. pts</th>
              <th class="hidden md:table-cell text-center">Pts</th>
              <th class="hidden md:table-cell text-center">Diff. sets</th>
              <th class="hidden md:table-cell text-center">Diff. pts</th>
              <th class="hidden md:table-cell text-center">Joués</th>
              <th class="hidden md:table-cell text-center">Gagnés</th>
              <th class="hidden md:table-cell text-center">Perdus</th>
              <th class="hidden md:table-cell text-center">Sets pour</th>
              <th class="hidden md:table-cell text-center">Sets contre</th>
              <th class="hidden md:table-cell text-center">Pts pour</th>
              <th class="hidden md:table-cell text-center">Pts contre</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="team in filteredRanks"
                :key="team.id_equipe"
                :class="{'bg-success/10': isQualified(team)}">
              <td class="text-center font-bold">{{ team.rang }}</td>
              <td>
                <router-link :to="'/teams/' + team.id_equipe"
                             class="font-medium link link-primary hover:link-hover">
                  {{ team.equipe }}
                </router-link>
                <i v-if="isQualified(team)"
                   class="fas fa-trophy text-success ml-1"
                   title="Qualifié pour les phases finales"></i>
              </td>
              <td class="text-center border-l border-base-300">{{ team.rang_poule }}</td>
              <td class="text-center">{{ team.nb_matchs }}</td>
              <td class="text-center border-r border-base-300">
                <router-link :to="'/divisions/' + team.code_competition + '/' + team.division"
                             class="link link-primary hover:link-hover">
                  {{ team.division }}
                </router-link>
              </td>
              <td class="text-center font-bold">{{ toFixed(team.points_ponderes) }}</td>
              <td class="text-center">{{ toFixed(team.diff_sets_ponderes) }}</td>
              <td class="text-center border-r border-base-300">{{ toFixed(team.diff_points_ponderes) }}</td>
              <td class="hidden md:table-cell text-center">{{ team.points }}</td>
              <td class="hidden md:table-cell text-center">{{ team.diff_sets }}</td>
              <td class="hidden md:table-cell text-center">{{ team.diff_points }}</td>
              <td class="hidden md:table-cell text-center">{{ team.joues }}</td>
              <td class="hidden md:table-cell text-center text-green-500">{{ team.gagnes }}</td>
              <td class="hidden md:table-cell text-center text-red-500">{{ team.perdus }}</td>
              <td class="hidden md:table-cell text-center">{{ team.sets_pour }}</td>
              <td class="hidden md:table-cell text-center">{{ team.sets_contre }}</td>
              <td class="hidden md:table-cell text-center">{{ team.points_pour }}</td>
              <td class="hidden md:table-cell text-center">{{ team.points_contre }}</td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    `,
    data() {
        return {
            code_competition: this.$route.params.code_competition,
            ranks: [],
            competitionLabel: '',
            searchQuery: '',
            isLoading: true,
            qualifiedCount: QUALIFIED_COUNT,
        };
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                if (this.code_competition !== newParams.code_competition) {
                    this.code_competition = newParams.code_competition;
                }
                this.fetch();
                this.fetchCompetitionLabel();
            },
            immediate: true
        }
    },
    computed: {
        filteredRanks() {
            if (!this.searchQuery) {
                return this.ranks;
            }
            // Recherche multi-termes séparés par des virgules, comme la grille ExtJS.
            const terms = this.searchQuery.split(',')
                .map((term) => term.trim().toLowerCase())
                .filter((term) => term.length);
            return this.ranks.filter((team) => {
                const haystack = `${team.equipe} ${team.division}`.toLowerCase();
                return terms.some((term) => haystack.includes(term));
            });
        },
    },
    methods: {
        isQualified(team) {
            return Number(team.rang) <= QUALIFIED_COUNT;
        },
        toFixed(value) {
            const number = Number(value);
            return Number.isNaN(number) ? value : number.toFixed(1);
        },
        fetch() {
            this.isLoading = true;
            axios
                .get('/rest/action.php/rank/sort_cup_rank', {
                    params: {code_competition: this.code_competition}
                })
                .then((response) => {
                    this.ranks = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement du classement général :", error);
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        fetchCompetitionLabel() {
            axios
                .get('/rest/action.php/rank/getDivisions')
                .then((response) => {
                    const row = response.data.find((x) => x.code_competition === this.code_competition);
                    this.competitionLabel = row ? row.libelle_competition : this.code_competition;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des compétitions :", error);
                });
        },
    },
};
