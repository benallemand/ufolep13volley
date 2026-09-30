/**
 * Mémoire de la vue d'un écran d'administration, entre deux visites (issue #311).
 *
 * Chaque retour sur un écran repartait de zéro : recherche vidée, tri par
 * défaut, page 1, 25 lignes par page. La grille mémorise donc sa vue par écran
 * dans `localStorage` — c'est une commodité propre à chaque navigateur, rien de
 * plus. `localStorage` peut échouer (navigation privée, données bloquées) :
 * lecture et écriture sont protégées, et l'écran s'affiche normalement sans
 * valeur mémorisée.
 */

const PREFIX = 'admin-grid:';

/** Clef de l'écran : le chemin de la route, stable d'une visite à l'autre. */
export function stateKey(route, fallback) {
    return PREFIX + (route && route.path ? route.path : fallback);
}

export function loadState(key) {
    try {
        const raw = window.localStorage.getItem(key);
        const value = raw ? JSON.parse(raw) : null;
        return value && typeof value === 'object' ? value : null;
    } catch (e) {
        return null;
    }
}

export function saveState(key, value) {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        // stockage indisponible : la vue ne sera simplement pas retrouvée
    }
}

export function clearState(key) {
    try {
        window.localStorage.removeItem(key);
    } catch (e) {
        // idem
    }
}

/**
 * Un écran ouvert avec des paramètres explicites (`?ids=` d'une tuile
 * d'indicateur, #312) ne restaure rien : l'URL fait foi.
 */
export function hasExplicitQuery(route) {
    return Boolean(route && route.query && Object.keys(route.query).length);
}

/**
 * Mixin pour les filtres propres à un écran (slot `filters` de la grille) :
 * mémorise les données nommées, et les remet à leur valeur de départ sur
 * l'événement `reset-view` de la grille.
 *
 *   mixins: [persistedFilters(['status'])],
 *   <admin-grid … @reset-view="resetPersistedFilters">
 */
export function persistedFilters(names) {
    return {
        data() {
            return { persistedFiltersDefaults: null };
        },
        computed: {
            persistedFiltersKey() {
                return stateKey(this.$route, this.$options.name || 'screen') + ':filters';
            },
        },
        created() {
            this.persistedFiltersDefaults = JSON.parse(JSON.stringify(
                Object.fromEntries(names.map((name) => [name, this[name]]))));
            const saved = hasExplicitQuery(this.$route) ? null : loadState(this.persistedFiltersKey);
            if (saved) {
                for (const name of names) {
                    if (name in saved) {
                        this[name] = saved[name];
                    }
                }
            }
            for (const name of names) {
                this.$watch(name, () => {
                    saveState(this.persistedFiltersKey,
                        Object.fromEntries(names.map((n) => [n, this[n]])));
                }, { deep: true });
            }
        },
        methods: {
            resetPersistedFilters() {
                const defaults = JSON.parse(JSON.stringify(this.persistedFiltersDefaults || {}));
                for (const name of names) {
                    this[name] = defaults[name];
                }
                clearState(this.persistedFiltersKey);
            },
        },
    };
}
