export default {
    template: `
      <div>
        <div class="flex justify-center">
          <a href="/" class="center">
            <img alt="Ufolep" src="../images/svg/logo-ufolep-vectorizer-no-background.svg" style="max-height:150px;">
          </a>
        </div>
        <div class="navbar bg-base-100 shadow-sm">
          <div class="navbar-start">
            <a href="/">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-arrow-left"></i>retour</span>
              </div>
            </a>
          </div>
          <div class="navbar-center flex gap-2">
            <div class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-user"></i>mon équipe: {{ currentTeam.nom_equipe }}<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
                <li v-for="team in teams" :key="team.id_equipe">
                  <a @click="switchTeam(team.id_equipe)">{{ team.nom_equipe }}</a>
                </li>
              </ul>
            </div>
            <div class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span>gestion<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
                <li>
                  <router-link to="/players"><span><i class="mr-2 fas fa-user"></i>effectif</span></router-link>
                </li>
                <li>
                  <router-link to="/timeslots"><span><i class="mr-2 fas fa-clock"></i>créneaux</span></router-link>
                </li>
              </ul>
            </div>
            <div class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span>infos<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
                <li><a href="/rest/action.php/team/download_calendar"><span><i class="mr-2 fas fa-download"></i>calendrier</span></a>
                </li>
                <li>
                  <router-link to="/team"><span><i class="mr-2 fas fa-edit"></i>coordonnées</span></router-link>
                </li>
                <li>
                  <router-link to="/history"><span><i class="mr-2 fas fa-calendar"></i>historique</span></router-link>
                </li>
              </ul>
            </div>
            <div class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span>matchs<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
                <li>
                  <router-link to="/team_matchs"><span><i class="fas fa-volleyball mr-2"></i>
                    equipe</span></router-link>
                </li>
                <li>
                  <router-link to="/club_matchs"><span><i class="fas fa-volleyball mr-2"></i>club</span>
                  </router-link>
                </li>
              </ul>
            </div>
            <router-link to="/preferences">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-gear"></i>préférences</span>
              </div>
            </router-link>
          </div>
          <div class="navbar-end">
            <a href="/rest/action.php/usermanager/logout">
              <div tabindex="0" role="button" class="btn btn-error">
                <span><i class="mr-2 fas fa-right-from-bracket"/>déconnexion</span>
              </div>
            </a>
          </div>
        </div>
      </div>`,
    data() {
        return {
            user: null,
            currentTeam: null,
            teams: null,
        };
    },
    methods: {
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                    } else {
                        this.user = response.data;
                        axios.get(`/rest/action.php/usermanager/getUserTeams?user_id=${this.user.id_user}`).then((response) => {
                            this.teams = response.data;
                            this.currentTeam = this.teams.find((item) => item.id_equipe == this.user.id_equipe)
                        })
                    }
                })
                .catch((error) => {
                    console.log(error)
                });
        },
        switchTeam(id_equipe) {
            const formData = new FormData();
            formData.append('id_equipe', id_equipe);
            axios
                .post(`/rest/action.php/usermanager/switchCurrentUserTeam`, formData)
                .then(() => {
                    window.location.reload();
                })
                .catch((error) => {
                    console.error('Erreur lors du changement d\'équipe:', error);
                    alert('Erreur lors du changement d\'équipe');
                });
        },
    },
    created() {
        this.fetchUserDetails();
    },
};