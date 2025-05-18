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
            <router-link to="/dashboard">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-home"></i>ma page</span>
              </div>
            </router-link>
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
        return {};
    },
};