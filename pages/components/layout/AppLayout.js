const routes = [
    // Routes pour my_page
    {
        path: '/home',
        component: () => import('../panel/Home.js')
    },
    {
        path: '/login',
        component: () => import('../form/Login.js')
    },
    {
        path: '/divisions/:code_competition/:division',
        component: () => import('../panel/Division.js')
    },
    {
        path: '/finals/:code_competition',
        component: () => import('../panel/Finals.js'),
    },
    {
        path: '/last-results',
        component: () => import('../panel/LastResults.js'),
    },
    {
        path: '/week-matchs',
        component: () => import('../panel/WeekMatchs.js'),
    },
    {
        path: '/hall-of-fame',
        component: () => import('../panel/HallOfFame.js'),
    },

    {path: '*', redirect: '/home'}

];

const router = new VueRouter({
    mode: 'hash',
    routes
});

export default {
    components: {
        'main-navbar': () => import('../navbar/Main.js')
    },
    router,
    template: `
      <div class="container mx-auto p-4">
        <main-navbar></main-navbar>
        <router-view></router-view>
      </div>
    `
};
