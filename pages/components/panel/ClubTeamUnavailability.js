import {onError, onSuccess} from "../../../toaster.js";

/**
 * Issue #101 — Indisponibilités des équipes du club (responsable de club).
 *
 * Panneau club-scoped : le responsable de club déclare les dates où une de ses
 * équipes ne peut pas jouer. Les endpoints REST (blacklistteam/*) restreignent
 * côté serveur aux équipes du club.
 */
export default {
    template: `
      <div>
        <p class="text-xl">Indisponibilités des équipes du club</p>
        <p class="text-sm opacity-70 mb-4">
          Déclarez les dates où une de vos équipes ne peut pas jouer.
          Ces indisponibilités sont prises en compte lors de la génération du calendrier.
        </p>
        <div class="flex flex-wrap gap-2">
          <div v-if="unavailabilities.length > 0" class="w-full">
            <div class="bg-base-200 border border-2 border-base-300 p-4 flex flex-wrap gap-2">
              <div v-for="item in unavailabilities" :key="item.id" class="card shadow-xl">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-ban mr-2"></i>{{ item.closed_date }}</h2>
                  <p>{{ item.libelle_equipe }}</p>
                </div>
                <div class="card-actions justify-end mr-2 mb-2">
                  <button class="btn btn-error" @click="onRemoveClick(item)">
                    <i class="fas fa-trash mr-2"></i>supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info w-full">
            <span>Aucune indisponibilité déclarée pour les équipes de votre club.</span>
          </div>
          <div class="w-full">
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmit">
              <label class="font-bold" for="equipe">Équipe</label>
              <select id="equipe" name="id_team" v-model="newItem.id_team" required>
                <option value="">Sélectionner une équipe</option>
                <option v-for="team in clubTeams" :key="team.id_equipe" :value="team.id_equipe">
                  {{ team.team_full_name }}
                </option>
              </select>

              <label class="font-bold" for="closed_date">Date d'indisponibilité</label>
              <input type="date" id="closed_date" name="closed_date" v-model="newItem.closed_date" required>

              <button type="submit" class="btn btn-primary">Ajouter une indisponibilité</button>
            </form>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            clubTeams: [],
            unavailabilities: [],
            newItem: {id_team: '', closed_date: ''},
            isLoading: false,
        };
    },
    methods: {
        fetchClubTeams() {
            axios
                .get("/rest/action.php/club/getMyClubTeams")
                .then((response) => {
                    this.clubTeams = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des équipes :", error);
                });
        },
        fetchUnavailabilities() {
            axios
                .get("/rest/action.php/blacklistteam/getMyClubBlacklistTeam")
                .then((response) => {
                    this.unavailabilities = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des indisponibilités :", error);
                });
        },
        // Convertit yyyy-mm-dd (input date) vers dd/mm/yyyy (attendu par le backend)
        toFrenchDate(isoDate) {
            if (!isoDate) {
                return '';
            }
            const [y, m, d] = isoDate.split('-');
            return `${d}/${m}/${y}`;
        },
        handleSubmit() {
            const formData = new FormData();
            formData.append("id_team", this.newItem.id_team);
            formData.append("closed_date", this.toFrenchDate(this.newItem.closed_date));
            axios
                .post(`/rest/action.php/blacklistteam/saveBlacklistTeam`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchUnavailabilities();
                    this.newItem = {id_team: '', closed_date: ''};
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
        onRemoveClick(item) {
            const formData = new FormData();
            formData.append("id", item.id);
            axios
                .post(`/rest/action.php/blacklistteam/removeBlacklistTeam`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchUnavailabilities();
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
    },
    created() {
        this.fetchClubTeams();
        this.fetchUnavailabilities();
    },
};
