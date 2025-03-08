import TeamLeaderNavbar from './components/team-leader-navbar.js';
import TimeslotsPanel from "./components/timeslots-panel.js";

Vue.component('timeslots-panel', TimeslotsPanel);
Vue.component('team-leader-navbar', TeamLeaderNavbar);

const routes = [
    {
        path: "/",
        component: TimeslotsPanel,
        props: {fetchUrl: "/rest/action.php/timeslot/get_my_timeslots"}
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