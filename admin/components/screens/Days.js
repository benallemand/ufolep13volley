import { defineAsyncComponent } from 'vue';

/**
 * Gestion des journées (issue #265, lot 2).
 * Remplace `js/view/day/{AdminGrid,Edit}.js`.
 *
 * L'action « générer les journées » n'est pas reprise : la génération se fait
 * par les scripts Python du dépôt `ufolep13volley_python` (`calendar-agent/`).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des journées"
        entity-label="journée"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/day/getDays"
        save-url="/rest/action.php/day/save_day"
        delete-url="/rest/action.php/day/delete"/>
    `,
    data() {
        return {
            competitions: [],
            columns: [
                { key: 'libelle_competition', label: 'Compétition' },
                { key: 'numero', label: 'Numéro', align: 'right' },
                { key: 'nommage', label: 'Nommage' },
                { key: 'libelle', label: 'Libellé' },
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
                { name: 'numero', label: 'Numéro', type: 'number', required: true },
                { name: 'nommage', label: 'Nommage' },
                { name: 'start_date', label: 'Premier jour de la semaine', placeholder: 'jj/mm/aaaa' },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
    },
};
