import { defineAsyncComponent } from 'vue';

/**
 * Indisponibilités d'équipe (issue #265, lot 3).
 * Remplace `js/view/grid/BlacklistTeam.js` + `js/view/window/BlacklistTeam.js`.
 *
 * Dates où une équipe donnée ne peut pas jouer. Comme pour les fermetures de
 * gymnase, un responsable de club saisit les siennes depuis son espace ; ici
 * l'administrateur voit et modifie toutes les équipes.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Indisponibilités d'équipe"
        entity-label="indisponibilité"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/blacklistteam/getBlacklistTeam"
        save-url="/rest/action.php/blacklistteam/saveBlacklistTeam"
        delete-url="/rest/action.php/blacklistteam/delete"/>
    `,
    data() {
        return {
            teams: [],
            columns: [
                { key: 'libelle_equipe', label: 'Équipe' },
                { key: 'closed_date', label: 'Date interdite' },
            ],
        };
    },
    computed: {
        fields() {
            return [
                {
                    name: 'id_team', label: 'Équipe', type: 'select', required: true,
                    options: this.teams.map((t) => ({
                        value: t.id_equipe,
                        label: t.team_full_name || t.nom_equipe,
                    })),
                },
                { name: 'closed_date', label: 'Date interdite', type: 'date', required: true },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
    },
};
