import { onError, onSuccess } from '../../../toaster.js';

/**
 * Fusion de deux fiches d'un même joueur (issue #409, lot 2).
 *
 * Les deux fiches côte à côte ; on choisit celle à garder. L'autre est
 * supprimée après report de ses équipes, feuilles de match, photo, compte et
 * champs vides (`Players::mergePlayers`). Par défaut, on propose de garder la
 * fiche la plus « vivante » : celle qui a des équipes, puis une homologation.
 */
export default {
    props: {
        /** Les deux lignes de la grille Joueurs à fusionner. */
        players: { type: Array, required: true },
    },
    emits: ['close', 'merged'],
    template: `
      <dialog class="modal modal-open" data-testid="player-merge">
        <div class="modal-box max-w-3xl">
          <h3 class="font-bold text-lg mb-1">Fusionner deux fiches</h3>
          <p class="text-sm text-base-content/70 mb-4">
            Choisissez la fiche à <strong>garder</strong>. L'autre sera supprimée, après report de
            ses équipes, feuilles de match, photo et compte, et des champs vides de la fiche gardée.
          </p>
          <div class="grid sm:grid-cols-2 gap-3">
            <label v-for="p in players" :key="p.id"
                   class="card border cursor-pointer transition"
                   :class="String(keepId) === String(p.id) ? 'border-primary ring-2 ring-primary' : 'border-base-300'"
                   :data-testid="'player-merge-' + p.id">
              <div class="card-body p-4 gap-1 text-sm">
                <div class="flex items-center gap-2">
                  <input type="radio" class="radio radio-primary radio-sm" name="keep"
                         :value="p.id" v-model="keepId"/>
                  <span class="font-semibold">{{ p.nom }} {{ p.prenom }}</span>
                  <span v-if="String(keepId) === String(p.id)" class="badge badge-primary badge-sm ml-auto">gardée</span>
                  <span v-else class="badge badge-ghost badge-sm ml-auto">supprimée</span>
                </div>
                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 mt-2">
                  <dt class="text-base-content/60">Licence</dt><dd>{{ p.num_licence || '—' }}</dd>
                  <dt class="text-base-content/60">Homologation</dt><dd>{{ p.date_homologation || '—' }}</dd>
                  <dt class="text-base-content/60">Club</dt><dd>{{ p.club || '—' }}</dd>
                  <dt class="text-base-content/60">Équipes</dt><dd>{{ p.teams_list || '—' }}</dd>
                  <dt class="text-base-content/60">Email</dt><dd class="break-all">{{ p.email || '—' }}</dd>
                  <dt class="text-base-content/60">Fiche n°</dt><dd>{{ p.id }}</dd>
                </dl>
              </div>
            </label>
          </div>
          <div class="modal-action">
            <button class="btn btn-ghost btn-sm" :disabled="isBusy" @click="$emit('close')">Annuler</button>
            <button class="btn btn-primary btn-sm" data-testid="player-merge-confirm"
                    :disabled="isBusy || !keepId" @click="merge">
              <span v-if="isBusy" class="loading loading-spinner loading-xs"></span>
              <i v-else class="fas fa-code-merge"></i> Fusionner
            </button>
          </div>
        </div>
        <div class="modal-backdrop" @click="$emit('close')"></div>
      </dialog>
    `,
    data() {
        return { keepId: this.defaultKeep(), isBusy: false, isLoading: false };
    },
    methods: {
        defaultKeep() {
            const score = (p) => (String(p.teams_list || '').trim() ? 2 : 0) + (p.date_homologation ? 1 : 0);
            const [a, b] = this.players;
            return score(b) > score(a) ? b.id : a.id;
        },
        merge() {
            const remove = this.players.find((p) => String(p.id) !== String(this.keepId));
            const formData = new FormData();
            formData.append('id_keep', this.keepId);
            formData.append('id_remove', remove.id);
            this.isBusy = true;
            axios.post('/rest/action.php/player/mergePlayers', formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.$emit('merged');
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
