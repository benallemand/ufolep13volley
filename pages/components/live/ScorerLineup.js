import { defineAsyncComponent } from 'vue';

/**
 * Composition d'un set (issue #332).
 *
 * Remplace les 12 menus déroulants qui occupaient en permanence plus d'un écran
 * de téléphone. Trois chemins, du plus fréquent au plus rare :
 *
 *  1. **Reprendre la composition du set précédent** — un toucher. C'est le cas
 *     courant : une équipe change rarement son six de départ en cours de match.
 *  2. **Toucher un poste, puis un joueur.** Le poste actif avance tout seul, on
 *     enchaîne six touchers sans revenir choisir une case entre chaque.
 *  3. Rien du tout : seul le **poste 1** est nécessaire pour démarrer, c'est lui
 *     qui pilote la rotation. Les cinq autres peuvent se compléter plus tard.
 *
 * Les cibles font 56 px — les `select select-xs` d'avant en faisaient 24.
 */

// Ordre de remplissage : on commence par le serveur, puis on suit la rotation.
const FILL_ORDER = [1, 2, 3, 4, 5, 6];

export default {
    components: {
        'scorer-court': defineAsyncComponent(() => import('./ScorerCourt.js')),
    },
    template: `
      <dialog class="modal modal-open modal-bottom">
        <div class="modal-box max-h-[92vh] flex flex-col gap-3">
          <div class="flex items-center justify-between">
            <h3 class="font-bold">Composition · set {{ setNumber }}</h3>
            <span class="text-sm font-bold">{{ placedCount }} / 6</span>
          </div>

          <div role="tablist" class="tabs tabs-boxed">
            <button v-for="side in sides"
                    :key="'tab-' + side.key"
                    type="button"
                    role="tab"
                    class="tab"
                    :class="side.key === currentSide ? 'tab-active' : ''"
                    @click="switchSide(side.key)">
              <span class="truncate max-w-[8rem]">{{ side.name }}</span>
            </button>
          </div>

          <div class="flex gap-2">
            <button type="button"
                    class="btn btn-primary flex-1"
                    :disabled="!hasPrevious"
                    @click="$emit('repeat-previous', currentSide)">
              Reprendre le set {{ setNumber - 1 }}
            </button>
            <button type="button"
                    class="btn btn-outline btn-square"
                    title="Vider la composition"
                    @click="clearSide">
              <i class="fas fa-rotate-left"></i>
            </button>
          </div>
          <p v-if="!hasPrevious" class="text-xs text-center opacity-60 -mt-1">
            Pas de composition précédente pour ce set.
          </p>

          <scorer-court :lineup="side.lineup"
                        :players="side.players"
                        :active-position="activePosition"
                        selectable
                        large
                        @select-position="activePosition = $event"></scorer-court>

          <div class="flex-1 overflow-y-auto">
            <p class="text-xs font-bold mb-2">
              {{ activePosition ? 'Qui occupe le poste ' + activePosition + ' ?' : 'Effectif' }}
            </p>
            <div v-if="availablePlayers.length" class="grid grid-cols-2 gap-2">
              <button v-for="player in availablePlayers"
                      :key="'roster-' + player.id"
                      type="button"
                      class="btn btn-outline h-14 justify-start gap-2 pl-1 pr-3 normal-case"
                      :disabled="!activePosition"
                      @click="place(player)">
                <div class="avatar">
                  <div class="w-9 rounded-full">
                    <img :src="'/' + player.path_photo_low" :alt="player.nom_court">
                  </div>
                </div>
                <span class="truncate font-semibold">{{ player.nom_court }}</span>
              </button>
            </div>
            <p v-else class="text-sm opacity-60 text-center py-4">
              Tous les joueurs de l'effectif sont placés.
            </p>
          </div>

          <div class="flex flex-col gap-1">
            <button type="button" class="btn btn-success btn-block" @click="$emit('close')">
              Revenir au score
            </button>
            <p class="text-xs text-center opacity-60">
              Seul le poste 1 (le serveur) est nécessaire pour démarrer.
            </p>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button @click="$emit('close')">close</button>
        </form>
      </dialog>
    `,
    props: {
        setNumber: { type: Number, default: 1 },
        /** [{ key: 'dom'|'ext', name, lineup, players, hasPrevious }] */
        sides: { type: Array, required: true },
        initialSide: { type: String, default: null },
    },
    emits: ['place', 'clear', 'repeat-previous', 'close'],
    data() {
        return {
            currentSide: this.initialSide || (this.sides[0] && this.sides[0].key) || 'dom',
            activePosition: 1,
        };
    },
    computed: {
        side() {
            return this.sides.find((s) => s.key === this.currentSide) || this.sides[0];
        },
        hasPrevious() {
            return Boolean(this.side && this.side.hasPrevious);
        },
        placedCount() {
            const lineup = (this.side && this.side.lineup) || {};
            return FILL_ORDER.filter((position) => lineup[position]).length;
        },
        availablePlayers() {
            const lineup = (this.side && this.side.lineup) || {};
            const taken = FILL_ORDER.map((position) => String(lineup[position] || ''));
            return ((this.side && this.side.players) || [])
                .filter((player) => taken.indexOf(String(player.id)) === -1);
        },
    },
    methods: {
        switchSide(key) {
            this.currentSide = key;
            this.activePosition = this.firstFreePosition();
        },
        /**
         * Le poste actif avance tout seul après chaque placement : c'est ce qui
         * permet d'enchaîner six touchers sans revenir choisir une case.
         */
        place(player) {
            if (!this.activePosition) {
                return;
            }
            this.$emit('place', this.currentSide, this.activePosition, player.id);
            this.$nextTick(() => { this.activePosition = this.firstFreePosition(); });
        },
        clearSide() {
            this.$emit('clear', this.currentSide);
            this.$nextTick(() => { this.activePosition = 1; });
        },
        firstFreePosition() {
            const lineup = (this.side && this.side.lineup) || {};
            const free = FILL_ORDER.find((position) => !lineup[position]);
            return free === undefined ? null : free;
        },
    },
};
