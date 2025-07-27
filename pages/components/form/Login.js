export default {
    template: `
      <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Page de connexion</h1>
        <form action="/login.php" method="post" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Nom d'utilisateur</span>
            </label>
            <input name="login" type="text" class="input input-bordered" required/>
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Mot de passe</span>
            </label>
            <input name="password" type="password" class="input input-bordered" required/>
          </div>
          <div class="flex justify-between gap-2">
            <a href="/reset_password.php" class="btn btn-sm btn-ghost"><i class="fas fa-question"></i>mot de passe oublié</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Connexion</button>
          </div>
        </form>
      </div>
    `,
    data() {
        return {}
    },
    created() {
    },
    methods: {
    },
};
