import TeamLeaderNavbar from './components/team-leader-navbar.js';
import TeamLeaderPlayersPanel from "./components/team-leader-players-panel.js";
import TeamLeaderPlayerEditPanel from "./components/team-leader-player-edit-panel.js";

Vue.component('team-leader-players-panel', TeamLeaderPlayersPanel);
Vue.component('team-leader-player-edit-panel', TeamLeaderPlayerEditPanel);
Vue.component('team-leader-navbar', TeamLeaderNavbar);

const routes = [
    {path: "/", component: TeamLeaderPlayersPanel},
    {path: "/player/:id", component: TeamLeaderPlayerEditPanel},
];
const router = new VueRouter({
    mode: "hash",
    routes,
});
new Vue({
    router,
    el: '#my-page-app',
});