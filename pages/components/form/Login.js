export default {
    template: `
      <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Page de connexion</h1>

        <!-- Affichage du message d'erreur/raison si présent -->
        <div v-if="reason" class="alert alert-warning mb-4">
          <span class="badge badge-warning badge-sm">⚠️</span>
          <span>{{ reason }}</span>
        </div>

        <form :action="$url('/login.php')" method="post" class="space-y-4">
          <!-- Champs cachés pour la redirection -->
          <input v-if="redirect" type="hidden" name="redirect" :value="redirect"/>
          <input v-if="reason" type="hidden" name="reason" :value="reason"/>

          <div class="flex flex-col *:w-full">
            <label class="flex select-none items-center justify-between px-1 py-2">
              <span class="text-sm">Login ou email</span>
            </label>
            <input name="login" type="text" class="input" required/>
          </div>
          <div class="flex flex-col *:w-full">
            <label class="flex select-none items-center justify-between px-1 py-2">
              <span class="text-sm">Mot de passe</span>
            </label>
            <input name="password" type="password" class="input" required/>
          </div>
          <div class="flex justify-between gap-2">
            <router-link to="/reset_password" class="btn btn-sm btn-ghost">
              <i class="fas fa-question"></i>mot de passe oublié
            </router-link>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i>Connexion
            </button>
          </div>
        </form>

        <!-- Information sur la redirection si présente -->
        <div v-if="redirect" class="mt-4 text-sm text-base-content/70">
          <div class="flex items-center gap-2">
            <span class="badge badge-info badge-xs">ℹ️</span>
            <span>Après connexion, vous serez redirigé vers : {{ decodeURIComponent(redirect) }}</span>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            redirect: null,
            reason: null
        }
    },
    created() {
        // Récupérer les paramètres redirect et reason de l'URL
        this.redirect = this.$route.query.redirect || null;
        this.reason = this.$route.query.reason || null;
    },
    methods: {
        decodeURIComponent(str) {
            return decodeURIComponent(str);
        }
    },
};
