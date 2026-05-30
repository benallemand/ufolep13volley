/**
 * Barre de navigation d'une page de gestion de match.
 * Remplace l'include PHP menu.php — les liens pointent désormais vers les
 * pages HTML statiques (match.html / team_sheets.html / survey.html).
 */
export default {
    props: {
        idMatch: {type: [String, Number], required: true},
    },
    template: `
      <ul class="menu menu-horizontal bg-base-200 rounded-box flex items-center justify-between">
        <li class="border-solid rounded-full border-4 border-indigo-500">
          <a href="javascript:history.back()">
            <span>retour</span><i class="fa-solid fa-arrow-left"></i>
          </a>
        </li>
        <li class="border-solid rounded-full border-4 border-indigo-500">
          <a href="/">
            <span>accueil</span><i class="fa-solid fa-house"></i>
          </a>
        </li>
        <li class="border-solid rounded-full border-4 border-indigo-500">
          <a :href="'/match.html?id_match=' + idMatch">
            <span>match</span><i class="fa-solid fa-volleyball"></i>
          </a>
        </li>
        <li class="border-solid rounded-full border-4 border-indigo-500">
          <a :href="'/team_sheets.html?id_match=' + idMatch">
            <span>equipes</span><i class="fa-solid fa-user"></i>
          </a>
        </li>
        <li class="border-solid rounded-full border-4 border-indigo-500">
          <a :href="'/survey.html?id_match=' + idMatch">
            <span>fair-play</span><i class="fa-solid fa-square-poll-vertical"></i>
          </a>
        </li>
      </ul>
    `,
};
