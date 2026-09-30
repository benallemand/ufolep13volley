import RulesDocument from './RulesDocument.js';

/**
 * Règlements UFOLEP 13 (issue #342) : la liste vient du dossier Google Drive
 * de la commission (`rules/getRulesList`), un règlement par document de la
 * saison la plus récente. Ajouter, retirer ou renommer un règlement se fait
 * dans le dossier, sans toucher au code.
 */
export default {
    components: {
        RulesDocument,
    },
    data() {
        return {
            loading: true,
            selectedId: 'general',
            regulations: [],
        };
    },
    computed: {
        selectedRegulation() {
            return this.regulations.find((reg) => reg.slug === this.selectedId) ?? this.regulations[0] ?? null;
        },
    },
    methods: {
        selectRegulation(slug) {
            this.selectedId = slug;
            // Faire défiler jusqu'au règlement affiché (utile surtout sur mobile)
            this.$nextTick(() => {
                const el = document.getElementById('reglement-content');
                if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        },
        scrollToList() {
            const el = document.getElementById('reglement-list');
            if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
        },
    },
    async created() {
        try {
            const {data} = await axios.get('/rest/action.php/rules/getRulesList');
            this.regulations = Array.isArray(data) ? data : [];
        } catch (error) {
            console.error('Erreur lors du chargement des règlements :', error);
            this.regulations = [];
        } finally {
            this.loading = false;
        }
    },
    template: `
      <div class="container mx-auto p-4 space-y-4">
        <div id="reglement-list" class="bg-base-100 shadow rounded-lg p-4 text-center scroll-mt-4">
          <h1 class="text-2xl sm:text-3xl font-bold">Règlements UFOLEP 13</h1>
          <p class="mt-1 text-sm text-base-content/70">Touchez un règlement pour l'afficher.</p>
        </div>

        <div v-if="loading" class="flex justify-center p-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="!regulations.length" role="alert" class="alert alert-warning" data-testid="rules-list-empty">
          <i class="fas fa-triangle-exclamation"></i>
          <span>Les règlements sont momentanément indisponibles. Merci de réessayer un peu plus tard, ou de
            contacter la commission.</span>
        </div>

        <template v-else>
          <!-- Liste des règlements -->
          <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" data-testid="rules-list">
            <button
                v-for="reg in regulations"
                :key="reg.slug"
                type="button"
                :data-slug="reg.slug"
                class="flex items-center gap-3 text-left w-full rounded-xl border p-3 transition hover:bg-base-200 focus:outline-none"
                :class="reg.slug === selectedRegulation.slug ? 'border-primary border-2 bg-primary/5' : 'border-base-300'"
                @click="selectRegulation(reg.slug)">
              <i :class="['fas', reg.icon, 'text-xl text-primary w-6 text-center shrink-0']"></i>
              <div class="flex-1 min-w-0">
                <div class="font-semibold leading-tight">{{ reg.label }}</div>
                <div class="text-xs text-base-content/60">{{ reg.description || 'Saison ' + reg.season }}</div>
              </div>
              <i class="fas shrink-0 text-base-content/40"
                 :class="reg.slug === selectedRegulation.slug ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
            </button>
          </div>

          <!-- Contenu du règlement sélectionné -->
          <div id="reglement-content" class="bg-base-100 rounded-xl shadow-xl p-4 scroll-mt-4">
            <div class="flex items-center justify-between gap-2 mb-2 border-b border-base-300 pb-2">
              <div class="flex items-center gap-2 font-bold text-primary min-w-0">
                <i :class="['fas', selectedRegulation.icon, 'shrink-0']"></i>
                <span class="truncate">{{ selectedRegulation.label }}</span>
              </div>
              <button type="button" class="btn btn-xs btn-ghost gap-1 shrink-0" @click="scrollToList">
                <i class="fas fa-arrow-up"></i> Changer
              </button>
            </div>
            <rules-document :key="selectedRegulation.slug" :slug="selectedRegulation.slug"/>
          </div>
        </template>
      </div>
    `,
};
