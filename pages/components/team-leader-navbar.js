export default {
    template: `
<div class="flex">
    <div class="flex flex-wrap justify-items-start z-[50]">
        <a href="/">
            <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-arrow-left"></i>retour</span>
            </div>
        </a>            
            <a href="/pages/my_page.html">
        <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-home"></i>ma page</span>
        </div>
            </a>          
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost">
              <span>gestion<i class="ml-1 fas fa-chevron-down"/></span>
          </div>
          <ul
            tabindex="0"
            class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
              <li><a href="/pages/my_players.html"><span><i class="mr-2 fas fa-user"></i>effectif</span></a></li>
              <li><a href="/pages/my_timeslots.html"><span><i class="mr-2 fas fa-clock"></i>créneaux</span></a></li>
          </ul>
        </div>
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost">
              <span>infos<i class="ml-1 fas fa-chevron-down"/></span>
          </div>
          <ul
            tabindex="0"
            class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
              <li><a href="/rest/action.php/team/download_calendar"><span><i class="mr-2 fas fa-download"></i>calendrier</span></a></li>
              <li><a href="/pages/my_team.html"><span><i class="mr-2 fas fa-edit"></i>coordonnées</span></a></li>
              <li><a href="/pages/my_history.html"><span><i class="mr-2 fas fa-calendar"></i>historique</span></a></li>
          </ul>
        </div>
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost">
              <span>matchs<i class="ml-1 fas fa-chevron-down"/></span>
          </div>
          <ul
            tabindex="0"
            class="menu menu-sm dropdown-content bg-base-100 rounded-box z-50 w-52 p-2 shadow">
                <li><a href="/pages/my_team_matchs.html"><span><i class="fas fa-volleyball mr-2"></i></i>equipe</span></a></li>
                <li><a href="/pages/my_club_matchs.html"><span><i class="fas fa-volleyball mr-2"></i></i>club</span></a></li>
          </ul>
        </div>
            <a href="/pages/my_preferences.html">
        <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-gear"></i>préférences</span>
        </div>
            </a>          
    </div>
    <div class="flex justify-items-end">
        <a href="/rest/action.php/usermanager/logout">
            <div tabindex="0" role="button" class="btn btn-error">
                <span><i class="mr-2 fas fa-right-from-bracket"/>déconnexion</span>
            </div>
        </a>          
    </div>
</div>
    `,
};