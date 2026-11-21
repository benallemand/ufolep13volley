/**
 * Étape « Le match » de la page de match (#490) : le live scoring, et le
 * forfait d'une équipe absente (score 25-0 × 3, sans fiche équipes, #344).
 * Toujours ouverte : un forfait se déclare sans présents ni signature.
 */
export default {
    props: {
        matchData: {type: Object, required: true},
        canScoreLive: {type: Boolean, default: false},
        canDeclareForfeit: {type: Boolean, default: false},
    },
    emits: ['forfeit'],
    data() {
        return {forfeitSide: null};
    },
    computed: {
        liveUrl() {
            return this.$url('/live.html?id_match=' + encodeURIComponent(this.matchData.code_match || '') + '&mode=scorer');
        },
    },
    methods: {
        confirmForfeit() {
            const side = this.forfeitSide;
            this.forfeitSide = null;
            this.$emit('forfeit', side);
        },
    },
    template: `
      <div data-testid="match-day-step">
        <template v-if="canScoreLive">
          <p class="text-sm mb-2">Pendant le match, suivez le score en direct ; le score final s'enregistre ensuite ici.</p>
          <a :href="liveUrl" target="_blank" rel="noopener" class="btn btn-outline w-full sm:w-auto" data-testid="live-scoring">
            <i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i>Ouvrir le live scoring
          </a>
        </template>

        <div v-if="canDeclareForfeit" class="mt-4 rounded-box border border-warning p-3" data-testid="forfeit">
          <h3 class="font-semibold mb-1">Une équipe ne s'est pas présentée ?</h3>
          <p class="text-sm mb-2">Le score 25-0 sur trois sets est enregistré pour l'équipe présente, sans fiche équipes à signer.</p>
          <div v-if="forfeitSide" role="alertdialog"
               class="rounded-box border-2 border-warning bg-warning/10 p-3 flex flex-col gap-3">
            <span class="text-sm">Confirmer le forfait de <strong>{{ matchData['equipe_' + forfeitSide] }}</strong> ?</span>
            <div class="flex flex-col sm:flex-row gap-2">
              <button type="button" class="btn btn-warning" @click="confirmForfeit()">Confirmer le forfait</button>
              <button type="button" class="btn btn-ghost" @click="forfeitSide = null">Annuler</button>
            </div>
          </div>
          <div v-else class="flex flex-col sm:flex-row gap-2">
            <button v-for="side in ['dom', 'ext']" :key="side" type="button" class="btn btn-warning btn-outline"
                    @click="forfeitSide = side">
              <i class="fas fa-flag" aria-hidden="true"></i>{{ matchData['equipe_' + side] }} déclare forfait
            </button>
          </div>
        </div>
      </div>
    `,
};
