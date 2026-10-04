import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';
import { compareCells } from './compareCells.js';
import { clearState, hasExplicitQuery, loadState, saveState, stateKey } from './gridState.js';
import { filterKind, isEmptyFilter, matchesFilter, selectOptions } from './columnFilters.js';

/**
 * Taille de page d'une première visite (issues #311, #408) : `null` =
 * automatique. Les grilles chargent toutes leurs lignes d'un coup ; la
 * pagination ne sert qu'à limiter ce qui est DESSINÉ. Jusqu'à
 * `AUTO_ALL_ROWS` lignes, tout afficher reste fluide (~130-200 ms pour 300
 * lignes) ; au-delà, des pages de `AUTO_PAGE_SIZE` (Joueurs : 4 s pour 3 663
 * lignes en « tout », 160 ms par page de 100). Mesures dans #408.
 */
const DEFAULT_PAGE_SIZE = null;
const AUTO_ALL_ROWS = 500;
const AUTO_PAGE_SIZE = 100;
/** Ancien défaut (25) : mémorisé avant #408, il est ignoré à la restauration. */
const LEGACY_DEFAULT_PAGE_SIZE = 25;

/**
 * Champs jamais cherchés par la recherche rapide (#408) : identifiants
 * techniques (chercher « 12 » ramènerait toute ligne dont un identifiant
 * contient 12) et chemins de fichiers (« players_pics/… » sur chaque joueur).
 */
