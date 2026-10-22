/**
 * Archives des classements et résultats des demi-saisons passées (issue #435).
 *
 * Classements figés en fin de demi-saison (`classements_archives`, source
 * FROZEN), ou reconstitués après coup depuis les matchs archivés (REBUILT,
 * sans pénalités ni reports : la base ne les avait plus). Résultats : les
 * matchs archivés de la division sur sa période, avec les noms de l'époque.
 *
 * Le choix courant vit dans l'adresse (`?saison=…&demi=…&competition=…&division=…`),
 * pour qu'un lien vers une division passée se partage.
 */
export default {
    template: `
      <div class="space-y-4" data-testid="archives">
        <h1 class="text-2xl font-bold">Archives des classements</h1>

        <div v-if="loading" class="flex justify-center py-12">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <div v-else-if="!periods.length" class="alert">
          Aucune demi-saison archivée pour l'instant.
        </div>
        <template v-else>
          <div class="flex flex-wrap gap-2 items-center">
            <select class="select select-sm" data-testid="archives-period"
                    :value="periodKey" @change="selectPeriod($event.target.value)">
              <option v-for="p in periods" :key="keyOf(p)" :value="keyOf(p)">{{ p.label }}</option>
            </select>
            <div role="tablist" class="tabs tabs-box tabs-sm" data-testid="archives-competitions">
              <a v-for="c in period.competitions" :key="c.code_competition" role="tab"
                 :class="['tab', { 'tab-active': competition && c.code_competition === competition.code_competition }]"
                 @click="selectCompetition(c.code_competition)">{{ c.libelle }}</a>
            </div>
          </div>

          <div v-if="competition" role="tablist" class="tabs tabs-border tabs-sm flex-wrap"
               data-testid="archives-divisions">
            <a v-for="d in divisions" :key="d.value" role="tab"
               :class="['tab', { 'tab-active': d.value === division }]"
               @click="selectDivision(d.value)">{{ d.label }}</a>
          </div>

          <div v-if="competition && competition.rebuilt && !isFinals" role="alert"
               class="alert alert-warning text-sm" data-testid="archives-rebuilt">
            Classement reconstitué après coup à partir des matchs archivés : le site
            n'avait pas gardé celui de l'époque. Les pénalités sont celles des comptes
            rendus de réunion ; les reports ne sont pas connus.
          </div>

          <div v-if="!isFinals" class="overflow-x-auto">
            <table class="table table-sm" data-testid="archives-rank">
              <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Équipe</th>
                <th class="text-center">Pts</th>
                <th class="text-center">MJ</th>
                <th class="text-center">G</th>
                <th class="text-center">P</th>
                <th class="text-center">Diff. sets</th>
                <th class="hidden md:table-cell text-center">Sets pour</th>
                <th class="hidden md:table-cell text-center">Sets contre</th>
                <th v-if="!competition.rebuilt" class="hidden md:table-cell text-center">Reports</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="team in ranks" :key="team.id_equipe">
                <td class="text-center">{{ team.rang }}</td>
                <td>
                  <div class="font-medium">{{ team.equipe }}</div>
                  <div class="text-xs text-base-content/60">{{ team.club }}</div>
                </td>
                <td class="text-center font-bold">
                  {{ team.points }}
                  <div v-if="Number(team.penalites) > 0">
                    <span class="badge badge-error text-[8px]">
                      {{ Number(team.points) + Number(team.penalites) }} - {{ team.penalites }} pts de pénalité
                    </span>
                  </div>
                </td>
                <td class="text-center">{{ team.joues }}</td>
                <td class="text-center text-green-500">{{ team.gagnes }}</td>
                <td class="text-center text-red-500">
                  {{ team.perdus }}
                  <span v-if="Number(team.matches_lost_by_forfeit_count) > 0" class="badge badge-info text-[8px]">
                    dont {{ team.matches_lost_by_forfeit_count }} par forfait
                  </span>
                </td>
                <td class="text-center">{{ team.diff }}</td>
                <td class="hidden md:table-cell text-center">{{ team.sets_pour }}</td>
                <td class="hidden md:table-cell text-center">{{ team.sets_contre }}</td>
                <td v-if="!competition.rebuilt" class="hidden md:table-cell text-center">{{ team.reports }}</td>
              </tr>
              </tbody>
            </table>
          </div>

          <div>
            <h2 class="text-lg font-semibold mb-2">{{ isFinals ? 'Phases finales' : 'Résultats' }}</h2>
            <div v-if="!results.length" class="text-sm text-base-content/60">Aucun résultat archivé.</div>
            <table v-else class="table table-sm" data-testid="archives-results">
              <tbody>
              <tr v-for="m in results" :key="m.code_match">
                <td class="whitespace-nowrap text-xs">{{ m.date }}</td>
                <td class="text-right" :class="{ 'font-semibold': m.score_dom > m.score_ext }">{{ m.equipe_dom }}</td>
                <td class="text-center whitespace-nowrap font-bold">
                  <span v-if="m.played">{{ m.score_dom }} - {{ m.score_ext }}</span>
                  <span v-else class="text-base-content/50">non joué</span>
                </td>
                <td :class="{ 'font-semibold': m.score_ext > m.score_dom }">{{ m.equipe_ext }}</td>
                <td class="hidden md:table-cell text-xs text-base-content/60">{{ m.sets }}</td>
              </tr>
              </tbody>
            </table>
          </div>

          <p class="text-sm text-base-content/70">
            Les champions des saisons plus anciennes sont au
            <router-link to="/hall-of-fame" class="link link-primary">palmarès</router-link>.
          </p>
        </template>
      </div>
    `,
    data() {
        return {
            loading: true,
            periods: [],
            periodKey: null,
            competitionCode: null,
            division: null,
            ranks: [],
            results: [],
        };
    },
    computed: {
        period() {
            return this.periods.find((p) => this.keyOf(p) === this.periodKey) || null;
        },
        competition() {
            return this.period?.competitions.find((c) => c.code_competition === this.competitionCode) || null;
        },
        divisions() {
            if (!this.competition) {
                return [];
            }
            const list = this.competition.divisions.map((d) => ({ value: d, label: 'Division ' + d }));
            if (this.competition.has_finals) {
                list.push({ value: 'finales', label: 'Phases finales' });
            }
            return list;
        },
        isFinals() {
            return this.division === 'finales';
        },
    },
    methods: {
        keyOf(p) {
            return p.saison + '|' + p.demi_saison;
        },
        selectPeriod(key) {
            this.periodKey = key;
            const codes = (this.period?.competitions || []).map((c) => c.code_competition);
            this.selectCompetition(codes.includes(this.competitionCode) ? this.competitionCode : codes[0]);
        },
        selectCompetition(code) {
            this.competitionCode = code;
            const values = this.divisions.map((d) => d.value);
            this.selectDivision(values.includes(this.division) ? this.division : values[0]);
        },
        selectDivision(division) {
            this.division = division;
            this.$router.replace({
                query: {
                    saison: this.period.saison, demi: String(this.period.demi_saison),
                    competition: this.competitionCode, division,
                },
            });
            this.load();
        },
        load() {
            const params = {
                saison: this.period.saison,
                demi_saison: this.period.demi_saison,
                code_competition: this.competitionCode,
                division: this.division,
            };
            this.ranks = [];
            this.results = [];
            if (!this.isFinals) {
                axios.get('/rest/action.php/rank/getArchivedRank', { params })
                    .then((response) => { this.ranks = response.data; });
            }
            axios.get('/rest/action.php/rank/getArchivedResults', { params })
                .then((response) => { this.results = response.data; });
        },
    },
    created() {
        axios.get('/rest/action.php/rank/getArchivePeriods')
            .then((response) => {
                this.periods = response.data;
                if (!this.periods.length) {
                    return;
                }
                const q = this.$route.query;
                const asked = this.periods.find((p) => p.saison === q.saison && String(p.demi_saison) === String(q.demi));
                this.competitionCode = q.competition || null;
                this.division = q.division || null;
                this.selectPeriod(this.keyOf(asked || this.periods[0]));
            })
            .finally(() => { this.loading = false; });
    },
};
