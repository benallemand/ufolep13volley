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
    {
        path: '/web-sites',
        component: () => import('../table/WebSites.js'),
    },
    {
        path: '/gymnasiums',
        component: () => import('../table/Gymnasiums.js'),
    },
    {
        path: '/information',
        component: () => import('../panel/Info.js'),
    },
    {
        path: '/commission',
        component: () => import('../table/Commission.js'),
    },
    {
        path: '/accident',
        component: () => import('../panel/Accident.js'),
    },
    {
        path: '/general-rules',
        component: () => import('../panel/GeneralRules.js'),
    },

    {path: '*', redirect: '/home'}

];

const router = new VueRouter({
    mode: 'hash',
    routes
});

export default {
    components: {
        'main-navbar': () => import('../navbar/Main.js'),
        'main-footer': () => import('../footer/Main.js'),
    },
    router,
    template: `
      <div class="p-2">
        <main-navbar></main-navbar>
        <router-view></router-view>
        <main-footer/>
      </div>
    `
};
