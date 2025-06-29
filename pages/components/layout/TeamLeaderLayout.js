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
    template: `
      <div class="container mx-auto p-4">
        <team-leader-navbar></team-leader-navbar>
        <router-view></router-view>
      </div>
    `
};
