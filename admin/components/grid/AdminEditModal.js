import { onError, onSuccess } from '../../../toaster.js';

/**
 * Fenêtre modale d'édition générique (issue #265, lot 0).
 *
 * Remplace le motif `Ext.window.Window` + `Ext.form.Panel` des écrans admin
 * ExtJS. L'appelant décrit ses champs, le composant s'occupe du rendu, de la
 * validation HTML5 et de l'envoi.
 *
 * Description d'un champ :
 *   { name, label, type?, required?, options?, min?, max?, placeholder?, help? }
 *   type : 'text' (défaut) | 'number' | 'textarea' | 'select' | 'checkbox'
 *          | 'date' | 'email' | 'password'
 *   options (select) : [{ value, label }]
 *
 * L'`id` n'est pas un champ : il est repris du record et renvoyé tel quel, ce
 * qui fait qu'un record vide donne un INSERT et un record chargé un UPDATE —
 * convention de `Generic::save()`.
 */
export default {
    props: {
        title: { type: String, required: true },
        fields: { type: Array, required: true },
        /** Objet à éditer ; `{}` pour une création */
        record: { type: Object, required: true },
        saveUrl: { type: String, required: true },
        idField: { type: String, default: 'id' },
    },
    emits: ['close', 'saved'],
    template: `
      <dialog class="modal modal-open">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-lg mb-4">
            {{ isCreation ? 'Créer' : 'Modifier' }} — {{ title }}
          </h3>

          <form @submit.prevent="submit" class="space-y-3">
            <div v-for="field in fields" :key="field.name" class="form-control">
              <label class="label py-1" :for="'f-' + field.name">
                <span class="label-text">
                  {{ field.label }}
                  <span v-if="field.required" class="text-error">*</span>
                </span>
              </label>

              <textarea v-if="field.type === 'textarea'"
                        :id="'f-' + field.name"
                        v-model="form[field.name]"
                        class="textarea textarea-bordered"
                        rows="3"
                        :required="field.required"></textarea>

              <select v-else-if="field.type === 'select'"
                      :id="'f-' + field.name"
                      v-model="form[field.name]"
                      class="select select-bordered"
                      :required="field.required">
                <option value="">—</option>
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>

              <input v-else-if="field.type === 'checkbox'"
                     :id="'f-' + field.name"
                     v-model="form[field.name]"
                     type="checkbox"
                     class="checkbox checkbox-primary"/>

              <input v-else
                     :id="'f-' + field.name"
                     v-model="form[field.name]"
                     :type="field.type || 'text'"
                     class="input input-bordered"
                     :required="field.required"
                     :min="field.min"
                     :max="field.max"
                     :placeholder="field.placeholder"/>

              <label v-if="field.help" class="label py-0">
                <span class="label-text-alt text-base-content/60">{{ field.help }}</span>
              </label>
            </div>

            <div class="modal-action">
              <button type="button" class="btn btn-ghost" @click="$emit('close')">Annuler</button>
              <button type="submit" class="btn btn-primary" :disabled="isLoading">
                <span v-if="isLoading" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-save"></i>
                Enregistrer
              </button>
            </div>
          </form>
        </div>
        <form method="dialog" class="modal-backdrop" @click="$emit('close')"><button>close</button></form>
      </dialog>
    `,
    data() {
        return {
            form: {},
            isLoading: false,
        };
    },
    computed: {
        isCreation() {
            return !this.record[this.idField];
        },
    },
    created() {
        // On part des champs déclarés pour que Vue voie toutes les clés, puis on
        // recouvre avec les valeurs du record.
        const form = {};
        for (const field of this.fields) {
            form[field.name] = field.type === 'checkbox' ? false : '';
        }
        for (const [k, v] of Object.entries(this.record)) {
            form[k] = v;
        }
        this.form = form;
    },
    methods: {
        submit() {
            this.isLoading = true;
            const formData = new FormData();
            for (const field of this.fields) {
                const value = this.form[field.name];
                formData.append(
                    field.name,
                    field.type === 'checkbox' ? (value ? '1' : '0') : (value ?? '')
                );
            }
            // L'identifiant est TOUJOURS envoyé, vide à la création : c'est ce
            // que faisait le champ caché `id` des formulaires ExtJS, et
            // plusieurs méthodes le déclarent en paramètre obligatoire
            // (`Club::saveClub($id, ...)`). Côté base, `Generic::save()` fait un
            // INSERT sur une valeur vide et un UPDATE sinon.
            formData.append(this.idField, this.record[this.idField] ?? '');
            axios.post(this.saveUrl, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.$emit('saved');
                })
                .catch((error) => onError(this, error));
        },
    },
};
