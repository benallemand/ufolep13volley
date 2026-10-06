import { defineAsyncComponent } from 'vue';
import { persistedFilters } from '../grid/gridState.js';
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
 * - `validateRegistration` / `refuseRegistration` / `unvalidateRegistration`
 *   sont **unitaires** (`id`), alors que `fill_ranks`, `create_teams_and_accounts` et `delete`
 *   prennent une liste (`ids`) — d'où deux façons d'enchaîner les appels ;
 * - l'édition passe par `register/register`, qui déclare **quinze paramètres
 *   obligatoires**. Le formulaire ne montre que ce qui se corrige à la main et
 *   transporte le reste en champs cachés, exactement comme le faisait le
 *   formulaire ExtJS.
 */
/**
 * Décisions de la commission sur une demande (issue #376) : statuts de départ
 * admis, endpoint, et libellés. Le serveur refait le contrôle (409).
 */
const DECISIONS = {
    validate: {from: ['PENDING', 'REFUSED'], action: 'validateRegistration', verb: 'Valider', done: 'validée(s)'},
    refuse: {from: ['PENDING'], action: 'refuseRegistration', verb: 'Refuser', done: 'refusée(s)'},
    unvalidate: {from: ['VALIDATED'], action: 'unvalidateRegistration', verb: 'Dévalider', done: 'remise(s) en attente'},
};

