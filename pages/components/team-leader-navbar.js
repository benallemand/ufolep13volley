export default {
    template: `
      <div class="navbar bg-base-200 border border-2 border-base-300 p-4">
        <div class="navbar-start">
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
              <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h8m-8 6h16"/>
              </svg>
            </div>
            <ul
                tabindex="0"
                class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[50] mt-3 p-2 shadow">
              <li><a href="/"><span><i class="mr-2 fas fa-arrow-left"></i>retour</span></a></li>
              <li><a href="/pages/my_page.html"><span><i class="mr-2 fas fa-home"></i>ma page</span></a></li>
              <li>
                <details>
                  <summary>gestion</summary>
                  <ul class="p-2">
                    <li><a href="/pages/my_players.html"><span><i class="mr-2 fas fa-user"></i>effectif</span></a></li>
                    <li><a href="/pages/my_timeslots.html"><span><i class="mr-2 fas fa-clock"></i>créneaux</span></a></li>
                  </ul>
                </details>
              </li>
              <li>
                <details>
                  <summary>infos</summary>
                  <ul class="p-2">
                    <li><a href="/rest/action.php/team/download_calendar"><span><i class="mr-2 fas fa-calendar"></i>calendrier</span></a></li>
                    <li><a href="/pages/my_team.html"><span><i class="mr-2 fas fa-edit"></i>coordonnées</span></a></li>
                    <li><a href="/pages/my_history.html"><span><i class="mr-2 fas fa-calendar"></i>historique</span></a></li>
                  </ul>
                </details>
              </li>
              <li>
                <details>
                  <summary>matchs</summary>
                  <ul class="p-2">
                    <li><a href="/pages/my_team_matchs.html"><span><i class="mr-2 fas fa-home"></i>equipe</span></a></li>
                    <li><a href="/pages/my_club_matchs.html"><span><i class="mr-2 fas fa-home"></i>club</span></a></li>
                  </ul>
                </details>
              </li>
              <li><a href="/pages/my_preferences.html"><span><i class="mr-2 fas fa-gear"></i>préférences</span></a></li>
            </ul>
          </div>
        </div>
        <div class="navbar-center hidden lg:flex z-[50]">
          <ul class="menu menu-horizontal px-1">
            <li><a href="/">
              <span><i class="mr-2 fas fa-arrow-left"></i>retour</span>
            </a></li>
            <li><a href="/pages/my_page.html">
              <span><i class="mr-2 fas fa-home"></i>ma page</span>
            </a></li>
            <li>
              <details>
                <summary>gestion</summary>
                <ul class="p-2">
                  <li><a href="/pages/my_players.html"><span><i class="mr-2 fas fa-user"></i>effectif</span></a></li>
                  <li><a href="/pages/my_timeslots.html"><span><i class="mr-2 fas fa-clock"></i>créneaux</span></a></li>
                </ul>
              </details>
            </li>
            <li>
              <details>
                <summary>infos</summary>
                <ul class="p-2">
                  <li><a href="/rest/action.php/team/download_calendar"><span><i class="mr-2 fas fa-calendar"></i>calendrier</span></a></li>
                  <li><a href="/pages/my_team.html"><span><i class="mr-2 fas fa-edit"></i>coordonnées</span></a></li>
                  <li><a href="/pages/my_history.html"><span><i class="mr-2 fas fa-calendar"></i>historique</span></a></li>
                </ul>
              </details>
            </li>
            <li>
              <details>
                <summary>matchs</summary>
                <ul class="p-2">
                  <li><a href="/pages/my_team_matchs.html"><span><i class="mr-2 fas fa-home"></i>equipe</span></a></li>
                  <li><a href="/pages/my_club_matchs.html"><span><i class="mr-2 fas fa-home"></i>club</span></a></li>
                </ul>
              </details>
            </li>
            <li><a href="/pages/my_preferences.html"><span><i class="mr-2 fas fa-gear"></i>préférences</span></a></li>
          </ul>
        </div>
        <div class="navbar-end">
        <ul class="menu menu-horizontal px-1 items-center">
            <li>
                <a href="/rest/action.php/usermanager/logout">
                    <button type="button" class="btn btn-error">
                        <span class="glyphicon glyphicon-log-out"></span> déconnexion
                    </button>
                </a>
            </li>
            <li>
                <a target="_blank" href="https://cd.ufolep.org/bouchesdurhone/bouchesdurhone_a/cms/index_public.php?ui_id_site=1&us_action=show_note_site">
                    <img src="/images/ufolep-logo-cmjn-BOUCHES-DU.jpg" alt="Logo" class="h-12">
                </a>
            </li>
        </ul>
        </div>
      </div>
    `
};