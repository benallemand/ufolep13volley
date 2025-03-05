import TeamLeaderNavbar from './components/team-leader-navbar.js';
import PreferencesPanel from "./components/preferences-panel.js";

Vue.component('preferences-panel', PreferencesPanel);
Vue.component('team-leader-navbar', TeamLeaderNavbar);

const routes = [
    {
        path: "/",
        component: PreferencesPanel,
        props: {fetchUrl: "/rest/action.php/usermanager/getMyPreferences"}
    },
];
const router = new VueRouter({
    mode: "hash",
    routes,
});
new Vue({
    router,
    el: '#my-page-app',
});