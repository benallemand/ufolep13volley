export default {
    template: `
      <div class="card bg-base-100 shadow-xl mb-4" v-if="isLive">
        <div class="card-body p-4">
          <h3 class="text-center font-bold mb-2">Contrôles Scoreur</h3>

          <!-- Save Status Indicator -->
          <div class="flex items-center justify-center gap-2 mb-4">
            <span v-if="saveStatus === 'saved'" class="badge badge-success gap-1">
              <i class="fas fa-check-circle"></i> Enregistré
            </span>
            <span v-else-if="saveStatus === 'saving'" class="badge badge-info gap-1">
              <i class="fas fa-sync-alt animate-spin"></i> Enregistrement...
            </span>
            <span v-else-if="saveStatus === 'unsaved'" class="badge badge-warning gap-1 animate-pulse">
              <i class="fas fa-exclamation-circle"></i> Non enregistré
            </span>
            <span v-else-if="saveStatus === 'error'" class="badge badge-error gap-1">
              <i class="fas fa-times-circle"></i> Échec
            </span>
            <button v-if="saveStatus === 'unsaved' || saveStatus === 'error'"
                    @click="$emit('save-score')"
                    class="btn btn-outline btn-success btn-xs">
              <i class="fas fa-save mr-1"></i> Enregistrer
            </button>
            <button v-if="saveStatus === 'error'"
                    @click="$emit('save-score')"
                    class="btn btn-outline btn-warning btn-xs">
              <i class="fas fa-redo mr-1"></i> Réessayer
            </button>
            <span v-if="!isOnline" class="badge badge-ghost gap-1">
              <i class="fas fa-wifi-slash"></i> Hors ligne
            </span>
          </div>

          <div class="text-center mb-4">
            <button @click="$emit('swap-sides')" class="btn btn-outline btn-sm">
              <i class="fas fa-exchange-alt mr-1"></i> Inverser les équipes
            </button>
          </div>

          <!-- Rotation / lineups (competitions a 6) -->
          <div v-if="isRotationModeEnabled" class="mb-4">
            <div class="divider">Positions joueurs (optionnel)</div>
            <p class="text-xs text-center text-gray-500 mb-3">Rotation automatique sur reprise du service (side-out)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="card bg-base-200">
                <div class="card-body p-3">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold">{{ leftTeamName }}</span>
                    <span class="badge badge-outline badge-sm">Gauche</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2 text-xs text-center mb-1">
                    <span class="font-semibold">4</span>
                    <span class="font-semibold">3</span>
                    <span class="font-semibold">2</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2 mb-2">
                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(4, $event)">
                      <option value="" :selected="!leftLineup[4]">P4</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p4-' + player.id" :value="player.full_name" :selected="leftLineup[4] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(4)" placeholder="P4">

                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(3, $event)">
                      <option value="" :selected="!leftLineup[3]">P3</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p3-' + player.id" :value="player.full_name" :selected="leftLineup[3] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(3)" placeholder="P3">

                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(2, $event)">
                      <option value="" :selected="!leftLineup[2]">P2</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p2-' + player.id" :value="player.full_name" :selected="leftLineup[2] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(2)" placeholder="P2">
                  </div>
                  <div class="grid grid-cols-3 gap-2 text-xs text-center mb-1">
                    <span class="font-semibold">5</span>
                    <span class="font-semibold">6</span>
                    <span class="font-semibold">1</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2">
                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(5, $event)">
                      <option value="" :selected="!leftLineup[5]">P5</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p5-' + player.id" :value="player.full_name" :selected="leftLineup[5] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(5)" placeholder="P5">

                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(6, $event)">
                      <option value="" :selected="!leftLineup[6]">P6</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p6-' + player.id" :value="player.full_name" :selected="leftLineup[6] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(6)" placeholder="P6">

                    <select v-if="leftTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onLeftChange(1, $event)">
                      <option value="" :selected="!leftLineup[1]">P1</option>
                      <option v-for="player in leftTeamPlayers" :key="'left-p1-' + player.id" :value="player.full_name" :selected="leftLineup[1] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onLeftChange(1)" placeholder="P1">
                  </div>
                </div>
              </div>

              <div class="card bg-base-200">
                <div class="card-body p-3">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold">{{ rightTeamName }}</span>
                    <span class="badge badge-outline badge-sm">Droite</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2 text-xs text-center mb-1">
                    <span class="font-semibold">4</span>
                    <span class="font-semibold">3</span>
                    <span class="font-semibold">2</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2 mb-2">
                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(4, $event)">
                      <option value="" :selected="!rightLineup[4]">P4</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p4-' + player.id" :value="player.full_name" :selected="rightLineup[4] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(4)" placeholder="P4">

                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(3, $event)">
                      <option value="" :selected="!rightLineup[3]">P3</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p3-' + player.id" :value="player.full_name" :selected="rightLineup[3] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(3)" placeholder="P3">

                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(2, $event)">
                      <option value="" :selected="!rightLineup[2]">P2</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p2-' + player.id" :value="player.full_name" :selected="rightLineup[2] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(2)" placeholder="P2">
                  </div>
                  <div class="grid grid-cols-3 gap-2 text-xs text-center mb-1">
                    <span class="font-semibold">5</span>
                    <span class="font-semibold">6</span>
                    <span class="font-semibold">1</span>
                  </div>
                  <div class="grid grid-cols-3 gap-2">
                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(5, $event)">
                      <option value="" :selected="!rightLineup[5]">P5</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p5-' + player.id" :value="player.full_name" :selected="rightLineup[5] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(5)" placeholder="P5">

                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(6, $event)">
                      <option value="" :selected="!rightLineup[6]">P6</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p6-' + player.id" :value="player.full_name" :selected="rightLineup[6] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(6)" placeholder="P6">

                    <select v-if="rightTeamPlayers.length" class="select select-bordered select-xs text-center" @change="onRightChange(1, $event)">
                      <option value="" :selected="!rightLineup[1]">P1</option>
                      <option v-for="player in rightTeamPlayers" :key="'right-p1-' + player.id" :value="player.full_name" :selected="rightLineup[1] === player.full_name">{{ player.full_name }}</option>
                    </select>
                    <input v-else class="input input-bordered input-xs text-center" type="text" maxlength="20" @input="onRightChange(1)" placeholder="P1">
                  </div>
                </div>
              </div>
            </div>
            <div class="text-center mt-3">
              <button class="btn btn-outline btn-xs" @click="$emit('reset-positions')">
                <i class="fas fa-rotate-left mr-1"></i> Réinitialiser les positions
              </button>
            </div>
          </div>
          
          <!-- Timeouts -->
          <div class="divider">Temps morts</div>
          <div class="grid grid-cols-2 gap-4 mb-2">
            <!-- Left team timeouts -->
            <div class="flex flex-col items-center gap-2">
              <span class="text-xs font-semibold">{{ leftTeamName }}</span>
              <div class="flex gap-3">
                <div class="flex flex-col items-center" v-for="n in 2" :key="'left-tm-' + n">
                  <label class="label cursor-pointer gap-1 p-0">
                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm"
                           :checked="leftTimeouts['tm' + n].used"
                           :disabled="leftTimeouts['tm' + n].used"
                           @change="$emit('start-timeout', leftTeamKey, n)">
                    <span class="label-text text-xs">TM{{ n }}</span>
                  </label>
                  <span v-if="leftTimeouts['tm' + n].countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                    {{ leftTimeouts['tm' + n].countdown }}s
                  </span>
                  <span v-else-if="leftTimeouts['tm' + n].used" class="text-xs text-gray-400">Terminé</span>
                </div>
              </div>
            </div>
            <!-- Right team timeouts -->
            <div class="flex flex-col items-center gap-2">
              <span class="text-xs font-semibold">{{ rightTeamName }}</span>
              <div class="flex gap-3">
                <div class="flex flex-col items-center" v-for="n in 2" :key="'right-tm-' + n">
                  <label class="label cursor-pointer gap-1 p-0">
                    <input type="checkbox" class="checkbox checkbox-secondary checkbox-sm"
                           :checked="rightTimeouts['tm' + n].used"
                           :disabled="rightTimeouts['tm' + n].used"
                           @change="$emit('start-timeout', rightTeamKey, n)">
                    <span class="label-text text-xs">TM{{ n }}</span>
                  </label>
                  <span v-if="rightTimeouts['tm' + n].countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                    {{ rightTimeouts['tm' + n].countdown }}s
                  </span>
                  <span v-else-if="rightTimeouts['tm' + n].used" class="text-xs text-gray-400">Terminé</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Set Controls -->
          <div class="divider">Gestion des Sets</div>
          <div class="grid grid-cols-2 gap-2">
            <button @click="$emit('next-set-left')" class="btn btn-outline btn-primary">
              <i class="fas fa-trophy mr-1"></i> Set gagné gauche
            </button>
            <button @click="$emit('next-set-right')" class="btn btn-outline btn-secondary">
              <i class="fas fa-trophy mr-1"></i> Set gagné droite
            </button>
          </div>
          
          <!-- End Match -->
          <div class="mt-4 flex flex-col gap-2">
            <button @click="$emit('save-to-match')" class="btn btn-success btn-block" v-if="score && (score.sets_dom + score.sets_ext > 0)">
              <i class="fas fa-save mr-1"></i> Renseigner les scores du match
            </button>
            <button @click="$emit('end-live')" class="btn btn-error btn-block">
              <i class="fas fa-stop mr-1"></i> Terminer le Live
            </button>
          </div>
        </div>
      </div>
    `,
    props: {
        score: { type: Object, required: true },
        isLive: { type: Boolean, required: true },
        isRotationModeEnabled: { type: Boolean, default: false },
        saveStatus: { type: String, default: 'saved' },
        isOnline: { type: Boolean, default: true },
        leftTeamName: { type: String, default: '' },
        rightTeamName: { type: String, default: '' },
        leftTeamKey: { type: String, required: true },
        rightTeamKey: { type: String, required: true },
        leftLineup: { type: Object, default: () => ({}) },
        rightLineup: { type: Object, default: () => ({}) },
        leftTeamPlayers: { type: Array, default: () => [] },
        rightTeamPlayers: { type: Array, default: () => [] },
        leftTimeouts: { type: Object, required: true },
        rightTimeouts: { type: Object, required: true }
    },
    methods: {
        onLeftChange(pos, event) {
            this.$emit('update-position', this.leftTeamKey, pos, event.target.value);
        },
        onRightChange(pos, event) {
            this.$emit('update-position', this.rightTeamKey, pos, event.target.value);
        }
    }
};
