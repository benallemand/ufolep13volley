import {onError, onSuccess} from "../../../toaster.js";

export default {
    template: `
      <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Modifier l'équipe</h1>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Club de rattachement</span>
            </label>
            <select v-model="form.id_club" class="select select-bordered">
              <option v-for="club in clubs" :key="club.id" :value="club.id">
                {{ club.nom }}
              </option>
            </select>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Lien vers le site du club ou de l'équipe</span>
            </label>
            <input v-model="form.web_site" type="text" class="input input-bordered"/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Photo d'équipe</span>
            </label>
            <input type="file" class="file-input file-input-xs file-input-primary" @change="handleFileChange" />
          </div>
          <div class="flex justify-between">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Enregistrer</button>
            <router-link :to="'/team'"
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
            file: null,
            clubs: [],
        };
    },
    created() {
        this.fetchMyTeam();
        this.fetchClubs();
    },
    methods: {
        fetchMyTeam() {
            axios
                .get("/rest/action.php/team/getMyTeam")
                .then((response) => {
                    this.form = response.data[0] || {};
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement de l'équipe :", error);
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
        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.file = file;
            }
        },
        submitForm() {
            const formData = new FormData();
            for (const key in this.form) {
                switch (key) {
                    case 'id_club':
                    case 'web_site':
                        formData.append(key, this.form[key]);
                        break;
                }
            }
            if (this.file) {
                formData.append('photo', this.file);
            }
            axios.post('/rest/action.php/team/saveTeam', formData)
                .then(
                    response => {
                        onSuccess(this, response)
                    }
                )
                .catch(error => {
                    onError(this, error)
                });
        },
    },
};
