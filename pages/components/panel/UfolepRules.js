import GeneralRules from './GeneralRules.js';
import ReglementChampionnatMixte from './ReglementChampionnatMixte.js';
import ReglementCoupeFeminine6x6 from './ReglementCoupeFeminine6x6.js';
import ReglementFeminin from './ReglementFeminin.js';
import ReglementMasculin from './ReglementMasculin.js';
import ReglementIsoardi from './ReglementIsoardi.js';
import ReglementKouryHanna from './ReglementKouryHanna.js';

export default {
    components: {
        GeneralRules,
        ReglementChampionnatMixte,
        ReglementCoupeFeminine6x6,
        ReglementFeminin,
        ReglementMasculin,
        ReglementIsoardi,
        ReglementKouryHanna,
    },
    data() {
        return {
            selectedId: 'general',
            regulations: [
                {
                    id: 'general',
                    label: 'Règlement général',
                    description: 'Cadre commun UFOLEP 13 (organisation, arbitrage, licences, etc.).',
                    component: 'GeneralRules',
                    icon: 'fa-scale-balanced',
                },
                {
                    id: 'feminine',
                    label: 'Championnat féminin 4×4',
                    description: 'Inscription, organisation et règles spécifiques des divisions féminines.',
                    component: 'ReglementFeminin',
                    icon: 'fa-venus',
                },
                {
                    id: 'masculine',
                    label: 'Championnat masculin 6×6',
                    description: 'Organisation des divisions masculines et règles associées.',
                    component: 'ReglementMasculin',
                    icon: 'fa-mars',
                },
                {
                    id: 'mixte',
                    label: 'Championnat mixte 4×4',
                    description: 'Format mixte obligatoire avec règles de rotation et organisation dédiées.',
                    component: 'ReglementChampionnatMixte',
                    icon: 'fa-people-arrows',
                },
                {
                    id: 'coupe-feminine',
                    label: 'Coupe féminine 6×6',
                    description: 'Format coupe pour équipes féminines (poules et phases finales).',
                    component: 'ReglementCoupeFeminine6x6',
                    icon: 'fa-trophy',
                },
                {
                    id: 'koury-hanna',
                    label: 'Coupe mixte Koury Hanna',
                    description: 'Coupe 4×4 mixte avec règles spécifiques de composition et rotation.',
                    component: 'ReglementKouryHanna',
                    icon: 'fa-heart',
                },
                {
                    id: 'isoardi',
                    label: 'Coupe masculine Isoardi',
                    description: 'Coupe 6×6 avec handicaps selon les divisions et phases de poules/élimination.',
                    component: 'ReglementIsoardi',
                    icon: 'fa-medal',
                },
            ],
        };
    },
    computed: {
        selectedRegulation() {
            return this.regulations.find((reg) => reg.id === this.selectedId) ?? this.regulations[0];
        },
    },
    methods: {
        selectRegulation(id) {
            this.selectedId = id;
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
        // Les renvois "#article-X" du récapitulatif ne doivent pas passer par le routeur
        // (mode hash) : on intercepte le clic et on défile jusqu'à l'article.
        handleArticleClick(e) {
            const link = e.target.closest('a[href^="#"]');
            if (!link) return;
            const id = link.getAttribute('href').slice(1);
            if (!id) return;
            const target = document.getElementById(id);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        },
    },
    template: `
      <div class="container mx-auto p-4 space-y-4">
        <div id="reglement-list" class="bg-base-100 shadow rounded-lg p-4 text-center scroll-mt-4">
          <h1 class="text-2xl sm:text-3xl font-bold">Règlements UFOLEP 13</h1>
          <p class="mt-1 text-sm text-base-content/70">Touchez un règlement pour l'afficher.</p>
        </div>

        <!-- Liste des règlements -->
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <button
              v-for="reg in regulations"
              :key="reg.id"
              type="button"
              class="flex items-center gap-3 text-left w-full rounded-xl border p-3 transition hover:bg-base-200 focus:outline-none"
              :class="reg.id === selectedId ? 'border-primary border-2 bg-primary/5' : 'border-base-300'"
              @click="selectRegulation(reg.id)">
            <i :class="['fas', reg.icon, 'text-xl text-primary w-6 text-center shrink-0']"></i>
            <div class="flex-1 min-w-0">
              <div class="font-semibold leading-tight">{{ reg.label }}</div>
              <div class="text-xs text-base-content/60">{{ reg.description }}</div>
            </div>
            <i class="fas shrink-0 text-base-content/40"
               :class="reg.id === selectedId ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
          </button>
        </div>

        <!-- Contenu du règlement sélectionné -->
        <div id="reglement-content" class="bg-base-100 rounded-xl shadow-xl p-4 scroll-mt-4" @click="handleArticleClick">
          <div class="flex items-center justify-between gap-2 mb-2 border-b border-base-300 pb-2">
            <div class="flex items-center gap-2 font-bold text-primary min-w-0">
              <i :class="['fas', selectedRegulation.icon, 'shrink-0']"></i>
              <span class="truncate">{{ selectedRegulation.label }}</span>
            </div>
            <button type="button" class="btn btn-xs btn-ghost gap-1 shrink-0" @click="scrollToList">
              <i class="fas fa-arrow-up"></i> Changer
            </button>
          </div>
          <component :is="selectedRegulation.component" />
        </div>
      </div>
    `,
};
