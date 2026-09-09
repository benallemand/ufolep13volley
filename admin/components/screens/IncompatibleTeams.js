import { defineAsyncComponent } from 'vue';

/**
 * Équipes qui ne peuvent pas jouer le même soir (issue #265, lot 3).
 * Remplace `js/view/grid/BlacklistTeams.js` + `js/view/window/BlacklistTeams.js`.
 *
 * Typiquement deux équipes d'un même club qui partagent des joueurs ou un
 * gymnase. La contrainte est lue par les scripts Python de génération
 * (`blacklist_teams`).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Équipes incompatibles"
        entity-label="contrainte"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/blacklistteams/getBlacklistTeams"
        save-url="/rest/action.php/blacklistteams/saveBlacklistTeams"
        delete-url="/rest/action.php/blacklistteams/delete"/>
    `,
    data() {
        return {
            teams: [],
            columns: [
                { key: 'libelle_equipe_1', label: 'Équipe 1' },
                { key: 'libelle_equipe_2', label: 'Équipe 2' },
            ],
        };
    },
    computed: {
        fields() {
            const options = this.teams.map((t) => ({
                value: t.id_equipe,
                label: t.team_full_name || t.nom_equipe,
            }));
            return [
                { name: 'id_team_1', label: 'Équipe 1', type: 'select', required: true, options },
                { name: 'id_team_2', label: 'Équipe 2', type: 'select', required: true, options },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
    },
};
