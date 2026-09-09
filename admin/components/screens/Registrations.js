import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Inscriptions / engagements (issue #265, lot 4).
 * Remplace `js/view/grid/register.js` + `js/view/register/Edit.js`
 * + `js/controller/manage_register.js`.
 *
 * Les demandes sont saisies par les responsables de club (#249) ; cet écran est
 * le poste de pilotage de l'administrateur : valider, compléter division et
 * rang, puis créer les équipes et les comptes.
 *
 * Deux subtilités de l'API :
 * - `validateRegistration` / `unvalidateRegistration` sont **unitaires**
 *   (`id`), alors que `fill_ranks`, `create_teams_and_accounts` et `delete`
 *   prennent une liste (`ids`) — d'où deux façons d'enchaîner les appels ;
 * - l'édition passe par `register/register`, qui déclare **quinze paramètres
 *   obligatoires**. Le formulaire ne montre que ce qui se corrige à la main et
 *   transporte le reste en champs cachés, exactement comme le faisait le
 *   formulaire ExtJS.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Inscriptions"
        entity-label="inscription"
        :columns="columns"
        :fields="fields"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/register/get_register"
        save-url="/rest/action.php/register/register"
        delete-url="/rest/action.php/register/delete">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <span>Statut</span>
            <select v-model="status" class="select select-bordered select-sm">
              <option value="">tous</option>
              <option value="PENDING">en attente</option>
              <option value="VALIDATED">validées</option>
            </select>
          </label>
        </template>

        <template #actions="{ selection, reload }">
          <button class="btn btn-sm btn-success"
                  :disabled="!selection.length || isBusy"
                  @click="eachOne(selection, 'validateRegistration', 'Valider', reload)">
            <i class="fas fa-check"></i> Valider
          </button>
          <button class="btn btn-sm btn-warning"
                  :disabled="!selection.length || isBusy"
                  @click="eachOne(selection, 'unvalidateRegistration', 'Dévalider', reload)">
            <i class="fas fa-xmark"></i> Dévalider
          </button>
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="allAtOnce(selection, 'fill_ranks', 'Remplir les divisions et les rangs', reload)">
            <i class="fas fa-list-ol"></i> Divisions / rangs
          </button>
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="allAtOnce(selection, 'create_teams_and_accounts', 'Créer les équipes et les comptes responsables', reload)">
            <i class="fas fa-user-plus"></i> Équipes / comptes
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            status: '',
            isBusy: false,
        };
    },
    computed: {
        columns() {
            return [
                { key: 'creation_date', label: 'Demandée le' },
                {
                    key: 'status', label: 'Statut',
                    format: (v, r) => (v === 'VALIDATED' ? `validée le ${r.validation_date || ''}` : 'en attente'),
                    badge: (r) => 'badge badge-sm ' + (r.status === 'VALIDATED' ? 'badge-success' : 'badge-warning'),
                },
                { key: 'competition', label: 'Compétition' },
                { key: 'club', label: 'Club' },
                { key: 'new_team_name', label: "Nom d'équipe" },
                { key: 'old_team', label: 'Ancien nom' },
                { key: 'division', label: 'Div' },
                { key: 'rank_start', label: 'Rang', align: 'right' },
                {
                    key: 'leader', label: 'Responsable',
                    format: (v, r) => `${r.leader_first_name || ''} ${r.leader_name || ''}`.trim(),
                },
                { key: 'leader_email', label: 'Email' },
                { key: 'leader_phone', label: 'Téléphone' },
                { key: 'court_1', label: 'Gymnase 1' },
                { key: 'day_court_1', label: 'Jour 1' },
                { key: 'hour_court_1', label: 'Heure 1' },
                { key: 'court_2', label: 'Gymnase 2' },
                { key: 'day_court_2', label: 'Jour 2' },
                { key: 'hour_court_2', label: 'Heure 2' },
                {
                    key: 'is_paid', label: 'Payée',
                    format: (v) => (Number(v) ? 'oui' : 'non'),
                },
                { key: 'remarks', label: 'Remarques' },
            ];
        },
        /**
         * Ce que l'administrateur corrige à la main, puis tout ce que
         * `Register::register()` exige sans que l'écran ait à l'afficher.
         */
        fields() {
            const caches = [
                'id_club', 'id_competition', 'old_team_id',
                'leader_name', 'leader_first_name', 'leader_email', 'leader_phone',
                'id_court_1', 'day_court_1', 'hour_court_1',
                'id_court_2', 'day_court_2', 'hour_court_2',
                'remarks',
            ];
            return [
                { name: 'new_team_name', label: "Nom d'équipe", required: true },
                { name: 'division', label: 'Division' },
                { name: 'rank_start', label: 'Rang de départ', type: 'number', min: 1 },
                { name: 'is_paid', label: 'Adhésion payée ?', type: 'checkbox' },
                ...caches.map((name) => ({ name, hidden: true })),
            ];
        },
        rowFilter() {
            const status = this.status;
            if (!status) {
                return null;
            }
            return status === 'VALIDATED'
                ? (r) => r.status === 'VALIDATED'
                : (r) => r.status !== 'VALIDATED';
        },
    },
    methods: {
        /** Endpoints unitaires : un appel par ligne, enchaînés. */
        eachOne(selection, action, label, reload) {
            if (!window.confirm(`${label} ${selection.length} inscription(s) ?`)) {
                return;
            }
            this.isBusy = true;
            selection
                .reduce((chain, id) => chain.then(() => {
                    const formData = new FormData();
                    formData.append('id', id);
                    return axios.post(`/rest/action.php/register/${action}`, formData);
                }), Promise.resolve())
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => {
                    onError(this, error);
                    reload();
                })
                .finally(() => { this.isBusy = false; });
        },
        /** Endpoints qui prennent la liste complète. */
        allAtOnce(selection, action, label, reload) {
            if (!window.confirm(`${label} pour ${selection.length} inscription(s) ?`)) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', selection.join(','));
            this.isBusy = true;
            axios.post(`/rest/action.php/register/${action}`, formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
