import TeamLeaderNavbar from './components/team-leader-navbar.js';
import HistoryPanel from "./components/history-panel.js";

Vue.component('team-leader-navbar', TeamLeaderNavbar);
Vue.component('history-panel', HistoryPanel);

new Vue({
    el: '#my-page-app',
});