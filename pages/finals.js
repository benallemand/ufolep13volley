import MainNavbar from "./components/main-navbar.js";
import MatchsPanel from "./components/matchs-panel.js";

Vue.component('main-navbar', MainNavbar);
Vue.component('matchs-panel', MatchsPanel);

new Vue({
    el: '#my-page-app',
    data() {
        return {
            code_competition: new URLSearchParams(window.location.search).get('code_competition'),
        };
    },
    computed: {
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getMatches?competition=${this.code_competition}&division=1`;
        }
    },
});