/**
 * Tableau de bord des indicateurs (issue #265, lot 5).
 * Remplace `js/view/view/Indicators.js`.
 *
 * Le seul écran du chantier qui n'est pas une grille : une tuile par
 * indicateur, cliquable pour voir le détail.
 *
 * `ajax/indicators.php` fonctionne en deux temps, et on garde ce découpage :
 * `mode=list` rend les 47 libellés tout de suite, puis un `mode=detail&id=N`
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
            <h3 class="font-bold text-lg mb-1">{{ opened.fieldLabel }}</h3>
            <p class="text-sm text-base-content/60 mb-4">{{ opened.details.length }} ligne(s)</p>
            <div class="overflow-x-auto max-h-[60vh]">
              <table class="table table-zebra table-sm">
                <thead>
                  <tr><th v-for="c in detailColumns" :key="c">{{ c }}</th></tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in opened.details" :key="i">
                    <td v-for="c in detailColumns" :key="c">{{ row[c] }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-action">
              <button class="btn btn-ghost btn-sm" @click="exportCsv">
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
        };
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
    },
    created() {
        this.load();
    },
    methods: {
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
         * Les 47 requêtes ne partent pas d'un bloc : chacune exécute du SQL
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
            for (const row of this.opened.details) {
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
