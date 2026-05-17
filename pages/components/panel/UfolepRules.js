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
        },
    },
    template: `
      <div class="container mx-auto p-4 space-y-6">
        <div class="bg-base-100 shadow-xl rounded-lg p-6">
          <h1 class="text-3xl font-bold text-center">Règlements UFOLEP 13</h1>
          <p class="mt-2 text-center text-base-content/80">
            Sélectionnez une compétition pour consulter son règlement détaillé.
          </p>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <button
              v-for="reg in regulations"
              :key="reg.id"
              type="button"
              class="card bg-base-100 shadow hover:shadow-lg transition focus:outline-none"
              :class="{'border-primary border-2': reg.id === selectedId}"
              @click="selectRegulation(reg.id)">
            <div class="card-body">
              <div class="flex items-center gap-3">
                <i :class="['fas', reg.icon, 'text-xl']"></i>
                <h2 class="card-title text-lg">{{ reg.label }}</h2>
              </div>
              <p class="text-sm text-base-content/70">{{ reg.description }}</p>
              <div class="mt-3">
                <span class="btn btn-sm" :class="reg.id === selectedId ? 'btn-primary' : 'btn-outline'">
                  {{ reg.id === selectedId ? 'Consulté' : 'Consulter' }}
                </span>
              </div>
            </div>
          </button>
        </div>
        <div class="bg-base-100 rounded-xl shadow-xl p-4">
          <component :is="selectedRegulation.component" />
        </div>
      </div>
    `,
};
