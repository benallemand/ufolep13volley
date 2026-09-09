/**
 * Fenêtre de sélection générique (issue #288).
 *
 * Plusieurs actions d'administration se résument à « choisir dans une liste,
 * puis confirmer » : associer des joueurs à un club ou à une équipe, nommer un
 * responsable, rattacher un compte à des équipes ou à des clubs. L'admin ExtJS
 * avait une fenêtre par cas ; ici un seul composant, en simple ou multiple.
 *
 * Les listes sont longues (280 équipes, 45 clubs, des centaines de joueurs) :
 * la recherche est indispensable, pas décorative.
 */
export default {
    props: {
        title: { type: String, required: true },
        /** [{ value, label, hint? }] */
        items: { type: Array, required: true },
        /** true : cases à cocher ; false : un seul choix */
        multiple: { type: Boolean, default: false },
        /** Valeurs déjà sélectionnées (mode multiple) */
        selected: { type: Array, default: () => [] },
        confirmLabel: { type: String, default: 'Valider' },
        /** Texte d'aide sous le titre */
        help: { type: String, default: '' },
        isBusy: { type: Boolean, default: false },
    },
    emits: ['confirm', 'close'],
    template: `
      <dialog class="modal modal-open">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-lg">{{ title }}</h3>
          <p v-if="help" class="text-sm text-base-content/60 mt-1">{{ help }}</p>

          <input v-model.trim="search"
                 type="text"
                 class="input input-bordered input-sm w-full my-3"
                 placeholder="Rechercher…"/>

          <div class="max-h-80 overflow-y-auto border border-base-300 rounded">
            <p v-if="visible.length === 0" class="p-3 text-sm text-base-content/60">
              Aucun résultat.
            </p>
            <label v-for="item in visible"
                   :key="item.value"
                   class="flex items-start gap-2 px-3 py-2 border-b border-base-200 last:border-0 cursor-pointer hover:bg-base-200">
              <input v-if="multiple"
                     type="checkbox"
                     class="checkbox checkbox-sm checkbox-primary mt-0.5"
                     :value="item.value"
                     v-model="chosen"/>
              <input v-else
                     type="radio"
                     class="radio radio-sm radio-primary mt-0.5"
                     :value="item.value"
                     v-model="single"/>
              <span class="text-sm">
                {{ item.label }}
                <span v-if="item.hint" class="block text-xs text-base-content/50">{{ item.hint }}</span>
              </span>
            </label>
          </div>

          <p class="text-xs text-base-content/60 mt-2">
            {{ visible.length }} / {{ items.length }} —
            <span v-if="multiple">{{ chosen.length }} sélectionné(s)</span>
            <span v-else>{{ single === null ? 'aucune sélection' : '1 sélectionné' }}</span>
          </p>

          <div class="modal-action">
            <button type="button" class="btn btn-ghost" @click="$emit('close')">Annuler</button>
            <button type="button" class="btn btn-primary" :disabled="!canConfirm || isBusy" @click="confirm">
              <span v-if="isBusy" class="loading loading-spinner loading-xs"></span>
              {{ confirmLabel }}
            </button>
          </div>
        </div>
        <div class="modal-backdrop" @click="$emit('close')"></div>
      </dialog>
    `,
    data() {
        return {
            search: '',
            chosen: [...this.selected],
            single: null,
        };
    },
    computed: {
        visible() {
            const terms = this.search.toLowerCase().split(',').map((t) => t.trim()).filter(Boolean);
            if (terms.length === 0) {
                return this.items;
            }
            return this.items.filter((item) => {
                const haystack = (String(item.label) + ' ' + String(item.hint ?? '')).toLowerCase();
                return terms.some((t) => haystack.includes(t));
            });
        },
        canConfirm() {
            // En multiple, une sélection vide est légitime : elle veut dire
            // « détacher tout ».
            return this.multiple || this.single !== null;
        },
    },
    methods: {
        confirm() {
            this.$emit('confirm', this.multiple ? this.chosen : [this.single]);
        },
    },
};
