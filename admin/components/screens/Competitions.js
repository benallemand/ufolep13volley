import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des compétitions (issue #265, lot 2).
 * Remplace `js/view/grid/Competitions.js` + `js/view/window/Competition.js`.
 *
 * Les actions de GÉNÉRATION du menu ExtJS ne sont pas reprises : journées,
 * matchs et phases finales sont générés par les scripts Python du dépôt
 * `ufolep13volley_python` (`calendar-agent/`). Seul le CRUD est migré.
 *
 * L'initialisation de saison, elle, n'a rien à voir avec la génération et doit
 * rester accessible : c'est le seul point d'entrée de `Register::set_up_season`,
 * qui n'était joignable que par `matchmgr/generateAll` (issue #279).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des compétitions"
        entity-label="compétition"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/competition/getCompetitions"
        save-url="/rest/action.php/competition/saveCompetition"
        delete-url="/rest/action.php/competition/delete">

        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-sm btn-error btn-outline"
                  :disabled="!selection.length || isSettingUp"
                  @click="setUpSeason(selection, rows, reload)">
            <span v-if="isSettingUp" class="loading loading-spinner loading-xs"></span>
            <i v-else class="fas fa-rotate-right"></i>
            Initialiser la saison
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            isSettingUp: false,
            columns: [
                { key: 'code_competition', label: 'Code' },
                { key: 'libelle', label: 'Libellé' },
                { key: 'id_compet_maitre', label: 'Compétition maître' },
                { key: 'start_date', label: 'Début' },
                { key: 'start_register_date', label: 'Ouverture inscriptions' },
                { key: 'limit_register_date', label: 'Limite inscriptions' },
                { key: 'is_home_and_away', label: 'Aller-retour', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
            fields: [
                { name: 'code_competition', label: 'Code compétition', required: true },
                { name: 'libelle', label: 'Libellé', required: true },
                { name: 'id_compet_maitre', label: 'Code compétition maître' },
                { name: 'start_date', label: 'Date de début', placeholder: 'jj/mm/aaaa' },
                { name: 'start_register_date', label: "Ouverture des inscriptions", placeholder: 'jj/mm/aaaa' },
                { name: 'limit_register_date', label: "Date limite d'inscription", placeholder: 'jj/mm/aaaa' },
                { name: 'is_home_and_away', label: 'Matchs aller-retour ?', type: 'checkbox' },
            ],
        };
    },
    methods: {
        /**
         * Initialise la saison à partir des engagements : archive les matchs
         * en cours, recrée les équipes, les comptes responsables et les
         * créneaux, puis initialise les classements.
         *
         * L'opération est destructive (`Register::cleanup_before_start`
         * supprime les comptes responsables et les créneaux existants), d'où
         * la confirmation nominative.
         */
        setUpSeason(selection, rows, reload) {
            const labels = selection
                .map((id) => rows.find((r) => String(r.id) === String(id)))
                .map((row) => (row ? row.libelle || row.code_competition : '?'));
            const message = 'Initialiser la saison pour : ' + labels.join(', ') + ' ?\n\n'
                + 'Cette opération archive les matchs en cours, supprime les comptes '
                + 'responsables et les créneaux existants, puis les recrée depuis les '
                + 'engagements et réinitialise les classements.';
            if (!window.confirm(message)) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', selection.join(','));
            this.isSettingUp = true;
            axios.post('/rest/action.php/register/set_up_season', formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isSettingUp = false; });
        },
    },
};
