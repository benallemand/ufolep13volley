import { defineAsyncComponent } from 'vue';

/**
 * Gestion des divisions et poules (issue #265, lot 2).
 * Remplace `js/view/rank/{AdminGrid,Edit}.js`.
 *
 * La réorganisation par glisser-déposer (`js/view/rank/DragDropPanel.js`,
 * issue #189) n'est pas reprise ici : c'est un écran à part entière, pas une
 * grille. Elle reste dans l'ancienne administration.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des divisions et poules"
        entity-label="engagement"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/rank/getRanks"
        save-url="/rest/action.php/rank/saveRank"
        delete-url="/rest/action.php/rank/delete"/>
    `,
    data() {
        return {
            competitions: [],
            teams: [],
            columns: [
                { key: 'nom_equipe', label: 'Équipe' },
                { key: 'nom_competition', label: 'Compétition' },
                { key: 'division', label: 'Division' },
                { key: 'rank_start', label: 'Classement initial', align: 'right' },
                { key: 'will_register_again', label: 'Se réengage', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
        };
    },
    computed: {
        fields() {
            return [
                {
                    name: 'code_competition', label: 'Compétition', type: 'select', required: true,
                    options: this.competitions.map((c) => ({ value: c.code_competition, label: c.libelle })),
                },
                { name: 'division', label: 'Division', required: true },
                {
                    name: 'id_equipe', label: 'Équipe', type: 'select', required: true,
                    options: this.teams.map((t) => ({ value: t.id_equipe, label: t.nom_equipe })),
                },
                { name: 'rank_start', label: 'Classement au départ', type: 'number' },
                { name: 'will_register_again', label: "Se réengage l'an prochain ?", type: 'checkbox' },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
    },
};
