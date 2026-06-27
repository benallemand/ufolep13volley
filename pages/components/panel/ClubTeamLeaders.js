import {onError, onSuccess} from "../../../toaster.js";

/**
 * Issue #101 — Attribution des comptes responsables d'équipe (responsable de club).
 *
 * Panneau club-scoped : pour chaque équipe du club, liste les comptes
 * RESPONSABLE_EQUIPE rattachés et permet d'en créer/rattacher un (par email)
 * ou de le détacher. Les endpoints REST (usermanager/*) restreignent côté
 * serveur aux équipes du club.
 */
export default {
    template: `
      <div>
        <p class="text-xl">Comptes responsables d'équipe</p>
        <p class="text-sm opacity-70 mb-4">
          Rattachez un compte responsable à chaque équipe de votre club. Si le compte
          n'existe pas encore, il est créé et ses identifiants sont envoyés par email.
        </p>
        <div class="flex flex-wrap gap-4">
          <div class="w-full bg-base-200 border border-2 border-base-300 p-4 flex flex-wrap gap-4">
            <div v-for="team in teams" :key="team.id_equipe" class="card shadow-xl w-full md:w-96">
              <div class="card-body">
                <h2 class="card-title"><i class="fas fa-people-group mr-2"></i>{{ team.team_full_name }}</h2>
                <div v-if="team.leaders.length > 0">
                  <div v-for="leader in team.leaders" :key="leader.user_id"
                       class="flex items-center justify-between gap-2 border-b py-1">
                    <span><i class="fas fa-user mr-2"></i>{{ leader.login }}</span>
                    <button class="btn btn-xs btn-error" @click="onDetachClick(team, leader)">
                      <i class="fas fa-link-slash mr-1"></i>détacher
                    </button>
                  </div>
                </div>
                <p v-else class="text-sm opacity-60"><i class="fas fa-triangle-exclamation mr-2"></i>aucun compte rattaché</p>
              </div>
            </div>
          </div>
          <div class="w-full">
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmit">
              <p class="font-bold text-lg">Rattacher un compte</p>
              <label class="font-bold" for="equipe">Équipe</label>
              <select id="equipe" name="id_equipe" v-model="newLink.id_equipe" required>
                <option value="">Sélectionner une équipe</option>
                <option v-for="team in teams" :key="team.id_equipe" :value="team.id_equipe">
                  {{ team.team_full_name }}
                </option>
              </select>

              <label class="font-bold" for="email">Email du responsable</label>
              <input type="email" id="email" name="email" v-model="newLink.email"
                     placeholder="responsable@exemple.fr" required>

              <button type="submit" class="btn btn-primary">Créer / rattacher</button>
            </form>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            teams: [],
            newLink: {id_equipe: '', email: ''},
            isLoading: false,
        };
    },
    methods: {
        fetchTeamLeaders() {
            axios
                .get("/rest/action.php/usermanager/getMyClubTeamLeaders")
                .then((response) => {
                    this.teams = this.groupByTeam(response.data);
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        },
        // Regroupe les lignes (équipe × compte) par équipe ; user_id null => aucun compte.
        groupByTeam(rows) {
            const map = new Map();
            rows.forEach((row) => {
                if (!map.has(row.id_equipe)) {
                    map.set(row.id_equipe, {
                        id_equipe: row.id_equipe,
                        team_full_name: row.team_full_name,
                        leaders: [],
                    });
                }
                if (row.user_id) {
                    map.get(row.id_equipe).leaders.push({
                        user_id: row.user_id,
                        login: row.login,
                        email: row.email,
                    });
                }
            });
            return Array.from(map.values());
        },
        handleSubmit() {
            const formData = new FormData();
            formData.append("id_equipe", this.newLink.id_equipe);
            formData.append("email", this.newLink.email);
            axios
                .post(`/rest/action.php/usermanager/attachClubTeamLeader`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchTeamLeaders();
                    this.newLink = {id_equipe: '', email: ''};
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
        onDetachClick(team, leader) {
            const formData = new FormData();
            formData.append("user_id", leader.user_id);
            formData.append("id_equipe", team.id_equipe);
            axios
                .post(`/rest/action.php/usermanager/detachClubTeamLeader`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchTeamLeaders();
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
    },
    created() {
        this.fetchTeamLeaders();
    },
};
