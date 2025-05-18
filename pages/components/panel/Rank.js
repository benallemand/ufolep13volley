export default {
    template: `
      <table class="table w-full">
        <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Équipe</th>
          <th class="text-center">Pts</th>
          <template v-if="!light">
            <th class="text-center">MJ</th>
            <th class="text-center">G</th>
            <th class="text-center">P</th>
            <th class="text-center">Diff. Sets</th>
            <th class="text-center">Sets Pour</th>
            <th class="text-center">Sets Contre</th>
            <th class="text-center">Reports</th>
            <th v-if="canManagePoints" class="text-center">Actions</th>
          </template>
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
          <td>{{ team.equipe }}</td>
          <td class="text-center font-bold">
            <div class="flex flex-col">
              <span>{{ team.points }}</span>
              <div v-if="team.penalites > 0">
              <span class="badge badge-error text-[8px]">
                {{ Number(team.points) + team.penalites }} - {{ team.penalites }} pts de pénalité
              </span>
              </div>
            </div>
          </td>
          <template v-if="!light">
            <td class="text-center">{{ team.joues }}</td>
            <td class="text-center text-green-500">{{ team.gagnes }}</td>
            <td class="text-center text-red-500">{{ team.perdus }}</td>
            <td class="text-center">{{ team.diff }}</td>
            <td class="text-center">{{ team.sets_pour }}</td>
            <td class="text-center">{{ team.sets_contre }}</td>
            <td class="text-center">{{ team.report_count }}</td>
            <td v-if="canManagePoints" class="text-center">
              <button class="btn btn-xs btn-error mx-1" @click="modifierPoints(team, -1)">-1</button>
              <button class="btn btn-xs btn-success mx-1" @click="modifierPoints(team, 1)">+1</button>
            </td>
          </template>
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
        light: {
            type: Boolean,
            required: false,
            default: false,
        }
    },
    data() {
        return {
            ranks: [],
            searchQuery: "",
            user: null,
        };
    },
    computed: {
        canManagePoints() {
            const allowedProfiles = ["ADMINISTRATEUR", "SUPPORT"];
            return this.user && allowedProfiles.includes(this.user.profile_name);
        },
    },
    methods: {
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
    },
    created() {
        this.fetchUserDetails();
        this.fetch();
    },
};