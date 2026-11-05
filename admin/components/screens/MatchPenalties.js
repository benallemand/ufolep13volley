import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Pénalités automatiques (issue #458).
 *
 * Le cron horaire retire 1 point aux deux équipes d'un match dont la feuille
 * n'est pas signée 7 jours après (#345, #456). Cet écran les liste et permet de
 * les annuler : la pénalité est marquée annulée, le point est rendu au
 * classement de l'équipe et l'annulation est écrite dans son historique.
 *
 * Une pénalité annulée reste dans la liste (case « Voir aussi les annulées ») :
 * sa ligne empêche le cron de pénaliser une seconde fois le même match. Elle
 * sort de l'indicateur « Pénalités automatiques » et des alertes des
 * responsables d'équipe.
 *
 * Le « -1 » du classement rend aussi un point, mais sans annuler la pénalité :
 * pour une pénalité automatique, passer par cet écran.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Pénalités automatiques"
        entity-label="pénalité"
        :columns="columns"
        :selectable="true"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/matchpenalty/getPenalties">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="checkbox checkbox-sm" v-model="showCancelled">
            <span>Voir aussi les annulées</span>
          </label>
        </template>

        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-warning btn-sm"
                  :disabled="!activeIds(selection, rows).length || isBusy"
                  @click="cancel(activeIds(selection, rows), reload)">
            <i class="fas fa-rotate-left"></i> Annuler la pénalité<span
              v-if="activeIds(selection, rows).length > 1"> ({{ activeIds(selection, rows).length }})</span>
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            showCancelled: false,
            isBusy: false,
            columns: [
                { key: 'statut', label: 'Statut' },
                { key: 'appliquee_le', label: 'Appliquée le' },
                { key: 'code_match', label: 'Match' },
                { key: 'date_match', label: 'Date du match' },
                { key: 'equipe', label: 'Équipe' },
                { key: 'competition', label: 'Compétition' },
                { key: 'division', label: 'Division' },
                { key: 'motif', label: 'Motif' },
                { key: 'annulee_le', label: 'Annulée le' },
            ],
        };
    },
    computed: {
        rowFilter() {
            return this.showCancelled ? null : (row) => !row.annulee_le;
        },
    },
    methods: {
        /** Lignes cochées encore actives : une pénalité annulée ne s'annule pas deux fois. */
        activeIds(selection, rows) {
            const active = new Set(rows.filter((r) => !r.annulee_le).map((r) => String(r.id)));
            return selection.filter((id) => active.has(String(id)));
        },
        cancel(ids, reload) {
            const message = ids.length > 1
                ? `Annuler ces ${ids.length} pénalités ? Chaque équipe récupère son point au classement.`
                : "Annuler cette pénalité ? L'équipe récupère son point au classement.";
            if (!window.confirm(message)) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', ids.join(','));
            this.isBusy = true;
            axios.post('/rest/action.php/matchpenalty/cancelPenalties', formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
