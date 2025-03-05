import {onError, onSuccess} from "../../toaster.js";

export default {
    template: `
      <div>
        <p class="text-xl">Préférences</p>
        <div class="flex flex-wrap gap-2">
          <div>
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmitPassword">
              <label class="font-bold" for="new_password">Nouveau mot de passe</label>
              <input class="input input-bordered" type="password" id="new_password" name="new_password" v-model="password.new_password" required>
              <label class="font-bold" for="new_password_again">Confirmer le mot de passe</label>
              <input class="input input-bordered" type="password" id="new_password_again" name="new_password_again" v-model="password.new_password_again" required>
              <button type="submit" class="btn btn-primary">changer</button>
            </form>
          </div>
          <div>
            <form class="flex flex-col gap-4 p-4 max-w-md mx-auto border rounded-lg shadow-lg"
                  @submit.prevent="handleSubmitSettings">
              <div class="flex items-center gap-2">
                <input type="checkbox" id="is_remind_matches" name="is_remind_matches"
                       v-model="settings.is_remind_matches">
                <label for="is_remind_matches">Recevoir un rappel des matches de la semaine (le lundi)</label>
              </div>
              <button type="submit" class="btn btn-primary">sauver</button>
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
            settings: null,
            password: null,
        };
    },
    methods: {
        fetchSettings() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.settings = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        },
        handleSubmitPassword() {
            const formData = this.password;
            axios
                .post(`/rest/action.php/usermanager/modifierMonMotDePasse`, formData, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then((response) => {
                    onSuccess(this, response)
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        handleSubmitSettings() {
            const formData = this.settings;
            axios
                .post(`/rest/action.php/usermanager/saveMyPreferences`, formData, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then((response) => {
                    onSuccess(this, response)
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        resetSettings() {
            this.settings = {
                is_remind_matches: false
            }
        },
        resetPassword() {
            this.password = {
                new_password: '',
                new_password_again: '',
            }
        },
    },
    created() {
        this.fetchSettings();
        this.resetSettings()
        this.resetPassword()
    },
};
