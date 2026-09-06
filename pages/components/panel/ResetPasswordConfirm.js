// Atterrissage du lien de réinitialisation envoyé par email (issue #266).
// Avant, ce lien pointait directement sur /rest/action.php/usermanager/reset_my_password
// et l'utilisateur voyait du JSON brut dans son navigateur.
export default {
    template: `
      <div class="p-4 max-w-md mx-auto">
        <h1 class="text-2xl font-bold mb-4">Réinitialisation du mot de passe</h1>

        <div v-if="isLoading" class="flex items-center gap-3">
          <span class="loading loading-spinner loading-md text-primary"></span>
          <span>Vérification du lien en cours…</span>
        </div>

        <div v-else-if="successMessage" class="space-y-4">
          <div class="alert alert-success">
            <i class="fas fa-circle-check"></i>
            <span>{{ successMessage }}</span>
          </div>
          <router-link to="/login" class="btn btn-primary btn-sm">
            <i class="fas fa-right-to-bracket"></i> Aller à la page de connexion
          </router-link>
        </div>

        <div v-else class="space-y-4">
          <div class="alert alert-error">
            <i class="fas fa-triangle-exclamation"></i>
            <span>{{ errorMessage }}</span>
          </div>
          <router-link to="/reset_password" class="btn btn-primary btn-sm">
            <i class="fas fa-rotate-right"></i> Demander un nouveau lien
          </router-link>
        </div>
      </div>
    `,
    data() {
        return {
            isLoading: true,
            successMessage: null,
            errorMessage: null,
        };
    },
    watch: {
        // Le composant est réutilisé si l'on arrive sur la même route avec un
        // autre couple id/hash : on refait la vérification à chaque changement.
        '$route.query': {
            handler() {
                this.confirm();
            },
            immediate: true
        }
    },
    methods: {
        confirm() {
            const {id, hash} = this.$route.query;
            this.isLoading = true;
            this.successMessage = null;
            this.errorMessage = null;
            if (!id || !hash) {
                this.isLoading = false;
                this.errorMessage = "Le lien est incomplet. Merci de refaire une demande.";
                return;
            }
            axios
                .get('/rest/action.php/usermanager/reset_my_password', {params: {id, hash}})
                .then((response) => {
                    this.successMessage = response.data.Message;
                })
                .catch((error) => {
                    this.errorMessage = error.response?.data?.message
                        || "Le lien n'est pas ou plus valide.";
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
    },
};
