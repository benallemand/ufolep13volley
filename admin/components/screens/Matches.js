import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des matchs (issue #265).
 * Remplace `js/view/match/{AdminGrid,Edit}.js`, l'écran le plus fourni de
 * l'admin ExtJS : colonnes d'icônes, six actions de barre d'outils, filtres
 * prédéfinis.
 *
 * Ce qui n'est PAS repris : la génération de matchs, faite par les scripts
 * Python du dépôt `ufolep13volley_python` (`calendar-agent/`).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Gestion des matchs"
        entity-label="match"
        id-field="id_match"
        :columns="columns"
        :fields="fields"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/matchmgr/getMatches"
        save-url="/rest/action.php/matchmgr/saveMatch"
        delete-url="/rest/action.php/matchmgr/delete">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <span>Vue</span>
            <select v-model="preset" class="select select-bordered select-sm">
              <option v-for="p in presets" :key="p.key" :value="p.key">{{ p.label }}</option>
            </select>
          </label>
        </template>

        <template #actions="{ selection, reload }">
          <button v-for="a in bulkActions"
                  :key="a.key"
                  class="btn btn-sm"
                  :class="a.variant"
                  :disabled="!selection.length"
                  @click="runBulk(a, selection, reload)">
            <i :class="a.icon"></i> {{ a.label }}
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            isLoading: false,
            preset: 'season',
            competitions: [],
            teams: [],
            gymnasiums: [],
            presets: [
                { key: 'season', label: 'Saison en cours' },
                { key: 'validation_ready', label: 'Prêts à valider' },
                { key: 'players_requested', label: 'Présents à renseigner' },
                { key: 'forbidden_player', label: 'Joueurs non valides' },
                { key: 'not_certified', label: 'Non certifiés' },
                { key: 'archived', label: 'Archivés' },
            ],
            bulkActions: [
                { key: 'archive', label: 'Archiver', icon: 'fas fa-box-archive', variant: 'btn-outline', url: '/rest/action.php/matchmgr/archiveMatch' },
                { key: 'confirm', label: 'Confirmer', icon: 'fas fa-check', variant: 'btn-success', url: '/rest/action.php/matchmgr/confirmMatch' },
                { key: 'unconfirm', label: 'Dé-confirmer', icon: 'fas fa-xmark', variant: 'btn-warning', url: '/rest/action.php/matchmgr/unconfirmMatch' },
                { key: 'certify', label: 'Certifier', icon: 'fas fa-stamp', variant: 'btn-outline', url: '/rest/action.php/matchmgr/certify_matchs' },
                { key: 'flip', label: 'Inverser', icon: 'fas fa-right-left', variant: 'btn-outline', url: '/rest/action.php/matchmgr/flip_matchs' },
            ],
        };
    },
    computed: {
        columns() {
            const ok = (cond) => (cond ? 'text-success' : 'text-error');
            return [
                {
                    key: '_links', label: 'Liens',
                    links: [
                        {
                            icon: 'fas fa-volleyball', title: 'Feuille de match',
                            href: (r) => `/match.html?id_match=${r.id_match}`,
                            variant: (r) => ok(r.match_status !== 'ARCHIVED' && Number(r.is_sign_match_dom) && Number(r.is_sign_match_ext)),
                        },
                        {
                            icon: 'fas fa-user', title: 'Fiches équipes',
                            href: (r) => `/team_sheets.html?id_match=${r.id_match}`,
                            variant: (r) => ok(r.match_status !== 'ARCHIVED' && Number(r.is_match_player_filled) && Number(r.is_sign_team_dom) && Number(r.is_sign_team_ext)),
                        },
                        {
                            icon: 'fas fa-square-poll-vertical', title: 'Sondages',
                            href: (r) => `/survey.html?id_match=${r.id_match}`,
                            variant: (r) => ok(r.match_status !== 'ARCHIVED' && Number(r.is_survey_filled_dom) && Number(r.is_survey_filled_ext)),
                        },
                        {
                            icon: 'fas fa-envelope', title: 'Écrire aux responsables',
                            href: (r) => `mailto:${r.email_dom || ''},${r.email_ext || ''}`,
                            variant: () => 'text-base-content/70',
                        },
                    ],
                },
                {
                    key: 'match_status', label: 'Statut',
                    format: (v) => ({ ARCHIVED: 'archivé', CONFIRMED: 'confirmé', NOT_CONFIRMED: 'à confirmer' }[v] || v || ''),
                    badge: (r) => 'badge badge-sm ' + ({
                        ARCHIVED: 'badge-ghost', CONFIRMED: 'badge-success', NOT_CONFIRMED: 'badge-warning',
                    }[r.match_status] || 'badge-ghost'),
                },
                { key: 'code_match', label: 'Code' },
                { key: 'code_competition', label: 'Comp' },
                { key: 'division', label: 'Div' },
                { key: 'equipe_dom', label: 'Domicile' },
                { key: 'resultat', label: 'Résultat' },
                { key: 'equipe_ext', label: 'Extérieur' },
                { key: 'date_reception', label: 'Date' },
                { key: 'date_original', label: 'Date initiale' },
                { key: 'heure_reception', label: 'Heure' },
                { key: 'gymnasium', label: 'Gymnase' },
                { key: 'note', label: 'Commentaire' },
            ];
        },
        fields() {
            const teamOptions = this.teams.map((t) => ({ value: t.id_equipe, label: t.nom_equipe }));
            return [
                { name: 'code_match', label: 'Code', required: true },
                {
                    name: 'code_competition', label: 'Compétition', type: 'select', required: true,
                    options: this.competitions.map((c) => ({ value: c.code_competition, label: c.libelle })),
                },
                { name: 'division', label: 'Division' },
                { name: 'id_equipe_dom', label: 'Domicile', type: 'select', options: teamOptions },
                { name: 'id_equipe_ext', label: 'Extérieur', type: 'select', options: teamOptions },
                {
                    name: 'id_gymnasium', label: 'Gymnase', type: 'select',
                    options: this.gymnasiums.map((g) => ({ value: g.id, label: g.full_name || g.nom })),
                },
                { name: 'date_reception', label: 'Date', type: 'date' },
                // Pas d'heure : `heure_reception` vient du créneau de l'équipe
                // recevante (jointure de `matchs_view`), la table `matches`
                // n'a pas cette colonne.
                { name: 'certif', label: 'Certifié ?', type: 'checkbox' },
                { name: 'is_sign_team_dom', label: 'Fiche équipe signée (dom) ?', type: 'checkbox' },
                { name: 'is_sign_team_ext', label: 'Fiche équipe signée (ext) ?', type: 'checkbox' },
                { name: 'is_sign_match_dom', label: 'Feuille de match signée (dom) ?', type: 'checkbox' },
                { name: 'is_sign_match_ext', label: 'Feuille de match signée (ext) ?', type: 'checkbox' },
                { name: 'note', label: 'Commentaire', type: 'textarea' },
            ];
        },
        /**
         * Vues prédéfinies, reprises des entrées de menu de la grille ExtJS.
         * Elles filtraient le store côté client sur les mêmes champs.
         */
        rowFilter() {
            const p = this.preset;
            if (p === 'archived') {
                return (r) => r.match_status === 'ARCHIVED';
            }
            const notArchived = (r) => r.match_status !== 'ARCHIVED';
            switch (p) {
                case 'validation_ready':
                    // `is_validation_ready` n'existe PAS dans la réponse de
                    // l'API : le modèle ExtJS le calculait côté client
                    // (js/model/Match.js, champ `convert`). On reprend sa règle.
                    return (r) => notArchived(r) && this.isValidationReady(r);
                case 'players_requested':
                    return (r) => notArchived(r) && Number(r.is_match_player_requested);
                case 'forbidden_player':
                    return (r) => notArchived(r) && Number(r.has_forbidden_player);
                case 'not_certified':
                    return (r) => notArchived(r) && !Number(r.certif);
                default:
                    return notArchived;
            }
        },
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; }).catch(() => {});
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; }).catch(() => {});
        axios.get('/rest/action.php/court/getGymnasiums')
            .then(({ data }) => { this.gymnasiums = data; }).catch(() => {});
    },
    methods: {
        /**
         * Un match est prêt à valider quand tout est signé et rempli, qu'aucun
         * joueur n'est en défaut, et qu'il n'est pas déjà certifié.
         * Règle reprise du champ calculé `is_validation_ready` du modèle ExtJS.
         */
        isValidationReady(r) {
            const on = (v) => Boolean(Number(v));
            return !on(r.certif)
                && on(r.is_sign_team_dom) && on(r.is_sign_team_ext)
                && on(r.is_sign_match_dom) && on(r.is_sign_match_ext)
                && on(r.is_survey_filled_dom) && on(r.is_survey_filled_ext)
                && on(r.is_match_player_filled)
                && !on(r.is_match_player_requested)
                && !on(r.has_forbidden_player)
                && !r.count_status
                && r.match_status === 'CONFIRMED';
        },
        runBulk(action, selection, reload) {
            if (!window.confirm(`${action.label} ${selection.length} match(s) ?`)) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', selection.join(','));
            axios.post(action.url, formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error));
        },
    },
};
