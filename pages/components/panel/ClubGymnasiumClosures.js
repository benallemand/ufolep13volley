import {onError, onSuccess} from "../../../toaster.js";

/**
 * Issue #101 — Fermetures des gymnases du club (responsable de club).
 *
 * Panneau club-scoped (indépendant de l'équipe sélectionnée) : le responsable
 * de club déclare les dates de fermeture des gymnases utilisés par ses équipes.
 * Les endpoints REST (blacklistcourt/*) restreignent côté serveur aux gymnases
 * du club.
 */
export default {
    template: `
      <div>
        <p class="text-xl">Fermetures des gymnases du club</p>
        <p class="text-sm opacity-70 mb-4">
          Déclarez les dates où un gymnase utilisé par vos équipes est fermé.
          Ces fermetures sont prises en compte lors de la génération du calendrier.
        </p>
        <div class="flex flex-wrap gap-2">
          <div v-if="closures.length > 0" class="w-full">
            <div class="bg-base-200 border border-2 border-base-300 p-4 flex flex-wrap gap-2">
              <div v-for="closure in closures" :key="closure.id" class="card shadow-xl">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-lock mr-2"></i>{{ closure.closed_date }}</h2>
                  <p>{{ closure.libelle_gymnase }}</p>
                </div>
                <div class="card-actions justify-end mr-2 mb-2">
                  <button class="btn btn-error" @click="onRemoveClick(closure)">
                    <i class="fas fa-trash mr-2"></i>supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info w-full">
            <span>Aucune fermeture déclarée pour les gymnases de votre club.</span>
          </div>
          <div class="w-full">
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmit">
              <label class="font-bold" for="gymnase">Gymnase</label>
              <select id="gymnase" name="id_gymnase" v-model="newClosure.id_gymnase" required>
                <option value="">Sélectionner un gymnase</option>
                <option v-for="gymnasium in clubGymnasiums" :key="gymnasium.id" :value="gymnasium.id">
                  {{ gymnasium.full_name }}
                </option>
              </select>

              <label class="font-bold" for="closed_date">Date de fermeture</label>
              <input type="date" id="closed_date" name="closed_date" v-model="newClosure.closed_date" required>

              <button type="submit" class="btn btn-primary">Ajouter une fermeture</button>
            </form>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            clubGymnasiums: [],
            closures: [],
            newClosure: {id_gymnase: '', closed_date: ''},
            isLoading: false,
        };
    },
    methods: {
        fetchClubGymnasiums() {
            axios
                .get("/rest/action.php/blacklistcourt/getMyClubGymnasiums")
                .then((response) => {
                    this.clubGymnasiums = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des gymnases :", error);
                });
        },
        fetchClosures() {
            axios
                .get("/rest/action.php/blacklistcourt/getMyClubBlacklistGymnase")
                .then((response) => {
                    this.closures = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des fermetures :", error);
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
            formData.append("id_gymnase", this.newClosure.id_gymnase);
            formData.append("closed_date", this.toFrenchDate(this.newClosure.closed_date));
            axios
                .post(`/rest/action.php/blacklistcourt/saveBlacklistGymnase`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchClosures();
                    this.newClosure = {id_gymnase: '', closed_date: ''};
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
        onRemoveClick(closure) {
            const formData = new FormData();
            formData.append("id", closure.id);
            axios
                .post(`/rest/action.php/blacklistcourt/removeBlacklistGymnase`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchClosures();
                })
                .catch((error) => {
                    onError(this, error);
                });
        },
    },
    created() {
        this.fetchClubGymnasiums();
        this.fetchClosures();
    },
};
