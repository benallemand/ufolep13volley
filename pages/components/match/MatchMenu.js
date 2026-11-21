/**
 * Barre de navigation de la page de match. Depuis la page unique (#490), les
 * présents, le score et le sondage sont sur la même page : plus que retour et
 * accueil.
 */
export default {
    props: {
        idMatch: {type: [String, Number], default: null},
    },
    template: `
      <nav class="flex items-center justify-between gap-2">
        <a href="javascript:history.back()" class="btn btn-sm btn-ghost">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span>retour</span>
        </a>
        <a :href="$url('/')" class="btn btn-sm btn-ghost">
          <i class="fa-solid fa-house" aria-hidden="true"></i><span>accueil</span>
        </a>
      </nav>
    `,
};
