// Demande de réinitialisation de mot de passe (issue #266).
// Remplace reset_password.php + js/{reset_password,controller/reset_password,
// view/form/reset_password}.js (ExtJS).
export default {
    template: `
      <div class="p-4 max-w-md mx-auto">
        <h1 class="text-2xl font-bold mb-4">Mot de passe oublié</h1>

        <!-- Reprend la garde de reset_password.php : la page n'a pas de sens
             pour un utilisateur déjà connecté. -->
        <div v-if="isConnected" class="alert alert-info">
          <span>Vous êtes déjà connecté.</span>
          <router-link to="/home" class="btn btn-sm">Retour à l'accueil</router-link>
        </div>

        <template v-else>
          <div v-if="successMessage" class="alert alert-success mb-4">
            <i class="fas fa-envelope-circle-check"></i>
            <span class="whitespace-pre-line">{{ successMessage }}</span>
          </div>
          <div v-if="errorMessage" class="alert alert-error mb-4">
            <i class="fas fa-triangle-exclamation"></i>
            <span>{{ errorMessage }}</span>
          </div>

          <form v-if="!successMessage" @submit.prevent="submitForm" class="space-y-4">
            <div class="form-control">
              <label class="label" for="reset-email">
                <span class="label-text">Email</span>
              </label>
              <input id="reset-email"
                     v-model.trim="email"
                     type="email"
                     name="user_email"
                     class="input input-bordered w-full"
                     placeholder="mon.adresse@exemple.fr"
                     required
                     autocomplete="email"/>
              <label class="label">
                <span class="label-text-alt text-base-content/60">
                  L'adresse email associée à votre compte.
                </span>
              </label>
            </div>
            <div class="flex justify-between gap-2">
              <router-link to="/login" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left"></i> Retour à la connexion
              </router-link>
              <button type="submit" class="btn btn-primary" :disabled="isLoading || !email">
                <span v-if="isLoading" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-paper-plane"></i>
                Envoyer
              </button>
            </div>
          </form>

          <router-link v-if="successMessage" to="/home" class="btn btn-primary btn-sm">
            <i class="fas fa-home"></i> Retour à l'accueil
          </router-link>
        </template>
      </div>
    `,
    data() {
        return {
            email: '',
            isLoading: false,
            isConnected: false,
            successMessage: null,
            errorMessage: null,
        };
    },
    created() {
        this.checkSession();
    },
    methods: {
        checkSession() {
            axios
                .get('/session_user.php')
                .then((response) => {
                    this.isConnected = !response.data.error;
                })
                .catch(() => {
                    this.isConnected = false;
                });
        },
        submitForm() {
            this.isLoading = true;
            this.successMessage = null;
            this.errorMessage = null;
            const formData = new FormData();
            formData.append('user_email', this.email);
            axios
                .post('/rest/action.php/usermanager/request_reset_password', formData)
                .then((response) => {
                    this.isLoading = false;
                    // Le backend signale le succès par une Exception de code 201
                    // (convention rest/action.php) : axios la voit comme un 2xx.
                    this.successMessage = response.data.message;
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.errorMessage = error.response?.data?.message
                        || "Une erreur est survenue, merci de réessayer plus tard.";
                });
        },
    },
};
