import { defineAsyncComponent } from 'vue';

/**
 * Tout ce qui n'intervient PAS entre deux points (issue #332).
 *
 * L'écran de marque (`ScorerBoard`) garde les deux zones tactiles pour lui ;
 * ici on trouve la ligne de service, la barre au pouce, et trois feuilles qui
 * s'ouvrent par-dessus. Le principe est le même partout : rien n'occupe l'écran
 * en permanence si l'arbitre n'en a pas besoin à chaque échange.
 *
 * Les positions en sont l'exemple : elles tenaient plus d'un écran de téléphone
 * en 12 menus déroulants, il n'en reste qu'une ligne — qui sert, et rien
 * d'autre — et le terrain complet s'ouvre à la demande.
 */
export default {
    components: {
        'scorer-court': defineAsyncComponent(() => import('./ScorerCourt.js')),
    },
    template: `
      <div class="bg-base-100 border-t border-base-300">
        <!--
          Ligne de service : la seule info de position utile pendant l'echange.
          Elle vaut pour TOUTES les competitions ; seul l'acces au terrain est
          reserve a celles qui tournent a six.
        -->
        <button type="button"
                class="w-full h-12 px-4 flex items-center justify-between border-b border-base-300"
                :disabled="!isRotationModeEnabled"
                @click="sheet = 'court'">
          <span class="flex items-center gap-2 min-w-0">
            <i class="fas fa-volleyball opacity-60"></i>
            <span class="text-sm">Au service</span>
            <span v-if="servingPlayer" class="avatar">
              <span class="w-5 rounded-full block">
                <img :src="'/' + servingPlayer.path_photo_low" :alt="servingPlayer.nom_court">
              </span>
            </span>
            <span class="text-sm font-bold truncate">{{ servingLabel }}</span>
          </span>
          <span v-if="isRotationModeEnabled" class="flex items-center gap-1 opacity-60 shrink-0">
            <span class="text-xs">Positions</span>
            <i class="fas fa-chevron-right text-xs"></i>
          </span>
        </button>

        <!-- Temps mort en cours : il doit se voir sans ouvrir quoi que ce soit -->
        <div v-if="runningTimeout" class="h-10 flex items-center justify-center gap-2 bg-warning/20 border-b border-base-300">
          <i class="fas fa-clock text-warning"></i>
          <span class="text-sm font-semibold">Temps mort {{ runningTimeout.teamName }}</span>
          <span class="font-bold tabular-nums text-warning">{{ runningTimeout.countdown }} s</span>
        </div>

        <!-- Barre du pouce -->
        <div class="grid grid-cols-3 gap-2 px-3 pt-3 pb-4">
          <button type="button" class="btn btn-outline h-12" :disabled="!canUndo" @click="$emit('undo')">
            <i class="fas fa-rotate-left"></i> Annuler
          </button>
          <button type="button" class="btn btn-outline h-12" @click="sheet = 'timeout'">
            <i class="fas fa-clock"></i> Temps mort
          </button>
          <button type="button" class="btn btn-outline h-12" @click="sheet = 'more'">
            <i class="fas fa-ellipsis"></i> Plus
          </button>
        </div>

        <!-- Feuille « positions » -->
        <dialog v-if="sheet === 'court'" class="modal modal-open modal-bottom">
          <div class="modal-box flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
              <h3 class="font-bold">Positions sur le terrain</h3>
              <span class="text-xs opacity-60">Set {{ score.set_en_cours }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <scorer-court :team-name="leftTeamName"
                            :lineup="leftLineup"
                            :players="leftTeamPlayers"
                            accent="bg-primary"></scorer-court>
              <scorer-court :team-name="rightTeamName"
                            :lineup="rightLineup"
                            :players="rightTeamPlayers"
                            accent="bg-secondary"></scorer-court>
            </div>
            <p class="text-xs opacity-60">
              La rotation suit les reprises de service. Annuler un point qui avait
              fait changer le service annule aussi la rotation.
            </p>
            <div class="flex flex-col gap-2">
              <button type="button" class="btn btn-outline btn-block" @click="openLineup">
                Modifier la composition…
              </button>
              <button type="button" class="btn btn-primary btn-block" @click="sheet = null">Fermer</button>
            </div>
          </div>
          <form method="dialog" class="modal-backdrop"><button @click="sheet = null">close</button></form>
        </dialog>

        <!-- Feuille « temps morts » -->
        <dialog v-if="sheet === 'timeout'" class="modal modal-open modal-bottom">
          <div class="modal-box flex flex-col gap-3">
            <h3 class="font-bold">Temps morts</h3>
            <p class="text-xs opacity-60 -mt-2">Deux par équipe et par set.</p>
            <div v-for="team in timeoutTeams" :key="'to-' + team.key" class="flex flex-col gap-2">
              <span class="text-sm font-semibold truncate">{{ team.name }}</span>
              <div class="grid grid-cols-2 gap-2">
                <button v-for="n in 2"
                        :key="team.key + '-tm' + n"
                        type="button"
                        class="btn h-12"
                        :class="team.timeouts['tm' + n].used ? 'btn-disabled btn-ghost' : 'btn-outline'"
                        :disabled="team.timeouts['tm' + n].used"
                        @click="startTimeout(team.key, n)">
                  <span v-if="team.timeouts['tm' + n].used">TM{{ n }} pris</span>
                  <span v-else>TM{{ n }}</span>
                </button>
              </div>
            </div>
            <button type="button" class="btn btn-block" @click="sheet = null">Fermer</button>
          </div>
          <form method="dialog" class="modal-backdrop"><button @click="sheet = null">close</button></form>
        </dialog>

        <!-- Feuille « plus » : ce qui se fait une fois par set, ou une fois par match -->
        <dialog v-if="sheet === 'more'" class="modal modal-open modal-bottom">
          <div class="modal-box flex flex-col gap-2">
            <!--
              Date et gymnase : information secondaire, utile seulement pour
              verifier qu'on est sur le bon match. Elle occupait l'ecran en
              permanence, et par-dessus la composition sur un petit telephone.
            -->
            <div v-if="match" class="text-xs opacity-60 flex flex-wrap gap-x-3 gap-y-1 pb-2 border-b border-base-300">
              <span><i class="fas fa-calendar mr-1"></i>{{ match.date_reception || 'date non définie' }}</span>
              <span v-if="match.heure_reception"><i class="fas fa-clock mr-1"></i>{{ match.heure_reception }}</span>
              <span class="w-full"><i class="fas fa-map-marker-alt mr-1"></i>{{ match.gymnasium || 'gymnase non défini' }}</span>
            </div>
            <div class="flex items-center justify-between pb-1">
              <span class="text-sm">Enregistrement</span>
              <span v-if="saveStatus === 'saved'" class="badge badge-success gap-1">
                <i class="fas fa-check-circle"></i> À jour
              </span>
              <span v-else-if="saveStatus === 'saving'" class="badge badge-info gap-1">
                <i class="fas fa-sync-alt animate-spin"></i> En cours
              </span>
              <span v-else-if="saveStatus === 'unsaved'" class="badge badge-warning gap-1">
                <i class="fas fa-exclamation-circle"></i> Non enregistré
              </span>
              <span v-else-if="saveStatus === 'error'" class="badge badge-error gap-1">
                <i class="fas fa-times-circle"></i> Échec
              </span>
            </div>
            <div v-if="!isOnline" class="alert alert-warning py-2">
              <i class="fas fa-wifi"></i>
              <span class="text-sm">Hors ligne — le score est gardé sur l'appareil et repartira au retour du réseau.</span>
            </div>
            <button v-if="saveStatus === 'unsaved' || saveStatus === 'error'"
                    type="button" class="btn btn-outline btn-success btn-block"
                    @click="$emit('save-score')">
              <i class="fas fa-save"></i> Enregistrer maintenant
            </button>

            <button v-if="isRotationModeEnabled" type="button" class="btn btn-outline btn-block h-14" @click="openLineup">
              Composition du set…
            </button>
            <button type="button" class="btn btn-outline btn-block h-14" @click="swapSides">
              <i class="fas fa-exchange-alt"></i> Inverser les camps
            </button>

            <div class="divider my-1 text-xs">Fin de set</div>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" class="btn btn-warning h-14"
                      :aria-label="'Set gagné par ' + leftTeamName"
                      @click="nextSet('left')">
                <span class="truncate">{{ leftTeamName }}</span>
              </button>
              <button type="button" class="btn btn-warning h-14"
                      :aria-label="'Set gagné par ' + rightTeamName"
                      @click="nextSet('right')">
                <span class="truncate">{{ rightTeamName }}</span>
              </button>
            </div>

            <div class="divider my-1 text-xs">Fin de match</div>
            <button v-if="canSaveToMatch" type="button" class="btn btn-success btn-block h-14"
                    @click="emitAndClose('save-to-match')">
              <i class="fas fa-save"></i> Renseigner les scores du match
            </button>
            <button type="button" class="btn btn-outline btn-error btn-block h-14"
                    @click="emitAndClose('end-live')">
              <i class="fas fa-stop"></i> Terminer le live
            </button>

            <button type="button" class="btn btn-ghost btn-block" @click="sheet = null">Fermer</button>
          </div>
          <form method="dialog" class="modal-backdrop"><button @click="sheet = null">close</button></form>
        </dialog>
      </div>
    `,
    props: {
        score: { type: Object, required: true },
        match: { type: Object, default: null },
        isLive: { type: Boolean, required: true },
        isRotationModeEnabled: { type: Boolean, default: false },
        saveStatus: { type: String, default: 'saved' },
        isOnline: { type: Boolean, default: true },
        canUndo: { type: Boolean, default: false },
        leftTeamName: { type: String, default: '' },
        rightTeamName: { type: String, default: '' },
        leftTeamKey: { type: String, required: true },
        rightTeamKey: { type: String, required: true },
        leftLineup: { type: Object, default: () => ({}) },
        rightLineup: { type: Object, default: () => ({}) },
        leftTeamPlayers: { type: Array, default: () => [] },
        rightTeamPlayers: { type: Array, default: () => [] },
        leftTimeouts: { type: Object, required: true },
        rightTimeouts: { type: Object, required: true },
        servingTeam: { type: String, default: null },
    },
    emits: [
        'undo', 'next-set-left', 'next-set-right', 'open-lineup', 'save-score',
        'save-to-match', 'end-live', 'start-timeout', 'swap-sides',
    ],
    data() {
        return { sheet: null };
    },
    computed: {
        timeoutTeams() {
            return [
                { key: this.leftTeamKey, name: this.leftTeamName, timeouts: this.leftTimeouts },
                { key: this.rightTeamKey, name: this.rightTeamName, timeouts: this.rightTimeouts },
            ];
        },
        runningTimeout() {
            const running = this.timeoutTeams.find((team) => (
                team.timeouts.tm1.countdown > 0 || team.timeouts.tm2.countdown > 0
            ));
            if (!running) {
                return null;
            }
            return {
                teamName: running.name,
                countdown: Math.max(running.timeouts.tm1.countdown, running.timeouts.tm2.countdown),
            };
        },
        servingPlayer() {
            if (!this.servingTeam) {
                return null;
            }
            const isLeft = this.servingTeam === this.leftTeamKey;
            const lineup = isLeft ? this.leftLineup : this.rightLineup;
            const players = isLeft ? this.leftTeamPlayers : this.rightTeamPlayers;
            const id = String(lineup[1] || '');
            return players.find((player) => String(player.id) === id) || null;
        },
        servingLabel() {
            if (!this.servingTeam) {
                return 'au premier point';
            }
            if (this.servingPlayer) {
                return this.servingPlayer.nom_court;
            }
            return this.servingTeam === this.leftTeamKey ? this.leftTeamName : this.rightTeamName;
        },
        canSaveToMatch() {
            return this.score && (Number(this.score.sets_dom) + Number(this.score.sets_ext)) > 0;
        },
    },
    methods: {
        openLineup() {
            this.sheet = null;
            this.$emit('open-lineup');
        },
        swapSides() {
            this.sheet = null;
            this.$emit('swap-sides');
        },
        startTimeout(teamKey, n) {
            this.sheet = null;
            this.$emit('start-timeout', teamKey, n);
        },
        nextSet(side) {
            this.sheet = null;
            this.$emit(side === 'left' ? 'next-set-left' : 'next-set-right');
        },
        emitAndClose(event) {
            this.sheet = null;
            this.$emit(event);
        },
    },
};
