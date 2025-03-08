import TeamLeaderNavbar from './components/team-leader-navbar.js';
import TeamLeaderTeamPanel from './components/team-leader-team-panel.js';
import TeamEditPanel from "./components/team-edit-panel.js";

Vue.component('team-leader-navbar', TeamLeaderNavbar);
Vue.component('team-leader-team-panel', TeamLeaderTeamPanel);

const routes = [
    {path: "/", component: TeamLeaderTeamPanel},
    {path: "/edit", component: TeamEditPanel},
];
const router = new VueRouter({
    mode: "hash",
    routes,
});
new Vue({
    router,
    el: '#my-page-app',
});