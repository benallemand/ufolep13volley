/**
 * Encart "Matchs du jour" de la page d'accueil (#230).
 *
 * Affiche les matchs programmés aujourd'hui (endpoint public
 * matchmgr/getMatchesOfTheDay), avec un lien vers le live score de chaque match.
 * Ne s'affiche QUE s'il y a au moins un match aujourd'hui (sinon rien).
 * Pensé mobile-first : cartes empilées, pleine largeur, zones tactiles larges.
 */
export default {
    template: `
      <section v-if="matches.length > 0" class="w-full max-w-2xl">
        <h2 class="text-2xl font-bold text-primary text-center mb-3">
          <i class="fas fa-volleyball mr-2"></i>Matchs du jour
        </h2>
        <div class="flex flex-col gap-3">
          <a v-for="match in matches" :key="match.id_match"
             :href="'/live.html?id_match=' + match.code_match"
             class="card bg-base-100 shadow-md border border-base-300 active:scale-[0.99] transition-transform">
            <div class="card-body p-4">
              <div class="flex items-center justify-between gap-2">
                <span class="badge badge-info badge-sm">{{ match.libelle_competition }}</span>
                <span class="text-sm font-mono opacity-70">{{ formatHeure(match.heure_reception) }}</span>
              </div>
              <div class="flex items-center justify-center gap-2 text-center font-bold text-base my-1">
                <span class="flex-1 text-right">{{ match.equipe_dom }}</span>
                <span v-if="isScoreFilled(match)" class="font-mono text-primary">{{ match.score_equipe_dom }} - {{ match.score_equipe_ext }}</span>
                <span v-else class="opacity-50">vs</span>
                <span class="flex-1 text-left">{{ match.equipe_ext }}</span>
              </div>
              <div class="flex items-center justify-between gap-2 text-xs opacity-70">
                <span><i class="fas fa-location-dot mr-1"></i>{{ match.gymnasium }}</span>
                <span class="text-primary font-semibold">
                  <i class="fas fa-circle-play mr-1"></i>Live score
                </span>
              </div>
            </div>
          </a>
        </div>
      </section>
    `,
    data() {
        return {
            matches: [],
        };
    },
    methods: {
        // Score FINAL renseigné (match terminé) — pas le live score.
        // is_match_score_filled peut arriver en chaîne ("0"/"1") via mysqli :
        // on compare en souple pour éviter que "0" (truthy en JS) ne passe.
        isScoreFilled(match) {
            return match.is_match_score_filled == 1;
        },
        formatHeure(heure) {
            // "20:30:00" -> "20:30"
            if (!heure) {
                return '';
            }
            return heure.toString().slice(0, 5);
        },
        fetch() {
            axios
                .get('/rest/action.php/matchmgr/getMatchesOfTheDay')
                .then((response) => {
                    this.matches = Array.isArray(response.data) ? response.data : [];
                })
                .catch((error) => {
                    console.error('Erreur lors du chargement des matchs du jour:', error);
                });
        },
    },
    created() {
        this.fetch();
    },
};
