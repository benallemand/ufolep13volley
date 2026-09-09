import { defineAsyncComponent } from 'vue';

/**
 * Fermetures de gymnase (issue #265, lot 3).
 * Remplace `js/view/grid/BlacklistGymnase.js` + `js/view/window/BlacklistGymnase.js`.
 *
 * Un responsable de club saisit les fermetures de ses propres gymnases depuis
 * son espace ; cet écran est la vue d'ensemble de l'administrateur, sur tous
 * les gymnases. `saveBlacklistGymnase` porte lui-même le contrôle de
 * périmètre (`assertClubGymnasium`), qu'un admin traverse.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Fermetures de gymnase"
        entity-label="fermeture"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/blacklistcourt/getBlacklistGymnase"
        save-url="/rest/action.php/blacklistcourt/saveBlacklistGymnase"
        delete-url="/rest/action.php/blacklistcourt/delete"/>
    `,
    data() {
        return {
            gymnasiums: [],
            columns: [
                { key: 'libelle_gymnase', label: 'Gymnase' },
                { key: 'closed_date', label: 'Date de fermeture' },
            ],
        };
    },
    computed: {
        fields() {
            return [
                {
                    name: 'id_gymnase', label: 'Gymnase', type: 'select', required: true,
                    options: this.gymnasiums.map((g) => ({
                        value: g.id,
                        label: g.full_name || g.nom,
                    })),
                },
                { name: 'closed_date', label: 'Date de fermeture', type: 'date', required: true },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/court/getGymnasiums')
            .then(({ data }) => { this.gymnasiums = data; })
            .catch(() => { this.gymnasiums = []; });
    },
};
