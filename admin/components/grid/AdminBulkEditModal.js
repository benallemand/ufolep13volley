import { onError, onSuccess } from '../../../toaster.js';
import { buildFormData, initialForm } from './editPayload.js';

/**
 * Édition en masse (issue #309) : affecter une division à douze équipes, ou
 * marquer douze inscriptions comme payées, sans rouvrir la fenêtre douze fois.
 *
 * Ne propose que les champs que l'écran déclare modifiables en lot
 * (`bulk-fields` de la grille), et n'applique que ceux qu'on coche « modifier ».
 *
 * Chaque ligne est ensuite postée comme par l'édition simple — tous les champs
 * de l'écran, valeurs de la ligne, champs cochés remplacés, et son identifiant
 * (`editPayload.js`) : les méthodes PHP écrivent chaque colonne reçue, n'envoyer
 * que le champ modifié viderait les autres. Un appel par ligne, enchaînés, comme
 * `delete-mode="id"` : on s'arrête à la première erreur, en disant sur quelle
 * ligne, et les lignes déjà enregistrées le restent.
 */
export default {
    props: {
        title: { type: String, required: true },
        /** Tous les champs de l'écran — voir AdminEditModal */
        fields: { type: Array, required: true },
        /** Noms des champs modifiables en lot */
        bulkFields: { type: Array, required: true },
        /** Lignes sélectionnées */
        rows: { type: Array, required: true },
        saveUrl: { type: String, required: true },
        idField: { type: String, default: 'id' },
        entityLabel: { type: String, default: 'élément' },
        /** (row) => libellé d'une ligne, pour les messages */
        rowLabel: { type: Function, default: (row) => String(row.id ?? '') },
    },
    emits: ['close', 'saved'],
    template: `
      <dialog class="modal modal-open">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-lg">Modifier {{ rows.length }} {{ entityLabel }}(s) — {{ title }}</h3>
          <p class="text-sm text-base-content/60 mt-1 mb-4">
            Cochez les champs à modifier : les autres gardent la valeur propre à chaque ligne.
          </p>

          <form @submit.prevent="submit" class="space-y-3">
            <div v-for="field in editable" :key="field.name"
                 class="flex flex-wrap items-center gap-3 border border-base-300 rounded-lg p-3"
                 :class="apply[field.name] ? 'border-primary bg-primary/5' : ''">
              <label class="flex items-center gap-2 cursor-pointer w-44 shrink-0">
                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                       v-model="apply[field.name]" :data-testid="'bulk-apply-' + field.name"/>
                <span class="text-sm font-medium">{{ field.label }}</span>
              </label>

              <select v-if="field.type === 'select'" v-model="values[field.name]"
                      class="select select-bordered select-sm flex-1 min-w-40"
                      :disabled="!apply[field.name]" :data-testid="'bulk-value-' + field.name">
                <option value="">—</option>
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 flex-1">
                <input type="checkbox" v-model="values[field.name]" class="checkbox checkbox-sm"
                       :disabled="!apply[field.name]" :data-testid="'bulk-value-' + field.name"/>
                <span class="text-sm">{{ values[field.name] ? 'oui' : 'non' }}</span>
              </label>
              <input v-else v-model="values[field.name]"
                     :type="field.type === 'date' ? 'date' : (field.type || 'text')"
                     class="input input-bordered input-sm flex-1 min-w-40"
                     :min="field.min" :max="field.max" :placeholder="field.placeholder"
                     :disabled="!apply[field.name]" :data-testid="'bulk-value-' + field.name"/>
            </div>

            <div v-if="progress" class="text-sm">
              <progress class="progress progress-primary w-full" :value="progress.done" :max="rows.length"></progress>
              {{ progress.done }} / {{ rows.length }} enregistrée(s)
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" :disabled="isLoading" @click="$emit('close')">Annuler</button>
              <button type="submit" class="btn btn-primary" :disabled="isLoading || !chosen.length"
                      data-testid="bulk-submit">
                <span v-if="isLoading" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-save"></i>
                Appliquer à {{ rows.length }} ligne(s)
              </button>
            </div>
          </form>
        </div>
        <div class="modal-backdrop" @click="isLoading || $emit('close')"></div>
      </dialog>
    `,
    data() {
        const apply = {};
        const values = {};
        for (const name of this.bulkFields) {
            const field = this.fields.find((f) => f.name === name);
            apply[name] = false;
            values[name] = field && field.type === 'checkbox' ? false : '';
        }
        return { apply, values, isLoading: false, progress: null };
    },
    computed: {
        editable() {
            return this.bulkFields
                .map((name) => this.fields.find((f) => f.name === name))
                .filter((f) => f && !f.hidden && f.type !== 'file');
        },
        chosen() {
            return this.editable.filter((f) => this.apply[f.name]);
        },
    },
    methods: {
        submit() {
            const chosen = this.chosen;
            const noms = chosen.map((f) => f.label).join(', ');
            if (!window.confirm(`Modifier « ${noms} » sur ${this.rows.length} ${this.entityLabel}(s) ?`)) {
                return;
            }
            this.isLoading = true;
            this.progress = { done: 0 };
            let current = null;
            this.rows
                .reduce((chain, row) => chain.then(() => {
                    current = row;
                    const form = initialForm(this.fields, row);
                    for (const field of chosen) {
                        form[field.name] = this.values[field.name];
                    }
                    return axios.post(this.saveUrl, buildFormData(this.fields, form, row, this.idField))
                        .then(() => { this.progress.done++; });
                }), Promise.resolve())
                .then(() => {
                    onSuccess(this, { data: { message: `${this.rows.length} ${this.entityLabel}(s) modifié(e)s.` } });
                    this.$emit('saved');
                })
                .catch((error) => {
                    const message = error?.response?.data?.message || error.message;
                    onError(this, { response: { data: {
                        message: `Arrêt sur « ${this.rowLabel(current)} » : ${message} `
                            + `(${this.progress.done} / ${this.rows.length} enregistrée(s) avant l'erreur).`,
                    } } });
                    this.$emit('saved');
                })
                .finally(() => { this.isLoading = false; });
        },
    },
};
