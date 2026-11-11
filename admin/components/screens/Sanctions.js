import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Sanctions (issue #475). Remplace l'écran « Pénalités automatiques » (#458).
 *
 * Un manquement au règlement est d'abord CONSTATÉ, automatiquement (feuille
 * de match non signée à 7 jours) ou par la commission (« Créer »), avec la
 * sanction envisagée : avertissement, points en moins, défaite par pénalité,
 * forfait, annulation du match, forfait général. Il n'a alors aucun effet.
 *
 * En réunion de fin de demi-saison, la commission coche les lignes et les
 * APPLIQUE (effet porté au classement ou aux matchs) ou les LÈVE (clémence).
 * « Remettre à décider » défait une décision, effet compris. Une sanction ne
 * se modifie ou ne se supprime qu'à décider.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Sanctions"
        entity-label="sanction"
        :columns="columns"
        :fields="fields"
        :selectable="true"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/sanction/getSanctions"
        save-url="/rest/action.php/sanction/saveSanction"
        delete-url="/rest/action.php/sanction/deleteSanctions">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="checkbox checkbox-sm" v-model="showDecided" data-testid="sanctions-show-decided">
            <span>Voir aussi les sanctions décidées (appliquées, levées)</span>
          </label>
        </template>

        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-error btn-sm" data-testid="sanctions-apply"
                  :disabled="!ids(selection, rows, ['CONSTATEE', 'LEVEE']).length || isBusy"
                  @click="decide('applySanctions', ids(selection, rows, ['CONSTATEE', 'LEVEE']), reload)">
            <i class="fas fa-gavel"></i> Appliquer<span
              v-if="ids(selection, rows, ['CONSTATEE', 'LEVEE']).length > 1"> ({{ ids(selection, rows, ['CONSTATEE', 'LEVEE']).length }})</span>
          </button>
          <button class="btn btn-success btn-sm" data-testid="sanctions-lift"
                  :disabled="!ids(selection, rows, ['CONSTATEE', 'APPLIQUEE']).length || isBusy"
                  @click="decide('liftSanctions', ids(selection, rows, ['CONSTATEE', 'APPLIQUEE']), reload)">
            <i class="fas fa-hand-holding-heart"></i> Lever (clémence)<span
              v-if="ids(selection, rows, ['CONSTATEE', 'APPLIQUEE']).length > 1"> ({{ ids(selection, rows, ['CONSTATEE', 'APPLIQUEE']).length }})</span>
          </button>
          <button class="btn btn-sm" data-testid="sanctions-reopen"
                  :disabled="!ids(selection, rows, ['APPLIQUEE', 'LEVEE']).length || isBusy"
                  @click="decide('reopenSanctions', ids(selection, rows, ['APPLIQUEE', 'LEVEE']), reload)">
            <i class="fas fa-rotate-left"></i> Remettre à décider
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            showDecided: false,
            isBusy: false,
            catalog: { motifs: [], types: [], teams: [] },
            columns: [
                { key: 'statut', label: 'Statut' },
                { key: 'constatee_le', label: 'Constatée le' },
                { key: 'origine', label: 'Origine' },
                { key: 'equipe', label: 'Équipe' },
                { key: 'competition', label: 'Compétition' },
                { key: 'division', label: 'Division' },
                { key: 'code_match', label: 'Match' },
                { key: 'motif_label', label: 'Motif' },
                { key: 'sanction_label', label: 'Sanction' },
                { key: 'comment', label: 'Commentaire' },
                { key: 'decidee_le', label: 'Décidée le' },
                { key: 'decision_comment', label: 'Décision' },
            ],
        };
    },
    computed: {
        fields() {
            return [
                { name: 'team_key', label: 'Équipe', type: 'select', required: true, options: this.catalog.teams },
                { name: 'motif', label: 'Motif', type: 'select', required: true, options: this.catalog.motifs },
                { name: 'sanction_type', label: 'Sanction envisagée', type: 'select', required: true,
                  options: this.catalog.types },
                { name: 'points', label: 'Points en moins', type: 'number', min: 0, max: 20,
                  help: 'Seulement pour « Points en moins ».' },
                { name: 'code_match', label: 'Match (code)', placeholder: 'M_2_20261103_174',
                  help: 'Obligatoire pour une défaite par pénalité, un forfait ou une annulation de match.' },
                { name: 'comment', label: 'Commentaire', type: 'textarea' },
            ];
        },
        rowFilter() {
            return this.showDecided ? null : (row) => row.status === 'CONSTATEE';
        },
    },
    created() {
        axios.get('/rest/action.php/sanction/getSanctionCatalog')
            .then(({ data }) => { this.catalog = data; })
            .catch((error) => onError(this, error));
    },
    methods: {
        /** Lignes cochées dont le statut permet l'action. */
        ids(selection, rows, statuses) {
            const allowed = new Set(rows.filter((r) => statuses.includes(r.status)).map((r) => String(r.id)));
            return selection.filter((id) => allowed.has(String(id)));
        },
        decide(action, ids, reload) {
            const labels = {
                applySanctions: 'Appliquer',
                liftSanctions: 'Lever',
                reopenSanctions: 'Remettre à décider',
            };
            const what = ids.length > 1 ? `ces ${ids.length} sanctions` : 'cette sanction';
            let comment = '';
            if (action === 'reopenSanctions') {
                if (!window.confirm(`Remettre à décider ${what} ? L'effet d'une sanction appliquée est annulé.`)) {
                    return;
                }
            } else {
                comment = window.prompt(`${labels[action]} ${what}. Commentaire de la décision (facultatif) :`, '');
                if (comment === null) {
                    return;
                }
            }
            const formData = new FormData();
            formData.append('ids', ids.join(','));
            if (action !== 'reopenSanctions') {
                formData.append('decision_comment', comment);
            }
            this.isBusy = true;
            axios.post(`/rest/action.php/sanction/${action}`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
