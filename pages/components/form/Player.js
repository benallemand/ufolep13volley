import {onError, onSuccess} from "../../../toaster.js";

export default {
    template: `
      <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Modifier les informations du joueur</h1>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Prénom</span>
            </label>
            <input v-model="form.prenom" type="text" class="input input-bordered" required/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Nom</span>
            </label>
            <input v-model="form.nom" type="text" class="input input-bordered" required/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Sexe</span>
            </label>
            <select v-model="form.sexe" class="select select-bordered">
              <option value="M">Masculin</option>
              <option value="F">Féminin</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Club</span>
            </label>
            <select v-model="form.id_club" class="select select-bordered">
              <option v-for="club in clubs" :key="club.id" :value="club.id">
                {{ club.nom }}
              </option>
            </select>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Numéro de licence</span>
            </label>
            <input v-model="form.num_licence" type="text" class="input input-bordered"/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Département d'affiliation</span>
            </label>
            <select v-model="form.departement_affiliation" class="select select-bordered">
              <option value="13">13 - bouches du rhône</option>
              <option value="83">83 - var</option>
            </select>
          </div>
          <div class="flex">
            <label class="cursor-pointer label">
              <span class="label-text mr-2">Afficher la photo sur la fiche équipe</span>
              <input v-model="form.show_photo"
                     type="checkbox"
                     class="checkbox"
                     true-value="1"
                     false-value="0"
              />
            </label>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Téléphone</span>
            </label>
            <input v-model="form.telephone" type="tel" class="input input-bordered"/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Email</span>
            </label>
            <input v-model="form.email" type="email" class="input input-bordered"/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Téléphone secondaire</span>
            </label>
            <input v-model="form.telephone2" type="tel" class="input input-bordered"/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Email secondaire</span>
            </label>
            <input v-model="form.email2" type="email" class="input input-bordered"/>
          </div>
          <div class="flex flex-row-reverse gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Enregistrer</button>
            <router-link :to="'/players'"
                         class="btn btn-error">
              <i class="fas fa-xmark"></i> Annuler
            </router-link>
          </div>
        </form>
      </div>
    `,
    data() {
        return {
            form: {},
            clubs: [],
        };
    },
    created() {
        this.fetchClubs();
        if(this.$route.params.id) {
            const playerId = this.$route.params.id;
            this.fetchPlayer(playerId);
        }
    },
    methods: {
        fetchPlayer(playerId) {
            axios
                .get("/rest/action.php/player/getMyPlayers")
                .then((response) => {
                    this.form = response.data.find((p) => p.id == playerId) || {};
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des joueurs :", error);
                });
        },
        fetchClubs() {
            axios.get("/rest/action.php/club/get")
                .then((response) => {
                    this.clubs = response.data || [];
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des clubs :", error);
                });
        },
        submitForm() {
            const formData = new FormData();
            formData.append('id_team', null);
            for (const key in this.form) {
                switch (key) {
                    case 'id_team':
                    case 'prenom':
                    case 'nom':
                    case 'num_licence':
                    case 'date_homologation':
                    case 'sexe':
                    case 'departement_affiliation':
                    case 'id_club':
                    case 'show_photo':
                    case 'telephone':
                    case 'email':
                    case 'telephone2':
                    case 'email2':
                    case 'id':
                    case 'dirtyFields':
                        formData.append(key, this.form[key]);
                        break;
                }
            }
            axios.post('/rest/action.php/player/update_player', formData)
                .then(
                    response => {
                        onSuccess(this, response)
                        history.back();
                    }
                )
                .catch(error => {
                    onError(this, error)
                });
        },
    },
};
