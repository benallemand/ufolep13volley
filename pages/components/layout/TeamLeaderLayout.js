// Fonction utilitaire pour vérifier l'authentification côté client
async function checkAuthentication() {
    try {
        const response = await axios.get('/rest/action.php/usermanager/getCurrentUserDetails');
        return response.data && response.data.id_user;
    } catch (error) {
        return false;
    }
}

// Fonction pour rediriger vers la page de login
function redirectToLogin(reason = "Vous devez être connecté pour accéder à cette page") {
    const currentUrl = window.location.href;
    const redirect = encodeURIComponent(currentUrl);
    const reasonEncoded = encodeURIComponent(reason);
    window.location.href = `/#/login?redirect=${redirect}&reason=${reasonEncoded}`;
}

// Définition des routes pour les pages "my_..."
const routes = [
    // Routes pour my_page
    {
        path: '/dashboard',
        component: () => import('../panel/TeamLeaderDashboard.js')
    },
    {
        path: '/history',
        component: () => import('../table/History.js')
    },
    {
        path: '/preferences',
        component: () => import('../panel/Preferences.js'),
        props: () => ({fetchUrl: "/rest/action.php/usermanager/getMyPreferences"})
    },
    {
        path: '/timeslots',
        component: () => import('../panel/Timeslots.js'),
        props: () => ({fetchUrl: "/rest/action.php/timeslot/get_my_timeslots"})
    },
    {
        path: '/players',
        component: () => import('../panel/Players.js'),
        props: () => ({fetchUrl: "/rest/action.php/player/getMyPlayers"})
    },
    {path: '/player/:id', component: () => import('../form/Player.js')},
    {path: '/player/new', component: () => import('../form/Player.js')},
    {path: '/team', component: () => import('../panel/TeamLeaderTeam.js')},
    {path: '/team/edit', component: () => import('../form/Team.js')},
    {
        path: '/team_matchs',
        component: () => import('../list/Matchs.js'),
        props: () => ({fetchUrl: "/rest/action.php/matchmgr/getMesMatches"})
    },
    {
        path: '/club_matchs',
        component: () => import('../list/Matchs.js'),
        props: () => ({fetchUrl: "/rest/action.php/matchmgr/getMyClubMatches"})
    },
    {path: '*', redirect: '/dashboard'}
];

// Configuration du router
const router = new VueRouter({
    mode: 'hash',
    routes
});

export default {
    components: {
        'team-leader-navbar': () => import('../navbar/TeamLeader.js')
    },
    router,
    data() {
        return {
            isAuthenticated: false,
            isLoading: true
        }
    },
    async created() {
        // Vérifier l'authentification au chargement du composant
        this.isAuthenticated = await checkAuthentication();
        this.isLoading = false;
        
        if (!this.isAuthenticated) {
            redirectToLogin("Vous devez être connecté en tant que responsable d'équipe pour accéder à cette page");
        }
    },
    template: `
      <div class="container mx-auto p-4">
        <div v-if="isLoading" class="flex justify-center items-center min-h-screen">
          <div class="loading loading-spinner loading-lg"></div>
          <span class="ml-2">Vérification de l'authentification...</span>
        </div>
        <div v-else-if="isAuthenticated">
          <team-leader-navbar></team-leader-navbar>
          <router-view></router-view>
        </div>
        <div v-else class="flex justify-center items-center min-h-screen">
          <div class="alert alert-warning">
            <span class="badge badge-warning badge-sm">⚠️</span>
            <span>Redirection vers la page de connexion...</span>
          </div>
        </div>
      </div>
    `
};
