import TeamLeaderAlertsPanel from './components/team-leader-alerts-panel.js';
import TeamLeaderInfosPanel from './components/team-leader-infos-panel.js';
import TeamLeaderNavbar from './components/team-leader-navbar.js';
import TeamLeaderTeamPanel from './components/team-leader-team-panel.js';

Vue.component('team-leader-alerts-panel', TeamLeaderAlertsPanel);
Vue.component('team-leader-infos-panel', TeamLeaderInfosPanel);
Vue.component('team-leader-navbar', TeamLeaderNavbar);
Vue.component('team-leader-team-panel', TeamLeaderTeamPanel);

new Vue({
    el: '#my-page-app',
});