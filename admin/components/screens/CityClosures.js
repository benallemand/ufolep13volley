import { defineAsyncComponent } from 'vue';

/**
 * Périodes interdites par ville (issue #265, lot 3).
 * Remplace `js/view/grid/BlacklistByCity.js` + `js/controller/manage_blacklist_by_city.js`.
 *
 * Écran créé pendant le COVID pour neutraliser une commune entière sur une
 * plage de dates. Repris à l'identique : c'est le seul écran de planification
 * qui porte une période (du / au) et non une date isolée.
 *
 * Les villes proposées sont celles des gymnases (`competition/get_city`), et
 * c'est bien le libellé qui est stocké, pas un identifiant.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Périodes interdites par ville"
        entity-label="période"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/competition/get_blacklist_by_city"
        save-url="/rest/action.php/competition/save_blacklist_by_city"
        delete-url="/rest/action.php/competition/delete_blacklist_by_city"/>
    `,
    data() {
        return {
            cities: [],
            columns: [
                { key: 'city', label: 'Ville' },
                { key: 'from_date', label: 'Du' },
                { key: 'to_date', label: 'Au' },
            ],
        };
    },
    computed: {
        fields() {
            return [
                {
                    name: 'city', label: 'Ville', type: 'select', required: true,
                    options: this.cities
                        .filter((c) => c.name)
                        .map((c) => ({ value: c.name, label: c.name })),
                },
                { name: 'from_date', label: 'Du', type: 'date', required: true },
                { name: 'to_date', label: 'Au', type: 'date', required: true },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/competition/get_city')
            .then(({ data }) => { this.cities = data; })
            .catch(() => { this.cities = []; });
    },
};
