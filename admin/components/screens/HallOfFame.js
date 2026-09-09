import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Palmarès (issue #265, lot 5).
 * Remplace `js/view/grid/HallOfFame.js` + `js/view/window/HallOfFame.js`
 * + `js/controller/download_diploma.js`, et l'entrée « Palmarès… » du menu
 * *Générer* de la grille des compétitions.
 *
 * Trois choses en plus du CRUD :
 * - la **génération depuis les matchs**, qui déduit les vainqueurs d'une
 *   compétition sur une plage de dates (elle était accrochée à l'écran des
 *   compétitions, elle est ici, avec le palmarès qu'elle alimente) ;
 * - le **téléchargement des diplômes**, un PDF paysage pour les lignes
 *   sélectionnées ;
 * - un filtre par période, la grille ExtJS étant groupée par saison.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Palmarès"
        entity-label="titre"
        :columns="columns"
        :fields="fields"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/halloffame/getHallOfFame"
        save-url="/rest/action.php/halloffame/saveHallOfFame"
        delete-url="/rest/action.php/halloffame/delete">

        <template #filters="{ rows }">
          <label class="flex items-center gap-2 text-sm">
            <span>Période</span>
            <select v-model="period" class="select select-bordered select-sm">
              <option value="">toutes</option>
              <option v-for="p in periodsOf(rows)" :key="p" :value="p">{{ p }}</option>
            </select>
          </label>
        </template>

        <template #actions="{ selection }">
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length"
                  @click="downloadDiplomas(selection)">
            <i class="fas fa-award"></i> Diplômes
          </button>
          <button class="btn btn-sm btn-outline" @click="openGenerate">
            <i class="fas fa-wand-magic-sparkles"></i> Générer depuis les matchs
          </button>
        </template>
      </admin-grid>

      <dialog v-if="generating" class="modal modal-open">
        <div class="modal-box">
          <h3 class="font-bold text-lg mb-4">Générer le palmarès depuis les matchs</h3>
          <form @submit.prevent="generate" class="space-y-3">
            <div class="form-control">
              <label class="label py-1"><span class="label-text">Compétition <span class="text-error">*</span></span></label>
              <select v-model="generateForm.code_competition" class="select select-bordered" required>
                <option value="">—</option>
                <option v-for="c in competitions" :key="c.code_competition" :value="c.code_competition">
                  {{ c.libelle }}
                </option>
              </select>
            </div>
            <div class="form-control">
              <label class="label py-1"><span class="label-text">Date de début <span class="text-error">*</span></span></label>
              <input v-model="generateForm.date_debut" type="date" class="input input-bordered" required/>
            </div>
            <div class="form-control">
              <label class="label py-1"><span class="label-text">Date de fin <span class="text-error">*</span></span></label>
              <input v-model="generateForm.date_fin" type="date" class="input input-bordered" required/>
            </div>
            <div class="form-control">
              <label class="label py-1"><span class="label-text">Période <span class="text-error">*</span></span></label>
              <input v-model="generateForm.period" type="text" class="input input-bordered"
                     placeholder="2026-2027" pattern="\\d{4}-\\d{4}" required/>
            </div>
            <div class="form-control">
              <label class="label py-1"><span class="label-text">Type <span class="text-error">*</span></span></label>
              <select v-model="generateForm.title_season" class="select select-bordered" required>
                <option value="mi-saison">Mi-saison</option>
                <option value="Dept.">Départemental</option>
              </select>
            </div>
            <div class="modal-action">
              <button type="button" class="btn btn-ghost" @click="generating = false">Annuler</button>
              <button type="submit" class="btn btn-primary" :disabled="isBusy">
                <span v-if="isBusy" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-wand-magic-sparkles"></i>
                Générer
              </button>
            </div>
          </form>
        </div>
        <div class="modal-backdrop" @click="generating = false"></div>
      </dialog>
    `,
    data() {
        return {
            period: '',
            generating: false,
            isBusy: false,
            competitions: [],
            generateForm: {
                code_competition: '',
                date_debut: '',
                date_fin: '',
                period: '',
                title_season: 'mi-saison',
            },
            columns: [
                { key: 'period', label: 'Période' },
                { key: 'league', label: 'Catégorie' },
                { key: 'title', label: 'Titre' },
                { key: 'team_name', label: 'Équipe' },
            ],
            fields: [
                { name: 'title', label: 'Titre', required: true },
                { name: 'team_name', label: 'Équipe', required: true },
                { name: 'period', label: 'Période', required: true, placeholder: '2026-2027' },
                { name: 'league', label: 'Catégorie', required: true },
            ],
        };
    },
    computed: {
        rowFilter() {
            const period = this.period;
            return period ? (r) => r.period === period : null;
        },
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
    },
    methods: {
        periodsOf(rows) {
            return [...new Set(rows.map((r) => r.period).filter(Boolean))].sort().reverse();
        },
        /**
         * Le PDF est produit par l'endpoint lui-même : on ouvre l'URL au lieu
         * de passer par axios, comme le faisait le contrôleur ExtJS.
         */
        downloadDiplomas(selection) {
            const url = `/rest/action.php/halloffame/download_diploma?ids=${selection.join(',')}`;
            window.open(url, '_blank');
        },
        openGenerate() {
            this.generateForm.period = '';
            this.generating = true;
        },
        generate() {
            const formData = new FormData();
            for (const [k, v] of Object.entries(this.generateForm)) {
                formData.append(k, v);
            }
            this.isBusy = true;
            axios.post('/rest/action.php/halloffame/generateHallOfFameFromMatches', formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.generating = false;
                    // La génération insère dans `hall_of_fame` : on recharge la
                    // grille, qui est un composant enfant référencé.
                    this.$refs.grid.fetchRows();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
