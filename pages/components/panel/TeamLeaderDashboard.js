import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'team-leader-infos': defineAsyncComponent(() => import('./TeamLeaderInfos.js')),
        'team-leader-alerts': defineAsyncComponent(() => import('./TeamLeaderAlerts.js'))
    },
    data() {
        return {
            isClubLeader: false,
            isTeamLeader: false,
            isActingAs: false,
            loaded: false,
        };
    },
    methods: {
        fetchSession() {
            axios.get('/session_user.php')
                .then((response) => {
                    if (response.data && !response.data.error) {
                        // rôles cumulables (issue #245)
                        this.isClubLeader = response.data.is_club_leader === true;
                        this.isTeamLeader = response.data.is_team_leader === true;
                        this.isActingAs = response.data.is_acting_as === true;
                    }
                })
                .catch(() => {})
                .finally(() => {
                    this.loaded = true;
                });
        },
    },
    created() {
        this.fetchSession();
    },
    // Les rôles se cumulent : un responsable de club voit l'espace club, un
    // responsable d'équipe voit le dashboard équipe, un compte qui cumule les
    // deux voit les deux sections. Pour gérer le détail d'une équipe dont il
    // n'a pas le compte, le responsable de club passe par « gérer une équipe ».
    template: `
      <div>
        <div v-if="!loaded" class="flex justify-center p-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        <template v-else>
          <div v-if="isClubLeader" :class="{ 'mb-8': isTeamLeader || isActingAs }">
            <h1 class="text-2xl font-bold mb-2"><i class="fas fa-people-group mr-2"></i>Espace responsable de club</h1>
            <p class="opacity-70 mb-6">
              Gérez les comptes et les contraintes de votre club ci-dessous. Pour gérer le détail
              d'une équipe (effectif, créneaux, matchs, coordonnées), utilisez
              <strong>« gérer une équipe »</strong> dans le menu : vous agirez alors en tant que son responsable.
            </p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <router-link to="/club_team_leaders" class="card bg-base-100 shadow-xl hover:shadow-2xl transition">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-user-gear mr-2"></i>Comptes responsables</h2>
                  <p>Créer et rattacher un compte responsable à chaque équipe du club.</p>
                </div>
              </router-link>
              <router-link to="/club_gymnasium_closures" class="card bg-base-100 shadow-xl hover:shadow-2xl transition">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-lock mr-2"></i>Fermetures gymnases</h2>
                  <p>Déclarer les dates de fermeture des gymnases de vos équipes.</p>
                </div>
              </router-link>
              <router-link to="/club_team_unavailability" class="card bg-base-100 shadow-xl hover:shadow-2xl transition">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-ban mr-2"></i>Indispos équipes</h2>
                  <p>Déclarer les dates où une équipe du club ne peut pas jouer.</p>
                </div>
              </router-link>
              <router-link to="/club_matchs" class="card bg-base-100 shadow-xl hover:shadow-2xl transition">
                <div class="card-body">
                  <h2 class="card-title"><i class="fas fa-volleyball mr-2"></i>Matchs du club</h2>
                  <p>Consulter l'ensemble des matchs des équipes du club.</p>
                </div>
              </router-link>
            </div>
          </div>
          <div v-if="isTeamLeader || isActingAs || !isClubLeader">
            <team-leader-alerts></team-leader-alerts>
            <team-leader-infos></team-leader-infos>
          </div>
        </template>
      </div>
    `
};
