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
          
          <!-- Score Buttons -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Left buttons -->
            <div class="flex flex-col gap-2">
              <button @click="$emit('increment-left')" 
                      class="btn btn-primary btn-lg h-24 text-3xl">
                +1
              </button>
              <button @click="$emit('decrement-left')" 
                      class="btn btn-outline btn-sm">
                -1
              </button>
            </div>
            
            <!-- Right buttons -->
            <div class="flex flex-col gap-2">
              <button @click="$emit('increment-right')" 
                      class="btn btn-secondary btn-lg h-24 text-3xl">
                +1
              </button>
              <button @click="$emit('decrement-right')" 
                      class="btn btn-outline btn-sm">
                -1
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
            <button @click="$emit('save-to-match')" class="btn btn-success btn-block" v-if="score && (score.sets_dom >= 3 || score.sets_ext >= 3)">
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
        saveStatus: { type: String, default: 'saved' },
        isOnline: { type: Boolean, default: true },
        leftTeamName: { type: String, default: '' },
        rightTeamName: { type: String, default: '' },
        leftTeamKey: { type: String, required: true },
        rightTeamKey: { type: String, required: true },
        leftTimeouts: { type: Object, required: true },
        rightTimeouts: { type: Object, required: true }
    }
};
