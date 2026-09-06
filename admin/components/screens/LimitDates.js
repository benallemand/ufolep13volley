import { defineAsyncComponent } from 'vue';

/**
 * Gestion des dates limites (issue #265, lot 2).
 * Remplace `js/view/limitdate/{Grid,Edit}.js`.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des dates limites"
        entity-label="date limite"
        id-field="id_date"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/limitdate/getLimitDates"
        save-url="/rest/action.php/limitdate/saveLimitDate"
        delete-url="/rest/action.php/limitdate/delete"/>
    `,
    data() {
        return {
            competitions: [],
            columns: [
                { key: 'libelle_competition', label: 'Compétition' },
                { key: 'date_limite', label: 'Date limite' },
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
                { name: 'date_limite', label: 'Date limite', required: true, placeholder: 'jj/mm/aaaa' },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
    },
};