export default {
    // Filtres de l'écran mémorisés entre deux visites (issue #311)
    mixins: [persistedFilters(['status'])],
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        @reset-view="resetPersistedFilters"
        :bulk-fields="['division', 'is_paid']"
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
              <option value="REFUSED">refusées</option>
            </select>
          </label>
        </template>

        <!-- Chaque décision ne porte que sur les lignes sélectionnées dont le
             statut s'y prête (issue #376) : le bouton reste inactif sinon. -->
        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-sm btn-success" data-testid="registration-validate"
                  :disabled="!eligible(selection, rows, 'validate').length || isBusy"
                  title="Demandes en attente ou refusées"
                  @click="decide(selection, rows, 'validate', reload)">
            <i class="fas fa-check"></i> Valider
          </button>
          <button class="btn btn-sm btn-error" data-testid="registration-refuse"
                  :disabled="!eligible(selection, rows, 'refuse').length || isBusy"
                  title="Demandes en attente"
                  @click="decide(selection, rows, 'refuse', reload)">
            <i class="fas fa-ban"></i> Refuser
          </button>
          <button class="btn btn-sm btn-warning" data-testid="registration-unvalidate"
                  :disabled="!eligible(selection, rows, 'unvalidate').length || isBusy"
                  title="Demandes validées"
                  @click="decide(selection, rows, 'unvalidate', reload)">
            <i class="fas fa-rotate-left"></i> Dévalider
          </button>
          <!-- #388 : équipe classée → sa division et son rang ; toute autre →
               division X (« à placer » dans la réorganisation), rang suivant. -->
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  title="Équipe classée la saison passée : sa division et son rang. Autres : division X, à placer dans « Réorganiser les divisions »"
                  @click="allAtOnce(selection, 'fill_ranks', 'Remplir les divisions et les rangs', reload)">
            <i class="fas fa-list-ol"></i> Divisions / rangs
          </button>
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="allAtOnce(selection, 'create_teams_and_accounts', 'Créer les équipes et les comptes responsables', reload)">
            <i class="fas fa-user-plus"></i> Équipes / comptes
          </button>
          <!-- #409 : correction de l'alerte « Décalage des créneaux
               d'inscription », équipe par équipe, sans attendre
               l'initialisation de la saison. -->
          <button class="btn btn-sm btn-outline" data-testid="registration-apply-timeslots"
                  :disabled="!selection.length || isBusy"
                  title="Remplace les créneaux de l'équipe par ceux demandés à l'inscription"
                  @click="allAtOnce(selection, 'apply_registered_timeslots', 'Remplacer les créneaux des équipes par ceux de leur inscription', reload)">
            <i class="fas fa-clock-rotate-left"></i> Appliquer les créneaux demandés
          </button>
          <!-- #417 : récapitulatif unique à la comptabilité, à la place de la
               relance hebdomadaire des clubs. Aperçu : indicateur « Facture
               par club ». Indépendant de la sélection. -->
          <button class="btn btn-sm btn-outline" data-testid="registration-fees-accounting"
                  :disabled="isBusy"
                  title="Envoie à la comptabilité le montant attendu de chaque club (inscriptions validées en championnat)"
                  @click="sendFeesToAccounting()">
            <i class="fas fa-file-invoice-dollar"></i> Cotisations → comptabilité
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
                    format: (v, r) => ({
                        VALIDATED: `validée le ${r.validation_date || ''}`,
                        REFUSED: `refusée le ${r.refusal_date || ''}`,
                    })[v] || 'en attente',
                    badge: (r) => 'badge badge-sm ' + ({VALIDATED: 'badge-success', REFUSED: 'badge-error'}[r.status]
                        || 'badge-warning'),
                },
                { key: 'refusal_reason', label: 'Motif du refus' },
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
            return (r) => r.status === status;
        },
    },
    methods: {
        /** Lignes sélectionnées auxquelles la décision s'applique. */
        eligible(selection, rows, decision) {
            const statuses = DECISIONS[decision].from;
            return (rows || []).filter((r) => selection.includes(r.id) && statuses.includes(r.status));
        },
        /**
         * Endpoints unitaires : un appel par ligne, enchaînés. Les lignes dont
         * le statut ne s'y prête pas sont écartées, et l'écran le dit.
         */
        decide(selection, rows, decision, reload) {
            const {action, verb, done} = DECISIONS[decision];
            const targets = this.eligible(selection, rows, decision);
            const skipped = selection.length - targets.length;
            const question = `${verb} ${targets.length} inscription(s) ?`
                + (skipped ? `\n(${skipped} autre(s) sélectionnée(s) ignorée(s) : statut inadapté)` : '');
            let reason = null;
            if (decision === 'refuse') {
                reason = window.prompt(`${question}\n\nMotif du refus, envoyé au club par email :`);
                if (reason === null) {
                    return;
                }
                if (!reason.trim()) {
                    window.alert('Le motif du refus est obligatoire.');
                    return;
                }
            } else if (!window.confirm(question)) {
                return;
            }
            this.isBusy = true;
            targets
                .reduce((chain, row) => chain.then(() => {
                    const formData = new FormData();
                    formData.append('id', row.id);
                    if (reason !== null) {
                        formData.append('reason', reason.trim());
                    }
                    return axios.post(`/rest/action.php/register/${action}`, formData);
                }), Promise.resolve())
                .then(() => {
                    onSuccess(this, {data: {message: `${targets.length} inscription(s) ${done}.`}});
                    reload();
                })
                .catch((error) => {
                    onError(this, error);
                    reload();
                })
                .finally(() => { this.isBusy = false; });
        },
        /**
         * Récapitulatif des cotisations à la comptabilité (#417). Une seule
         * fois par saison : le serveur refuse un second envoi (409, avec la
         * date du premier), qu'on ne renvoie que sur confirmation explicite.
         */
        sendFeesToAccounting(resend = false) {
            if (!resend && !window.confirm(
                'Envoyer à la comptabilité le récapitulatif des cotisations des clubs ?\n'
                + '(Aperçu : indicateur « Facture par club »)')) {
                return;
            }
            const formData = new FormData();
            if (resend) {
                formData.append('resend', '1');
            }
            let retry = false;
            this.isBusy = true;
            axios.post('/rest/action.php/register/send_membership_fees_to_accounting', formData)
                .then((response) => onSuccess(this, response))
                .catch((error) => {
                    const message = error.response?.data?.message;
                    if (!resend && error.response?.status === 409) {
                        retry = window.confirm(`${message}\n\nLe renvoyer quand même ?`);
                        return;
                    }
                    onError(this, error);
                })
                .finally(() => {
                    this.isBusy = false;
                    if (retry) {
                        this.sendFeesToAccounting(true);
                    }
                });
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