const NOT_SEARCHED = /^(id|id_.*|.*_id|path_.*)$/;
/** Préfixe de département d'un numéro de licence imprimé (`013_…`, #404). */
const LICENCE_PREFIX = /^0?\d{2,3}_/;

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
        'admin-detail-drawer': defineAsyncComponent(() => import('./AdminDetailDrawer.js')),
        'admin-bulk-edit-modal': defineAsyncComponent(() => import('./AdminBulkEditModal.js')),
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
        /**
         * Forme attendue par l'action de suppression :
         *   'ids' (défaut) — un seul POST, identifiants joints par des virgules
         *                    (`Generic::delete()`)
         *   'id'           — un POST par ligne, pour les endpoints unitaires
         *                    (`News::deleteNews($id)`, `deleteCalendarEvent($id)`)
         */
        deleteMode: { type: String, default: 'ids' },
        idField: { type: String, default: 'id' },
        /**
         * Champs supplémentaires à exclure de la recherche rapide (#408), qui
         * porte sinon sur toutes les données de la ligne, affichées ou non.
         */
        searchExclude: { type: Array, default: () => [] },
        /** Libellé au singulier, pour les boutons et messages */
        entityLabel: { type: String, default: 'élément' },
        /** Filtre supplémentaire piloté par l'écran : (row) => bool */
        rowFilter: { type: Function, default: null },
        /**
         * Colonne de sélection. Désactivée d'office sur un écran de simple
         * consultation : sans save/delete elle ne sert à rien, et les lignes
         * n'ont pas toujours d'identifiant sur lequel s'appuyer.
         */
        selectable: { type: Boolean, default: null },
        /**
         * Rend les lignes visuellement cliquables. `row-click` est emis dans
         * tous les cas ; cette prop ne fait que le curseur et le survol -- un
         * emit declare disparait de `$attrs`, on ne peut donc pas deviner si
         * l'ecran ecoute.
         */
        rowClickable: { type: Boolean, default: false },
        /**
         * Tiroir de detail ouvert au clic sur une ligne (issue #308).
         *
         *   `true`   — sections deduites des colonnes de la grille
         *   objet    — { title(row), subtitle?, badge?, image?, sections? }
         *
         * Quand il est actif, le clic sur la ligne OUVRE LE TIROIR au lieu de
         * cocher la case : la selection passe alors par la case elle-meme. Les
         * ecrans qui ne declarent pas de tiroir gardent le comportement
         * historique, clic = selection.
         */
        detail: { type: [Boolean, Object], default: false },
        /**
         * Champs modifiables en lot (issue #309), parmi `fields` : noms.
         * Déclarés, ils permettent « Éditer » sur plusieurs lignes cochées.
         */
        bulkFields: { type: Array, default: () => [] },
    },
    /**
     * `reset-view` : « Réinitialiser la vue » a été cliqué (issue #311) ; un
     * écran qui mémorise ses propres filtres (`persistedFilters`) les remet
     * alors à zéro.
     */
    emits: ['row-click', 'reset-view'],
    template: `
      <!-- En-tête figé (#386). La grille occupe la hauteur de la fenêtre et
           défile dans sa propre zone, sinon le thead ne peut pas coller : le
           défilement horizontal des tables larges impose un conteneur en
           overflow, et un sticky ne colle qu'à son conteneur défilant.
             - lg et plus : titre, compteur et actions restent en haut ; la zone
               du dessous défile (filtres de l'écran, recherche, puis table dont
               le thead colle).
             - plus petit : la racine défile en entier, seul le thead colle ;
               la barre d'outils, sur plusieurs lignes, mangerait l'écran.
           4rem = la barre de navigation mobile d'AdminLayout. -->
      <div class="flex flex-col p-4 h-[calc(100dvh-4rem)] overflow-auto lg:h-dvh lg:overflow-hidden"
           ref="root" data-testid="grid-root">
        <div class="sticky left-0 z-20 flex flex-wrap items-center justify-between gap-2 mb-4 lg:mb-3"
             data-testid="grid-toolbar">
          <div class="flex flex-wrap items-baseline gap-x-3">
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <span class="text-sm text-base-content/60" data-testid="grid-count">
              {{ filteredRows.length }} / {{ rows.length }} {{ entityLabel }}(s)
            </span>
          </div>
          <div class="flex flex-wrap gap-2">
            <button v-if="saveUrl" @click="openCreate" class="btn btn-primary btn-sm">
              <i class="fas fa-plus"></i> Créer
            </button>
            <!-- Plusieurs lignes cochées : édition en masse, si l'écran déclare
                 des champs modifiables en lot (#309). -->
            <button v-if="saveUrl" @click="onEditClick" class="btn btn-sm" :disabled="!canEdit"
                    data-testid="grid-edit"
                    :title="selection.length > 1 && !bulkFields.length ? 'Une seule ligne à la fois sur cet écran' : ''">
              <i class="fas fa-pen"></i> Éditer<span v-if="selection.length > 1"> ({{ selection.length }})</span>
            </button>
            <button v-if="deleteUrl" @click="confirmDelete" class="btn btn-error btn-sm" :disabled="!selection.length">
              <i class="fas fa-trash"></i> Supprimer<span v-if="selection.length"> ({{ selection.length }})</span>
            </button>
            <button @click="exportCsv" class="btn btn-outline btn-sm" :disabled="!filteredRows.length">
              <i class="fas fa-file-csv"></i> Export
            </button>
            <button @click="showColumnFilters = !showColumnFilters" data-testid="grid-column-filters"
                    :class="['btn btn-sm', showColumnFilters || activeColumnFilters ? 'btn-active' : 'btn-ghost']"
                    title="Filtrer colonne par colonne">
              <i class="fas fa-filter"></i> Filtres<span v-if="activeColumnFilters"> ({{ activeColumnFilters }})</span>
            </button>
            <button v-if="isCustomView" @click="resetView" class="btn btn-ghost btn-sm" data-testid="grid-reset-view"
                    title="Recherche, filtres, tri et pagination retrouvent leur état par défaut">
              <i class="fas fa-filter-circle-xmark"></i> Réinitialiser la vue
            </button>
            <button @click="fetchRows" class="btn btn-ghost btn-sm" title="Rafraîchir">
              <i class="fas fa-rotate"></i>
            </button>
            <!-- Actions propres à l'écran (nommer responsable, reset mot de
                 passe, import…) : la grille ne les connaît pas. -->
            <slot name="actions" :selection="selection" :rows="rows" :reload="fetchRows"></slot>
          </div>
        </div>

        <!-- Référence du tiroir de détail (#308) : il recouvre la droite de la
             zone défilante sans défiler avec elle, ni horizontalement ni
             verticalement, et ne peut pas monter sur la barre d'outils. -->
        <div class="relative lg:flex-1 lg:min-h-0">
        <div ref="scroller" class="lg:h-full lg:overflow-auto" data-testid="grid-scroller">

        <!-- Ouverture depuis une tuile du tableau de bord (#312) : on annonce
             d'où vient le filtre, sinon la grille paraît amputée sans raison.
             sticky left-0 ici et plus bas : ces blocs ne suivent pas le
             défilement horizontal d'une table large. -->
        <div v-if="focusIds.length" class="alert alert-info mb-3 py-2 sticky left-0">
          <i class="fas fa-filter"></i>
          <span>
            Affichage restreint à {{ focusIds.length }} {{ entityLabel }}(s)
            signalé(s) par les indicateurs.
          </span>
          <button class="btn btn-sm btn-ghost" @click="clearFocus">
            <i class="fas fa-xmark"></i> Voir tout
          </button>
        </div>

        <!-- Filtres propres à l'écran, au-dessus de la recherche -->
        <div v-if="$slots.filters" class="flex flex-wrap items-center gap-4 mb-3 sticky left-0">
          <slot name="filters" :rows="rows"></slot>
        </div>

        <!-- Le compteur est remonté à côté du titre, pour rester visible (#386). -->
        <div class="flex flex-wrap items-center gap-3 mb-3 sticky left-0">
          <input v-model.trim="search"
                 type="text"
                 class="input input-bordered input-sm w-full sm:w-96"
                 placeholder="Rechercher… (plusieurs termes séparés par des virgules)"/>
          <label class="flex items-center gap-2 text-sm ml-auto">
            <span>par page</span>
            <select v-model.number="pageSizeChoice" class="select select-bordered select-sm"
                    data-testid="grid-page-size">
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
              <!-- 0 = tout : une valeur stable, mémorisable (#311) -->
              <option :value="0">tout</option>
            </select>
          </label>
        </div>

        <div v-if="loading" class="flex justify-center py-10 sticky left-0">
          <span class="loading loading-spinner loading-lg text-primary"></span>
        </div>

        <div v-else-if="!rows.length" class="alert alert-info sticky left-0">
          <i class="fas fa-circle-info"></i>
          <span>Aucune donnée.</span>
        </div>

        <!-- Le thead colle en haut de la zone défilante, ligne des filtres de
             colonnes comprise. Pas de table-pin-rows : il rend chaque ligne
             collante à top 0, et la ligne des filtres recouvrirait les titres.
             Fond opaque : les lignes passent dessous. Pas de bordure entre ses
             lignes non plus : en border-collapse, la bordure appartient à la
             table et ne suit pas le thead collé, les lignes transparaissaient
             par cette fente. Le trait sous l'en-tête est une ombre, qui colle
             avec lui. -->
        <template v-else>
          <table class="table table-xs md:table-sm">
            <thead class="sticky top-0 z-10 bg-base-100 [&_tr]:border-b-0 shadow-[0_1px_0_0_oklch(var(--bc)/0.2)]"
                   data-testid="grid-thead">
            <tr>
              <th v-if="canSelect" class="w-8">
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
            <!-- Filtres par colonne (#310) : liste, plage de dates ou texte,
                 selon les valeurs de la colonne. -->
            <tr v-if="showColumnFilters" data-testid="grid-filter-row">
              <th v-if="canSelect"></th>
              <th v-for="col in columns" :key="'f-' + col.key" class="font-normal align-top">
                <select v-if="columnKinds[col.key] === 'select'"
                        :value="columnFilters[col.key] ?? ''"
                        @change="columnFilters[col.key] = $event.target.value"
                        :data-testid="'grid-filter-' + col.key"
                        class="select select-bordered select-xs w-full min-w-20">
                  <option value="">tous</option>
                  <option v-for="opt in columnOptions[col.key]" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <div v-else-if="columnKinds[col.key] === 'date'" class="flex flex-col gap-1 min-w-28">
                  <input type="date" class="input input-bordered input-xs" title="à partir du"
                         :data-testid="'grid-filter-' + col.key + '-from'"
                         :value="(columnFilters[col.key] || {}).from || ''"
                         @input="setDateFilter(col.key, 'from', $event.target.value)"/>
                  <input type="date" class="input input-bordered input-xs" title="jusqu'au"
                         :data-testid="'grid-filter-' + col.key + '-to'"
                         :value="(columnFilters[col.key] || {}).to || ''"
                         @input="setDateFilter(col.key, 'to', $event.target.value)"/>
                </div>
                <input v-else-if="columnKinds[col.key] === 'text'"
                       v-model.trim="columnFilters[col.key]"
                       :data-testid="'grid-filter-' + col.key"
                       type="text" placeholder="contient…"
                       class="input input-bordered input-xs w-full min-w-20"/>
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(row, i) in pageRows"
                :key="row[idField] ?? i"
                :class="[
                  canSelect && isSelected(row) ? 'bg-primary/10' : '',
                  detailConfig && isDetailed(row) ? 'bg-primary/10' : '',
                  rowClickable || detailConfig ? 'cursor-pointer hover' : '',
                ]"
                :role="detailConfig ? 'button' : null"
                :tabindex="detailConfig ? 0 : null"
                @click="onRowClick(row)"
                @keydown.enter.prevent="onRowClick(row)"
                @keydown.space.prevent="onRowClick(row)">
              <!-- Avec un tiroir, le clic sur la ligne l'ouvre : la case doit
                   donc rester cochable seule, cellule comprise — viser une
                   case de 16 px a la souris est deja assez ingrat. -->
              <td v-if="canSelect" @click.stop="toggle(row)"><input type="checkbox" class="checkbox checkbox-xs" :checked="isSelected(row)" @click.stop="toggle(row)"/></td>
              <td v-for="col in columns"
                  :key="col.key"
                  :class="col.align === 'right' ? 'text-right' : ''">
                <!-- Colonne d'icones : reprend les actioncolumn d'ExtJS.
                     Chaque lien declare son icone, son infobulle, sa cible et
                     sa couleur, toutes calculees depuis la ligne. -->
                <span v-if="col.links" class="flex gap-2" @click.stop>
                  <a v-for="(lnk, li) in col.links"
                     :key="li"
                     :href="lnk.href(row)"
                     :target="lnk.href(row).startsWith('mailto:') ? '_self' : '_blank'"
                     :title="lnk.title"
                     :class="['text-base hover:opacity-70', lnk.variant ? lnk.variant(row) : '']">
                    <i :class="lnk.icon"></i>
                  </a>
                </span>
                <!-- Colonne image : une vignette, chargee en differe (issue
                     #295). Le chemin est deja dans la reponse, l'affichage ne
                     coute donc rien en donnees ; seules les images elles-memes
                     sont recuperees, et uniquement celles a l'ecran. -->
                <img v-else-if="col.image"
                     :src="'/' + render(col, row)"
                     :alt="col.alt ? col.alt(row) : ''"
                     loading="lazy"
                     class="w-10 h-10 rounded-full object-cover bg-base-200"/>
                <span v-else-if="col.badge" :class="col.badge(row)">{{ render(col, row) }}</span>
                <template v-else>{{ render(col, row) }}</template>
              </td>
            </tr>
            </tbody>
          </table>
        </template>

        <div v-if="pageCount > 1" class="flex justify-center items-center gap-2 mt-4 sticky left-0">
          <button class="btn btn-sm" :disabled="page === 1" @click="page--">«</button>
          <span class="text-sm">page {{ page }} / {{ pageCount }}</span>
          <button class="btn btn-sm" :disabled="page === pageCount" @click="page++">»</button>
        </div>
        </div>

          <admin-detail-drawer v-if="detailRow"
                               :detail="detailConfig"
                               :row="detailRow"
                               :has-prev="hasPrevDetail"
                               :has-next="hasNextDetail"
                               :can-edit="Boolean(saveUrl)"
                               @close="detailId = null"
                               @prev="stepDetail(-1)"
                               @next="stepDetail(1)"
                               @edit="openEditFromDetail">
            <template #detail-actions="{ row }">
              <slot name="detail-actions" :row="row" :reload="fetchRows"></slot>
            </template>
          </admin-detail-drawer>
        </div>

        <!-- id-field doit descendre jusqu'au formulaire : c'est LUI qui poste
             l'identifiant. Sans cette liaison il retombait sur son defaut
             'id', et les quatre ecrans a identifiant non standard postaient un
             parametre que la methode PHP ne declare pas — 500 pour trois
             d'entre eux, duplication silencieuse pour le quatrieme (#299). -->
        <admin-edit-modal v-if="editing"
                          :title="title"
                          :fields="fields"
                          :record="editing"
                          :save-url="saveUrl"
                          :id-field="idField"
                          @close="editing = null"
                          @saved="onSaved"/>

        <admin-bulk-edit-modal v-if="bulkEditing"
                               :title="title"
                               :fields="fields"
                               :bulk-fields="bulkFields"
                               :rows="selectedRows"
                               :save-url="saveUrl"
                               :id-field="idField"
                               :entity-label="entityLabel"
                               :row-label="rowLabel"
                               @close="bulkEditing = false"
                               @saved="onBulkSaved"/>
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
            pageSize: DEFAULT_PAGE_SIZE,
            selection: [],
            editing: null,
            bulkEditing: false,
            /** Filtres par colonne (#310) : clé de colonne => critère */
            columnFilters: {},
            showColumnFilters: false,
            /**
             * Page mémorisée (#311), appliquée une fois les lignes chargées :
             * elle peut ne plus exister si le jeu de données a rétréci.
             */
            restoredPage: null,
            /**
             * Identifiant de la ligne ouverte dans le tiroir, et non la ligne
             * elle-meme : apres un rechargement la ligne est un autre objet,
             * et le tiroir doit suivre la donnee fraiche, pas garder une copie.
             */
            detailId: null,
        };
    },
    computed: {
        /**
         * Identifiants passés dans l'URL (`?ids=12,34`) — issue #312.
         *
         * C'est ainsi qu'une tuile du tableau de bord ouvre l'écran sur les
         * seules lignes qu'elle signale. La grille le lit ici plutôt que chaque
         * écran dans son coin : les 29 écrans en profitent sans rien déclarer.
         *
         * Comparaison en chaînes : un identifiant vient de l'URL, la ligne le
         * porte parfois en nombre selon l'endpoint.
         */
        focusIds() {
            const raw = this.$route && this.$route.query ? this.$route.query.ids : null;
            if (!raw) {
                return [];
            }
            return String(raw).split(',').map((id) => id.trim()).filter((id) => id.length);
        },
        filteredRows() {
            const focused = this.focusIds.length
                ? this.sortedRows.filter((r) => this.focusIds.includes(String(r[this.idField])))
                : this.sortedRows;
            const screened = this.rowFilter
                ? focused.filter((r) => this.rowFilter(r))
                : focused;
            // Filtres par colonne (#310), sur la valeur affichée
            const active = this.columns.filter((c) => this.columnKinds[c.key]
                && !isEmptyFilter(this.columnFilters[c.key]));
            const base = active.length
                ? screened.filter((row) => active.every((c) => matchesFilter(
                    this.columnKinds[c.key], this.displayed(c, row), this.columnFilters[c.key])))
                : screened;
            if (!this.search) {
                return base;
            }
            // Recherche multi-termes séparés par des virgules, comme la grille ExtJS
            // (#408) : sur tout le texte de la ligne, calculé une fois par
            // chargement. Un numéro de licence tel qu'imprimé (`013_…`) trouve
            // aussi la forme stockée, sans préfixe.
            const terms = this.search.split(',')
                .map((t) => t.trim().toLowerCase())
                .filter((t) => t.length)
                .flatMap((t) => (LICENCE_PREFIX.test(t) ? [t, t.replace(LICENCE_PREFIX, '')] : [t]));
            const index = this.searchIndex;
            return base.filter((row) => {
                const haystack = index.get(row) ?? '';
                return terms.some((t) => haystack.includes(t));
            });
        },
        /**
         * Texte cherchable de chaque ligne (#408) : TOUTES les données rendues
         * par le serveur, affichées ou non, plus les valeurs formatées des
         * colonnes. Sauf les identifiants et les chemins (`NOT_SEARCHED`), et
         * le HTML réduit à son texte (corps des emails). Recalculé quand les
         * lignes changent, pas à chaque frappe : sur 3 663 joueurs, une frappe
         * recalculait tout.
         */
        searchIndex() {
            const index = new WeakMap();
            const excluded = new Set(this.searchExclude);
            const text = (value) => {
                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }
                const s = String(value);
                return s.includes('<') && s.includes('>') ? s.replace(/<[^>]*>/g, ' ') : s;
            };
            for (const row of this.rows) {
                const parts = [];
                for (const [key, value] of Object.entries(row)) {
                    if (!NOT_SEARCHED.test(key) && !excluded.has(key)) {
                        parts.push(text(value));
                    }
                }
                for (const col of this.columns) {
                    if (col.format && !col.image) {
                        parts.push(text(this.render(col, row)));
                    }
                }
                index.set(row, parts.join(' ').toLowerCase());
            }
            return index;
        },
        sortedRows() {
            if (!this.sort.key) {
                return this.rows;
            }
            const key = this.sort.key;
            const dir = this.sort.asc ? 1 : -1;
            // Nombres, dates françaises puis texte : voir `compareCells.js`.
            return [...this.rows].sort((a, b) => compareCells(a[key], b[key]) * dir);
        },
        /**
         * Taille de page appliquée (#408) : le choix de l'utilisateur, sinon
         * « tout » jusqu'à `AUTO_ALL_ROWS` lignes et `AUTO_PAGE_SIZE` au-delà.
         * 0 = tout.
         */
        effectivePageSize() {
            if (this.pageSize !== null) {
                return this.pageSize;
            }
            return this.rows.length > AUTO_ALL_ROWS ? AUTO_PAGE_SIZE : 0;
        },
        /** Le sélecteur montre la taille appliquée ; le changer la fixe. */
        pageSizeChoice: {
            get() { return this.effectivePageSize; },
            set(value) { this.pageSize = value; },
        },
        pageCount() {
            if (!this.effectivePageSize) {
                return 1; // « tout »
            }
            return Math.max(1, Math.ceil(this.filteredRows.length / this.effectivePageSize));
        },
        pageRows() {
            if (!this.effectivePageSize) {
                return this.filteredRows;
            }
            const start = (this.page - 1) * this.effectivePageSize;
            return this.filteredRows.slice(start, start + this.effectivePageSize);
        },
        /**
         * Type de filtre de chaque colonne (#310), déduit des valeurs
         * affichées de TOUTES les lignes : une colonne ne change pas de type
         * au gré des autres filtres.
         */
        columnKinds() {
            const kinds = {};
            for (const col of this.columns) {
                kinds[col.key] = filterKind(col, this.rows.map((r) => this.displayed(col, r)));
            }
            return kinds;
        },
        columnOptions() {
            const options = {};
            for (const col of this.columns) {
                if (this.columnKinds[col.key] === 'select') {
                    options[col.key] = selectOptions(this.rows.map((r) => this.displayed(col, r)));
                }
            }
            return options;
        },
        activeColumnFilters() {
            return Object.keys(this.columnFilters)
                .filter((k) => this.columnKinds[k] && !isEmptyFilter(this.columnFilters[k])).length;
        },
        /** Ce que la grille mémorise d'une visite à l'autre (#311). */
        viewState() {
            return {
                search: this.search,
                sort: this.sort,
                pageSize: this.pageSize,
                // Distingue un 25 choisi de l'ancien défaut (voir restoreView).
                pageSizeChosen: this.pageSize !== null,
                page: this.page,
                columnFilters: this.columnFilters,
                showColumnFilters: this.showColumnFilters,
            };
        },
        storageKey() {
            return stateKey(this.$route, this.title);
        },
        isCustomView() {
            return Boolean(this.search || this.sort.key || this.pageSize !== DEFAULT_PAGE_SIZE
                || this.page !== 1 || this.activeColumnFilters || this.showColumnFilters);
        },
        selectedRows() {
            return this.rows.filter((r) => this.selection.includes(r[this.idField]));
        },
        canEdit() {
            return this.selection.length === 1 || (this.selection.length > 1 && this.bulkFields.length > 0);
        },
        canSelect() {
            return this.selectable !== null
                ? this.selectable
                : Boolean(this.saveUrl || this.deleteUrl);
        },

        allPageSelected() {
            return this.pageRows.length > 0 && this.pageRows.every((r) => this.isSelected(r));
        },

        /**
         * Normalise la prop `detail`. `detail` a `true` suffit a avoir un
         * tiroir utilisable : on reprend les colonnes de la grille, libelles
         * et `format` compris, en ecartant celles qui n'ont rien a dire hors
         * du tableau (vignette, colonne d'icones, colonne sans libelle).
         */
        detailConfig() {
            if (!this.detail) {
                return null;
            }
            const config = this.detail === true ? {} : { ...this.detail };
            if (!config.sections) {
                const fields = this.columns
                    .filter((c) => c.label && !c.image && !c.links)
                    .map((c) => ({ key: c.key, label: c.label, format: c.format }));
                config.sections = [{ title: '', fields }];
            }
            if (!config.title) {
                const first = this.columns.find((c) => c.label && !c.image && !c.links);
                config.title = (row) => (first ? String(row[first.key] ?? '') : '');
            }
            return config;
        },
        /** La ligne ouverte, relue dans les lignes visibles a chaque rendu. */
        detailRow() {
            if (!this.detailConfig || this.detailId === null) {
                return null;
            }
            return this.filteredRows.find((r) => r[this.idField] === this.detailId) || null;
        },
        detailIndex() {
            return this.detailRow
                ? this.filteredRows.findIndex((r) => r[this.idField] === this.detailId)
                : -1;
        },
        hasPrevDetail() {
            return this.detailIndex > 0;
        },
        hasNextDetail() {
            return this.detailIndex !== -1 && this.detailIndex < this.filteredRows.length - 1;
        },
    },
    watch: {
        // Filtrer VIDE la selection : sinon une action s'appliquerait a des
        // lignes devenues invisibles. Reperee en recette de #288 -- une
        // recherche avait masque la ligne selectionnee, et le bouton agissait
        // toujours sur elle. La pagination, elle, conserve la selection : la
        // suppression en masse sur plusieurs pages est un usage legitime.
        // Le tiroir se referme avec la selection : il porte le nom d'une ligne
        // qui vient peut-etre d'etre filtree hors de la liste, et un tiroir
        // ouvert sur une ligne invisible est un mensonge.
        search() { this.page = 1; this.selection = []; this.detailId = null; },
        rowFilter() { this.page = 1; this.selection = []; this.detailId = null; },
        // Même raison : les lignes visibles changent sous les pieds de la
        // sélection et du tiroir (#312).
        focusIds() { this.page = 1; this.selection = []; this.detailId = null; },
        // Filtres par colonne (#310) : même règle que la recherche.
        columnFilters: {
            deep: true,
            handler() { this.page = 1; this.selection = []; this.detailId = null; },
        },
        pageSize() { this.page = 1; },
        // La grille défile dans sa propre zone (#386) : sans cela, « » » en bas
        // de page ouvrirait la page suivante… par sa dernière ligne.
        page() {
            this.$refs.scroller?.scrollTo({ top: 0 });
            this.$refs.root?.scrollTo({ top: 0 });
        },
        viewState: {
            deep: true,
            handler(state) { saveState(this.storageKey, state); },
        },
        // Un écran peut faire varier son URL (fenêtre de chargement des
        // emails, filtre serveur…) : on recharge alors au lieu d'afficher
        // silencieusement les anciennes lignes.
        fetchUrl() { this.page = 1; this.fetchRows(); },
    },
    created() {
        this.restoreView();
        this.fetchRows();
    },
    methods: {
        render(col, row) {
            const value = row[col.key];
            return col.format ? col.format(value, row) : (value ?? '');
        },
        /** Valeur affichée, en texte : ce que filtrent les filtres par colonne. */
        displayed(col, row) {
            return String(this.render(col, row) ?? '').trim();
        },
        rowLabel(row) {
            const first = this.columns.find((c) => c.label && !c.image && !c.links);
            return row && first ? this.displayed(first, row) : '';
        },
        /**
         * Vue de la visite précédente (#311). Rien n'est restauré quand
         * l'écran est ouvert avec des paramètres explicites (`?ids=` d'une
         * tuile d'indicateur, #312) : l'URL fait foi.
         */
        restoreView() {
            if (hasExplicitQuery(this.$route)) {
                return;
            }
            const saved = loadState(this.storageKey);
            if (!saved) {
                return;
            }
            if (typeof saved.search === 'string') {
                this.search = saved.search;
            }
            if (saved.sort && typeof saved.sort === 'object' && 'key' in saved.sort) {
                this.sort = { key: saved.sort.key, asc: saved.sort.asc !== false };
            }
            // 25, l'ancien défaut, n'était le plus souvent pas un choix :
            // l'appliquer encore masquerait la taille automatique (#408).
            if ([0, 50, 100].includes(saved.pageSize)
                || (saved.pageSize === LEGACY_DEFAULT_PAGE_SIZE && saved.pageSizeChosen)) {
                this.pageSize = saved.pageSize;
            }
            if (saved.columnFilters && typeof saved.columnFilters === 'object') {
                this.columnFilters = saved.columnFilters;
            }
            this.showColumnFilters = Boolean(saved.showColumnFilters);
            this.restoredPage = Number(saved.page) > 1 ? Number(saved.page) : null;
        },
        /** « Réinitialiser la vue » : l'état d'une première visite. */
        resetView() {
            this.search = '';
            this.sort = { key: null, asc: true };
            this.pageSize = DEFAULT_PAGE_SIZE;
            this.page = 1;
            this.columnFilters = {};
            this.showColumnFilters = false;
            clearState(this.storageKey);
            this.$emit('reset-view');
        },
        setDateFilter(key, bound, value) {
            this.columnFilters = {
                ...this.columnFilters,
                [key]: { ...(this.columnFilters[key] || {}), [bound]: value },
            };
        },
        onEditClick() {
            if (this.selection.length > 1) {
                this.bulkEditing = true;
            } else {
                this.openEdit();
            }
        },
        onBulkSaved() {
            this.bulkEditing = false;
            this.fetchRows();
        },
        isSelected(row) {
            return this.selection.includes(row[this.idField]);
        },
        /**
         * Un clic sur la ligne coche la case quand la selection existe, et
         * previent l'ecran dans tous les cas : le journal des emails s'en sert
         * pour ouvrir le message, sans colonne de selection.
         */
        onRowClick(row) {
            // Avec un tiroir, le clic l'ouvre ; sans, il coche la case comme
            // il l'a toujours fait. Les deux a la fois selectionnerait une
            // ligne a chaque consultation, et la barre d'outils agirait sur
            // des lignes qu'on n'a fait que regarder.
            if (this.detailConfig) {
                this.detailId = row[this.idField];
            } else if (this.canSelect) {
                this.toggle(row);
            }
            this.$emit('row-click', row);
        },
        isDetailed(row) {
            return this.detailId !== null && row[this.idField] === this.detailId;
        },
        /** Retire `?ids=` de l'URL : la grille retrouve toutes ses lignes. */
        clearFocus() {
            const query = { ...this.$route.query };
            delete query.ids;
            this.$router.replace({ path: this.$route.path, query });
        },
        /**
         * Ligne precedente / suivante dans les lignes VISIBLES, filtre et tri
         * compris. On suit la pagination pour que la ligne mise en avant reste
         * a l'ecran derriere le tiroir.
         */
        stepDetail(delta) {
            // L'index est releve AVANT d'ouvrir la ligne suivante : une fois
            // `detailId` change, `detailIndex` designe deja la nouvelle ligne.
            const index = this.detailIndex + delta;
            const target = this.filteredRows[index];
            if (!target) {
                return;
            }
            this.detailId = target[this.idField];
            this.page = this.effectivePageSize ? Math.floor(index / this.effectivePageSize) + 1 : 1;
        },
        openEditFromDetail() {
            if (this.detailRow) {
                this.editing = { ...this.detailRow };
            }
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
            return axios.get(this.fetchUrl)
                .then(({ data }) => {
                    this.rows = Array.isArray(data) ? data : [];
                    // Page mémorisée (#311), ou page courante après un
                    // rechargement : si elle n'existe plus, la dernière page.
                    const wanted = this.restoredPage ?? this.page;
                    this.restoredPage = null;
                    this.$nextTick(() => { this.page = Math.min(Math.max(1, wanted), this.pageCount); });
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
            const requests = this.deleteMode === 'id'
                ? this.selection.map((id) => {
                    const one = new FormData();
                    one.append('id', id);
                    return () => axios.post(this.deleteUrl, one);
                })
                : [() => {
                    const all = new FormData();
                    all.append('ids', this.selection.join(','));
                    return axios.post(this.deleteUrl, all);
                }];
            // En mode unitaire on enchaîne les appels au lieu de les lancer en
            // parallèle : une erreur sur l'un ne doit pas laisser les autres
            // en vol, et l'ordre rend le message d'erreur lisible.
            requests
                .reduce(
                    (chain, run) => chain.then((last) => run().then((r) => r || last)),
                    Promise.resolve(null)
                )
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchRows();
                })
                .catch((error) => {
                    onError(this, error);
                    this.fetchRows();
                });
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
