import {onError, onSuccess} from "../../toaster.js";

export default {
    template: `
      <div>
        <p class="text-xl">Créneaux</p>
        <div class="flex flex-wrap gap-2">
          <div v-if="timeslots.length > 0">
            <div class="bg-base-200 border border-2 border-base-300 p-4 flex flex-wrap gap-2">
              <div v-for="timeslot in timeslots" :key="timeslot.id" class="card w-96 shadow-xl">
                <div class="card-body">
                  <h2 class="card-title">{{ timeslot.jour }} {{ timeslot.heure }}</h2>
                  <p>à {{ timeslot.gymnasium_full_name }}</p>
                  <p>priorité {{ timeslot.usage_priority }}</p>
                  <p class="alert alert-warning" v-if="timeslot.has_time_constraint === 1"><i class="fas fa-triangle-exclamation mr-2"/><span>contrainte horaire</span>
                  </p>
                </div>
                <div class="card-actions justify-end mr-2 mb-2">
                  <button class="btn btn-error"
                          @click="onRemoveTimeslotClick(timeslot)"><i class="fas fa-trash mr-2"></i>supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div>
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmit">
              <label class="font-bold" for="gymnase">Gymnase</label>
              <select id="gymnase" name="id_gymnase" v-model="newCreneau.id_gymnase" required>
                <option value="">Sélectionner un gymnase</option>
                <option v-for="gymnasium in all_gymnasiums" :key="gymnasium.id" :value="gymnasium.id">
                  {{ gymnasium.full_name }}
                </option>
              </select>

              <label class="font-bold" for="jour">Jour de la semaine</label>
              <select id="jour" name="jour" v-model="newCreneau.jour" required>
                <option value="">Sélectionner un jour</option>
                <option value="Lundi">Lundi</option>
                <option value="Mardi">Mardi</option>
                <option value="Mercredi">Mercredi</option>
                <option value="Jeudi">Jeudi</option>
                <option value="Vendredi">Vendredi</option>
              </select>

              <label class="font-bold" for="heure">Heure d'accueil</label>
              <input type="time" id="heure" name="heure" v-model="newCreneau.heure" step="900" min="19:00" max="21:30"
                     required>

              <div class="flex items-center gap-2">
                <input type="checkbox" id="has_time_constraint" name="has_time_constraint"
                       v-model="newCreneau.has_time_constraint">
                <label for="has_time_constraint">Contrainte horaire de fin de match</label>
              </div>

              <label class="font-bold" for="usage_priority">Priorité d'utilisation</label>
              <select id="usage_priority" name="usage_priority" v-model="newCreneau.usage_priority" required>
                <option value="1">1 (remplir en priorité)</option>
                <option value="2">2</option>
                <option value="3">3 (si vraiment pas d'autre possibilité)</option>
              </select>
              <button type="submit" class="btn btn-primary">Ajouter Créneau</button>
            </form>
          </div>
        </div>
      </div>
    `,
    props: {
        fetchUrl: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            all_gymnasiums: [],
            timeslots: [],
            newCreneau: null
        };
    },
    methods: {
        fetchAllGymnasiums() {
            axios
                .get("/rest/action.php/court/getGymnasiums")
                .then((response) => {
                    this.all_gymnasiums = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement:", error);
                });
        },
        fetchTeamTimeslots() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.timeslots = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        },
        onRemoveTimeslotClick(timeslot) {
            let action = 'removeTimeSlot';
            const formData = new FormData();
            formData.append("id", timeslot.id);
            axios
                .post(`/rest/action.php/timeslot/${action}`, formData)
                .then((response) => {
                    onSuccess(this, response)
                    this.fetchTeamTimeslots();
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        handleSubmit() {
            let action = 'saveTimeSlot';
            const formData = this.newCreneau;
            axios
                .post(`/rest/action.php/timeslot/${action}`, formData, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then((response) => {
                    onSuccess(this, response)
                    this.fetchTeamTimeslots();
                    this.resetNewCreneau()
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        resetNewCreneau() {
            this.newCreneau = {
                id_gymnase: '',
                jour: '',
                heure: '',
                has_time_constraint: false,
                usage_priority: '',
            }
        }
    },
    created() {
        this.fetchTeamTimeslots();
        this.fetchAllGymnasiums();
        this.resetNewCreneau()
    },
};
