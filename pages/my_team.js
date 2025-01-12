import TeamLeaderNavbar from './components/team-leader-navbar.js';
import TeamLeaderTeamPanel from './components/team-leader-team-panel.js';

Vue.component('team-leader-navbar', TeamLeaderNavbar);
Vue.component('team-leader-team-panel', TeamLeaderTeamPanel);

new Vue({
    el: '#my-page-app',
});