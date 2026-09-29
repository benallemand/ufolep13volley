import { compareCells } from '../grid/compareCells.js';

/**
 * Tableau de bord des indicateurs (issue #265, lot 5).
 * Remplace `js/view/view/Indicators.js`.
 *
 * Le seul écran du chantier qui n'est pas une grille : une tuile par
 * indicateur, cliquable pour voir le détail.
 *
 * `ajax/indicators.php` fonctionne en deux temps, et on garde ce découpage :
 * `mode=list` rend les 49 libellés tout de suite, puis un `mode=detail&id=N`
 * par indicateur exécute sa requête. Tout charger d'un coup prendrait des
 * dizaines de secondes avant le premier pixel.
 *
 * Comme dans l'écran ExtJS, **une tuile à zéro disparaît** : le tableau de bord
 * ne montre que ce sur quoi il y a quelque chose à faire.
 *
 * Cet endpoint n'est pas sous `rest/` : il porte sa propre garde admin depuis
 * l'issue #284, où il répondait à n'importe qui.
 */
export default {
    template: `
      <div class="p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <h1 class="text-2xl font-bold">Indicateurs</h1>
          <div class="flex items-center gap-2">
            <span v-if="pending" class="text-sm text-base-content/60">
              {{ done }} / {{ total }} calculés…
            </span>
            <button @click="load" class="btn btn-ghost btn-sm" :disabled="pending" title="Rafraîchir">
              <i class="fas fa-rotate"></i>
            </button>
          </div>
        </div>

        <div v-if="error" class="alert alert-error mb-4">
          <i class="fas fa-triangle-exclamation"></i>
          <span>{{ error }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-4 mb-3">
          <input v-model.trim="search"
                 type="text"
                 class="input input-bordered input-sm w-full sm:w-96"
                 placeholder="Rechercher un indicateur…"/>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="alertsOnly" type="checkbox" class="checkbox checkbox-sm"/>
            <span>Alertes seulement</span>
          </label>
          <span class="text-sm text-base-content/60">{{ visible.length }} indicateur(s)</span>
        </div>

        <div v-if="!pending && visible.length === 0" class="alert">
          <i class="fas fa-circle-check"></i>
          <span>Rien à signaler.</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          <button v-for="ind in visible"
                  :key="ind.id"
                  class="card border text-left transition hover:shadow-md"
                  :class="ind.type === 'alert'
                    ? 'bg-error/10 border-error/30 hover:border-error'
                    : 'bg-info/10 border-info/30 hover:border-info'"
                  :disabled="!ind.details || !ind.details.length"
                  @click="opened = ind">
            <div class="card-body p-4 items-center text-center gap-1">
              <div class="text-3xl font-bold"
                   :class="ind.type === 'alert' ? 'text-error' : 'text-info'">
                {{ ind.value }}
              </div>
              <div class="text-xs leading-snug">{{ ind.fieldLabel }}</div>
            </div>
          </button>

          <div v-for="n in loadingCount" :key="'skel-' + n"
               class="card border bg-base-200 border-base-300 animate-pulse h-28"></div>
        </div>

        <dialog v-if="opened" class="modal modal-open">
          <div class="modal-box max-w-6xl">
            <h3 class="font-bold text-lg mb-3">{{ opened.fieldLabel }}</h3>
            <!-- Recherche rapide, filtres par colonne et tri (issue #340) :
                 mêmes conventions que les grilles d'administration. -->
            <div class="flex flex-wrap items-center gap-3 mb-3">
              <input v-model.trim="detailSearch"
                     type="text"
                     class="input input-bordered input-sm w-full sm:w-96"
                     aria-label="Recherche rapide dans le détail"
                     placeholder="Rechercher… (plusieurs termes séparés par des virgules)"/>
              <span class="text-sm text-base-content/60" data-testid="detail-count">
                {{ detailRows.length }} / {{ opened.details.length }} ligne(s)
              </span>
              <button v-if="hasDetailFilters" class="btn btn-ghost btn-xs" @click="resetDetailFilters">
                <i class="fas fa-filter-circle-xmark"></i> Effacer les filtres
              </button>
            </div>
            <div class="overflow-auto max-h-[60vh]">
              <table class="table table-zebra table-sm table-pin-rows">
                <thead>
                  <tr>
                    <th v-for="c in detailColumns"
                        :key="c"
                        class="cursor-pointer select-none whitespace-nowrap"
                        :title="'Trier par ' + c"
                        @click="sortDetailBy(c)">
                      {{ c }}
                      <i v-if="detailSort.key === c"
                         :class="detailSort.asc ? 'fas fa-caret-up' : 'fas fa-caret-down'"></i>
                    </th>
                  </tr>
                  <tr>
                    <th v-for="c in detailColumns" :key="'filtre-' + c" class="py-1">
                      <input v-model.trim="columnFilters[c]"
                             type="text"
                             class="input input-bordered input-xs w-full min-w-24 font-normal"
                             :aria-label="'Filtrer la colonne ' + c"
                             placeholder="Filtrer…"/>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in detailRows" :key="i">
                    <td v-for="c in detailColumns" :key="c">{{ row[c] }}</td>
                  </tr>
                  <tr v-if="!detailRows.length">
                    <td :colspan="detailColumns.length" class="text-center text-base-content/60 py-6">
                      Aucune ligne ne correspond aux filtres.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-action">
              <!-- Le tableau de bord dit CE QUI ne va pas ; ce bouton dit OÙ
                   aller le corriger (issue #312). Il n'apparaît que sur les
                   indicateurs qui déclarent un écran cible : la plupart
                   croisent plusieurs entités et n'en ont pas. -->
              <button v-if="opened.target && opened.ids && opened.ids.length"
                      class="btn btn-primary btn-sm"
                      @click="openTarget(opened)">
                <i class="fas fa-arrow-right"></i>
                Corriger ces {{ opened.ids.length }} ligne(s)
              </button>
              <button class="btn btn-ghost btn-sm" :disabled="!detailRows.length" @click="exportCsv">
                <i class="fas fa-file-csv"></i> Export
              </button>
              <button class="btn btn-sm" @click="opened = null">Fermer</button>
            </div>
          </div>
          <div class="modal-backdrop" @click="opened = null"></div>
        </dialog>
      </div>
    `,
    data() {
        return {
            indicators: [],
            total: 0,
            done: 0,
            search: '',
            alertsOnly: false,
            opened: null,
            error: null,
            // État du détail ouvert, remis à zéro à chaque ouverture : un
            // filtre posé sur un indicateur n'a aucun sens sur le suivant.
            detailSearch: '',
            columnFilters: {},
            detailSort: { key: null, asc: true },
        };
    },
    watch: {
        opened() {
            this.resetDetailFilters();
            this.detailSort = { key: null, asc: true };
        },
    },
    computed: {
        pending() {
            return this.total > 0 && this.done < this.total;
        },
        /** Tuiles grises restant à calculer, pour ne pas afficher une page vide. */
        loadingCount() {
            return Math.max(0, this.total - this.done);
        },
        visible() {
            const terms = this.search.toLowerCase().split(',').map((t) => t.trim()).filter(Boolean);
            return this.indicators.filter((ind) => {
                if (this.alertsOnly && ind.type !== 'alert') {
                    return false;
                }
                if (terms.length === 0) {
                    return true;
                }
                const label = String(ind.fieldLabel ?? '').toLowerCase();
                return terms.some((t) => label.includes(t));
            });
        },
        detailColumns() {
            const first = this.opened && this.opened.details && this.opened.details[0];
            return first ? Object.keys(first) : [];
        },
        /**
         * Lignes du détail telles qu'affichées ET exportées : filtres par
         * colonne (tous doivent correspondre), puis recherche rapide (un des
         * termes suffit, comme dans `AdminGrid`), puis tri.
         */
        detailRows() {
            if (!this.opened) {
                return [];
            }
            const text = (v) => String(v ?? '').toLowerCase();
            const filters = Object.entries(this.columnFilters)
                .map(([c, v]) => [c, text(v).trim()])
                .filter(([, v]) => v.length);
            const terms = this.detailSearch.split(',')
                .map((t) => t.trim().toLowerCase())
                .filter((t) => t.length);
            const rows = (this.opened.details || []).filter((row) => {
                if (!filters.every(([c, v]) => text(row[c]).includes(v))) {
                    return false;
                }
                if (!terms.length) {
                    return true;
                }
                const haystack = this.detailColumns.map((c) => text(row[c])).join(' ');
                return terms.some((t) => haystack.includes(t));
            });
            const { key, asc } = this.detailSort;
            if (!key) {
                return rows;
            }
            // `filter` a déjà rendu une copie : on peut trier sur place.
            return rows.sort((a, b) => compareCells(a[key], b[key]) * (asc ? 1 : -1));
        },
        hasDetailFilters() {
            return this.detailSearch.length > 0
                || Object.values(this.columnFilters).some((v) => String(v ?? '').trim().length);
        },
    },
    created() {
        this.load();
    },
    methods: {
        /** Un clic trie par ordre croissant, le suivant inverse. */
        sortDetailBy(key) {
            this.detailSort = this.detailSort.key === key
                ? { key, asc: !this.detailSort.asc }
                : { key, asc: true };
        },
        resetDetailFilters() {
            this.detailSearch = '';
            this.columnFilters = {};
        },
        /**
         * Ouvre l'écran de correction, filtré sur les lignes de l'indicateur
         * (issue #312). `AdminGrid` lit `?ids=` tout seul : aucun écran n'a à
         * le déclarer.
         */
        openTarget(indicator) {
            this.opened = null;
            this.$router.push({
                path: '/' + indicator.target,
                query: { ids: indicator.ids.join(',') },
            });
        },
        load() {
            this.indicators = [];
            this.done = 0;
            this.total = 0;
            this.error = null;
            axios.get('/ajax/indicators.php', { params: { mode: 'list' } })
                .then(({ data }) => {
                    const liste = (data && data.results) || [];
                    this.total = liste.length;
                    return this.loadDetails(liste);
                })
                .catch((e) => {
                    this.error = "Impossible de charger les indicateurs : "
                        + (e.response && e.response.status === 403
                            ? "réservés aux administrateurs."
                            : e.message);
                });
        },
        /**
         * Les 49 requêtes ne partent pas d'un bloc : chacune exécute du SQL
         * d'exploitation, et le navigateur n'ouvre de toute façon que quelques
         * connexions par hôte. On en garde six en vol, ce qui laisse les
         * premières tuiles s'afficher vite.
         */
        loadDetails(liste) {
            const queue = [...liste];
            const worker = () => {
                const ind = queue.shift();
                if (!ind) {
                    return Promise.resolve();
                }
                return axios.get('/ajax/indicators.php', { params: { mode: 'detail', id: ind.id } })
                    .then(({ data }) => {
                        const value = (data && data.value) || 0;
                        // Une tuile à zéro n'est pas affichée : rien à faire dessus.
                        if (value !== 0) {
                            this.indicators.push({
                                id: ind.id,
                                fieldLabel: ind.fieldLabel,
                                type: ind.type,
                                value,
                                details: (data && data.details) || [],
                                // Écran de correction et lignes concernées, quand
                                // l'indicateur en déclare (issue #312).
                                target: (data && data.target) || null,
                                ids: (data && data.ids) || [],
                            });
                            this.indicators.sort((a, b) => {
                                if (a.type !== b.type) {
                                    return a.type === 'alert' ? -1 : 1;
                                }
                                return b.value - a.value;
                            });
                        }
                    })
                    .catch(() => { /* un indicateur en erreur ne bloque pas les autres */ })
                    .finally(() => {
                        this.done += 1;
                        return worker();
                    });
            };
            return Promise.all(Array.from({ length: 6 }, worker));
        },
        exportCsv() {
            const sep = ';';
            const escape = (v) => {
                const s = String(v ?? '');
                return /[";\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
            };
            const lines = [this.detailColumns.map(escape).join(sep)];
            // Ce qu'on voit, dans l'ordre où on le voit : filtres et tri
            // compris, comme l'export des grilles d'administration.
            for (const row of this.detailRows) {
                lines.push(this.detailColumns.map((c) => escape(row[c])).join(sep));
            }
            // BOM : sans lui Excel lit le CSV en latin-1 et casse les accents.
            const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = this.opened.fieldLabel.replace(/[^\w\-]+/g, '_') + '.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        },
    },
};
