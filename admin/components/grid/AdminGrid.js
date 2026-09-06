import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Grille d'administration générique (issue #265, lot 0).
 *
 * Remplace `Ufolep13Volley.view.grid.ufolep` (ExtJS) : recherche multi-termes,
 * tri par colonne, pagination, sélection multiple, création / édition via une
 * fenêtre modale, suppression en masse, export CSV.
 *
 * Les ~20 écrans CRUD de l'admin sont la même chose à quelques colonnes près :
 * un écran se réduit donc à déclarer ses colonnes, ses champs de formulaire et
 * ses URLs.
 *
 *   <admin-grid
 *     title="Gestion des gymnases"
 *     :columns="columns"
 *     :fields="fields"
 *     fetch-url="/rest/action.php/court/getGymnasiums"
 *     save-url="/rest/action.php/court/saveGymnasium"
 *     delete-url="/rest/action.php/court/delete"
 *   />
 *
 * Conventions backend reprises de l'admin ExtJS :
 *  - lecture   : GET, renvoie un tableau d'objets
 *  - écriture  : POST de tous les champs ; `id` vide => INSERT, sinon UPDATE
 *  - suppression : POST `ids` = identifiants joints par des virgules
 */
export default {
    components: {
        'admin-edit-modal': defineAsyncComponent(() => import('./AdminEditModal.js')),
    },
    props: {
        title: { type: String, required: true },
        /** [{ key, label, align?, width?, format?(value, row) }] */
        columns: { type: Array, required: true },
        /** Champs de la fenêtre d'édition — voir AdminEditModal */
        fields: { type: Array, default: () => [] },
        fetchUrl: { type: String, required: true },
        saveUrl: { type: String, default: null },
        deleteUrl: { type: String, default: null },
        idField: { type: String, default: 'id' },
        /** Libellé au singulier, pour les boutons et messages */
        entityLabel: { type: String, default: 'élément' },
        /** Filtre supplémentaire piloté par l'écran : (row) => bool */
        rowFilter: { type: Function, default: null },
    },
    template: `
      <div class="p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <h1 class="text-2xl font-bold">{{ title }}</h1>
          <div class="flex flex-wrap gap-2">
            <button v-if="saveUrl" @click="openCreate" class="btn btn-primary btn-sm">
              <i class="fas fa-plus"></i> Créer
            </button>
            <button v-if="saveUrl" @click="openEdit" class="btn btn-sm" :disabled="selection.length !== 1">
              <i class="fas fa-pen"></i> Éditer
            </button>
            <button v-if="deleteUrl" @click="confirmDelete" class="btn btn-error btn-sm" :disabled="!selection.length">
              <i class="fas fa-trash"></i> Supprimer<span v-if="selection.length"> ({{ selection.length }})</span>
            </button>
            <button @click="exportCsv" class="btn btn-outline btn-sm" :disabled="!filteredRows.length">
              <i class="fas fa-file-csv"></i> Export
            </button>
            <button @click="fetchRows" class="btn btn-ghost btn-sm" title="Rafraîchir">
              <i class="fas fa-rotate"></i>
            </button>
            <!-- Actions propres à l'écran (nommer responsable, reset mot de
                 passe, import…) : la grille ne les connaît pas. -->
            <slot name="actions" :selection="selection" :rows="rows" :reload="fetchRows"></slot>
          </div>
        </div>

        <!-- Filtres propres à l'écran, au-dessus de la recherche -->
        <div v-if="$slots.filters" class="flex flex-wrap items-center gap-4 mb-3">
          <slot name="filters" :rows="rows"></slot>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-3">
          <input v-model.trim="search"
                 type="text"
                 class="input input-bordered input-sm w-full sm:w-96"
                 placeholder="Rechercher… (plusieurs termes séparés par des virgules)"/>
          <span class="text-sm text-base-content/60">
            {{ filteredRows.length }} / {{ rows.length }} {{ entityLabel }}(s)
          </span>
          <label class="flex items-center gap-2 text-sm ml-auto">
            <span>par page</span>
            <select v-model.number="pageSize" class="select select-bordered select-sm">
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
              <option :value="filteredRows.length || 1">tout</option>
            </select>
          </label>
        </div>

        <div v-if="loading" class="flex justify-center py-10">
          <span class="loading loading-spinner loading-lg text-primary"></span>
        </div>

        <div v-else-if="!rows.length" class="alert alert-info">
          <i class="fas fa-circle-info"></i>
          <span>Aucune donnée.</span>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-xs md:table-sm table-pin-rows">
            <thead>
            <tr>
              <th class="w-8">
                <input type="checkbox"
                       class="checkbox checkbox-xs"
                       :checked="allPageSelected"
                       @change="toggleAll($event.target.checked)"/>
              </th>
              <th v-for="col in columns"
                  :key="col.key"
                  :class="['cursor-pointer select-none', col.align === 'right' ? 'text-right' : '']"
                  @click="sortBy(col.key)">
                {{ col.label }}
                <i v-if="sort.key === col.key"
                   :class="sort.asc ? 'fas fa-caret-up' : 'fas fa-caret-down'"></i>
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in pageRows"
                :key="row[idField]"
                :class="{'bg-primary/10': isSelected(row)}"
                @click="toggle(row)">
              <td><input type="checkbox" class="checkbox checkbox-xs" :checked="isSelected(row)" @click.stop="toggle(row)"/></td>
              <td v-for="col in columns"
                  :key="col.key"
                  :class="col.align === 'right' ? 'text-right' : ''">
                {{ render(col, row) }}
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pageCount > 1" class="flex justify-center items-center gap-2 mt-4">
          <button class="btn btn-sm" :disabled="page === 1" @click="page--">«</button>
          <span class="text-sm">page {{ page }} / {{ pageCount }}</span>
          <button class="btn btn-sm" :disabled="page === pageCount" @click="page++">»</button>
        </div>

        <admin-edit-modal v-if="editing"
                          :title="title"
                          :fields="fields"
                          :record="editing"
                          :save-url="saveUrl"
                          @close="editing = null"
                          @saved="onSaved"/>
      </div>
    `,
    data() {
        return {
            rows: [],
            loading: false,
            isLoading: false,   // attendu par toaster.js
            search: '',
            sort: { key: null, asc: true },
            page: 1,
            pageSize: 25,
            selection: [],
            editing: null,
        };
    },
    computed: {
        filteredRows() {
            const base = this.rowFilter
                ? this.sortedRows.filter((r) => this.rowFilter(r))
                : this.sortedRows;
            if (!this.search) {
                return base;
            }
            // Recherche multi-termes séparés par des virgules, comme la grille ExtJS
            const terms = this.search.split(',')
                .map((t) => t.trim().toLowerCase())
                .filter((t) => t.length);
            return base.filter((row) => {
                const haystack = this.columns
                    .map((c) => String(row[c.key] ?? ''))
                    .join(' ')
                    .toLowerCase();
                return terms.some((t) => haystack.includes(t));
            });
        },
        sortedRows() {
            if (!this.sort.key) {
                return this.rows;
            }
            const key = this.sort.key;
            const dir = this.sort.asc ? 1 : -1;
            return [...this.rows].sort((a, b) => {
                const va = a[key], vb = b[key];
                const na = Number(va), nb = Number(vb);
                if (va !== '' && vb !== '' && !Number.isNaN(na) && !Number.isNaN(nb)) {
                    return (na - nb) * dir;
                }
                return String(va ?? '').localeCompare(String(vb ?? ''), 'fr') * dir;
            });
        },
        pageCount() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.pageSize));
        },
        pageRows() {
            const start = (this.page - 1) * this.pageSize;
            return this.filteredRows.slice(start, start + this.pageSize);
        },
        allPageSelected() {
            return this.pageRows.length > 0 && this.pageRows.every((r) => this.isSelected(r));
        },
    },
    watch: {
        search() { this.page = 1; },
        rowFilter() { this.page = 1; },
        pageSize() { this.page = 1; },
    },
    created() {
        this.fetchRows();
    },
    methods: {
        render(col, row) {
            const value = row[col.key];
            return col.format ? col.format(value, row) : (value ?? '');
        },
        isSelected(row) {
            return this.selection.includes(row[this.idField]);
        },
        toggle(row) {
            const id = row[this.idField];
            const i = this.selection.indexOf(id);
            if (i === -1) {
                this.selection.push(id);
            } else {
                this.selection.splice(i, 1);
            }
        },
        toggleAll(checked) {
            const ids = this.pageRows.map((r) => r[this.idField]);
            this.selection = checked
                ? [...new Set([...this.selection, ...ids])]
                : this.selection.filter((id) => !ids.includes(id));
        },
        sortBy(key) {
            this.sort = this.sort.key === key
                ? { key, asc: !this.sort.asc }
                : { key, asc: true };
        },
        fetchRows() {
            this.loading = true;
            this.selection = [];
            axios.get(this.fetchUrl)
                .then(({ data }) => {
                    this.rows = Array.isArray(data) ? data : [];
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.loading = false; });
        },
        openCreate() {
            this.editing = {};
        },
        openEdit() {
            const id = this.selection[0];
            const row = this.rows.find((r) => r[this.idField] === id);
            if (row) {
                this.editing = { ...row };
            }
        },
        onSaved() {
            this.editing = null;
            this.fetchRows();
        },
        confirmDelete() {
            const n = this.selection.length;
            if (!window.confirm(`Supprimer ${n} ${this.entityLabel}(s) ? Cette action est irréversible.`)) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', this.selection.join(','));
            axios.post(this.deleteUrl, formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchRows();
                })
                .catch((error) => onError(this, error));
        },
        exportCsv() {
            const sep = ';';
            const escape = (v) => {
                const s = String(v ?? '');
                return /[";\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
            };
            const lines = [this.columns.map((c) => escape(c.label)).join(sep)];
            for (const row of this.filteredRows) {
                lines.push(this.columns.map((c) => escape(this.render(c, row))).join(sep));
            }
            // BOM UTF-8 : sans lui, Excel massacre les accents
            const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.title.replace(/[^\w-]+/g, '_').toLowerCase()}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        },
    },
};
