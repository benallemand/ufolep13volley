import TeamLeaderNavbar from './components/team-leader-navbar.js';
import MatchsPanel from './components/matchs-panel.js';

Vue.component('team-leader-navbar', TeamLeaderNavbar);
Vue.component('matchs-panel', MatchsPanel);

new Vue({
    el: '#my-page-app',
});