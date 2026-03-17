export default {
    template: `
      <table class="table table-pin-rows">
        <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Équipe</th>
          <th class="text-center">Pts</th>
          <th class="text-center">MJ</th>
          <th class="text-center">G</th>
          <th class="text-center">P</th>
          <th class="text-center">Diff. Sets</th>
          <th class="hidden md:table-cell text-center">Sets Pour</th>
          <th class="hidden md:table-cell text-center">Sets Contre</th>
          <th class="hidden md:table-cell text-center">Reports</th>
          <th v-if="canManagePoints" class="hidden md:table-cell text-center">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="team in ranks"
            :key="team.rang"
            :class="{
                'bg-success/5': team.rang <= 2,
                'bg-error/5': ranks.length > 0 && team.rang > ranks.length - 2
            }">
          <td class="text-center">{{ team.rang }}</td>
          <td>
            <router-link :to="'/teams/' + team.id_equipe" class="font-medium link link-primary hover:link-hover">
              {{ team.equipe }}
            </router-link>
          </td>
          <td class="text-center font-bold">
            <div class="flex flex-col">
              <span>{{ team.points }}</span>
              <div v-if="team.penalites > 0">
              <span class="badge badge-error text-[8px]">
                {{ Number(team.points) + team.penalites }} - {{ team.penalites }} pts de pénalité
              </span>
              </div>
              <div v-if="isCompetitionFinished() && isExactDeuce(team)">
              <span class="badge badge-warning text-[8px]">
                vérifier la rencontre directe
              </span>
              </div>
            </div>
          </td>
          <td class="text-center">{{ team.joues }}</td>
          <td class="text-center text-green-500">{{ team.gagnes }}</td>
          <td class="text-center text-red-500">
            {{ team.perdus }}
            <span v-if="team.matches_lost_by_forfeit_count > 0" class="badge badge-info text-[8px]">
                dont {{ team.matches_lost_by_forfeit_count }} par forfait
            </span>
          </td>
          <td class="text-center">{{ team.diff }}</td>
          <td class="hidden md:table-cell text-center">{{ team.sets_pour }}</td>
          <td class="hidden md:table-cell text-center">{{ team.sets_contre }}</td>
          <td class="hidden md:table-cell text-center">{{ team.report_count }}</td>
          <td v-if="canManagePoints" class="hidden md:table-cell text-center">
            <div class="flex flex-col gap-1">
              <div>
                <span class="text-xs mr-1">Pts:</span>
                <button class="btn btn-xs btn-error mx-1" @click="modifierPoints(team, -1)">-1</button>
                <button class="btn btn-xs btn-success mx-1" @click="modifierPoints(team, 1)">+1</button>
              </div>
              <div>
                <span class="text-xs mr-1">Reports:</span>
                <button class="btn btn-xs btn-error mx-1" @click="modifierReports(team, -1)">-1</button>
                <button class="btn btn-xs btn-success mx-1" @click="modifierReports(team, 1)">+1</button>
              </div>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    `,
    props: {
        code_competition: {
            type: String,
            required: true,
        },
        division: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            ranks: [],
            searchQuery: "",
            user: null,
            limitDate: '',
        };
    },
    computed: {
        canManagePoints() {
            const allowedProfiles = ["ADMINISTRATEUR", "SUPPORT"];
            return this.user && allowedProfiles.includes(this.user.profile_name);
        },
    },
    methods: {
        fetchLimitDate() {
            axios
                .get(
                    `/rest/action.php/limitdate/getLimitDates`
                )
                .then((response) => {
                    this.limitDate = response.data.find(limitDate => limitDate.code_competition === this.code_competition).date_limite;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        },
        fetch() {
            axios
                .get(
                    `/rest/action.php/rank/getRank?competition=${this.code_competition}&division=${this.division}`
                )
                .then((response) => {
                    this.ranks = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        },
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null
                    } else {
                        this.user = response.data;
                    }
                })
                .catch(() => {
                });
        },
        isExactDeuce(rank) {
            return this.ranks.filter((curRank) => curRank.points === rank.points && curRank.diff === rank.diff).length > 1;
        },
        isCompetitionFinished() {
            return this.limitDate && new Date() > new Date(this.limitDate.split('/').reverse().join('-'));
        },
        modifierPoints(team, delta) {
            const action = delta > 0 ? 'removePenalty' : 'addPenalty';
            const formData = new FormData();
            formData.append('compet', this.code_competition);
            formData.append('id_equipe', team.id_equipe);
            axios
                .post(`/rest/action.php/rank/${action}`, formData)
                .then(() => {
                    this.fetch();
                })
                .catch((error) => {
                    console.error("Erreur lors de la modification des points :", error);
                });
        },
        modifierReports(team, delta) {
            const action = delta > 0 ? 'incrementReportCount' : 'decrementReportCount';
            const formData = new FormData();
            formData.append('compet', this.code_competition);
            formData.append('id_equipe', team.id_equipe);
            axios
                .post(`/rest/action.php/rank/${action}`, formData)
                .then(() => {
                    this.fetch();
                })
                .catch((error) => {
                    console.error("Erreur lors de la modification des reports :", error);
                });
        }
    },
    created() {
        this.fetchUserDetails();
        this.fetch();
        this.fetchLimitDate();
    },
};