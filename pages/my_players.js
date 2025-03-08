import TeamLeaderNavbar from './components/team-leader-navbar.js';
import PlayersPanel from "./components/players-panel.js";
import PlayerEditPanel from "./components/player-edit-panel.js";

Vue.component('players-panel', PlayersPanel);
Vue.component('player-edit-panel', PlayerEditPanel);
Vue.component('team-leader-navbar', TeamLeaderNavbar);

const routes = [
    {
        path: "/",
        component: PlayersPanel,
        props: {fetchUrl: "/rest/action.php/player/getMyPlayers"}
    },
    {path: "/player/:id", component: PlayerEditPanel},
    {path: "/player/new", component: PlayerEditPanel},
];
const router = new VueRouter({
    mode: "hash",
    routes,
});
new Vue({
    router,
    el: '#my-page-app',
});