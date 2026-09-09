import { defineAsyncComponent } from 'vue';

/**
 * Ententes entre clubs (issue #265, lot 3).
 * Remplace `js/view/grid/Friendships.js` + `js/controller/manage_friendships.js`.
 *
 * Deux clubs en entente partagent des joueurs : la contrainte évite de
 * programmer leurs matchs en même temps.
 *
 * Attention aux noms d'actions : la lecture, l'écriture et la suppression ne
 * passent pas par les conventions habituelles (`get`/`save`/`delete` de la
 * classe) mais par trois méthodes dédiées de `Competition`.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Ententes entre clubs"
        entity-label="entente"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/competition/get_friendships"
        save-url="/rest/action.php/competition/save_friendships"
        delete-url="/rest/action.php/competition/delete_friendships"/>
    `,
    data() {
        return {
            clubs: [],
            columns: [
                { key: 'nom_club_1', label: 'Club 1' },
                { key: 'nom_club_2', label: 'Club 2' },
            ],
        };
    },
    computed: {
        fields() {
            const options = this.clubs.map((c) => ({ value: c.id, label: c.nom }));
            return [
                { name: 'id_club_1', label: 'Club 1', type: 'select', required: true, options },
                { name: 'id_club_2', label: 'Club 2', type: 'select', required: true, options },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/club/get')
            .then(({ data }) => { this.clubs = data; })
            .catch(() => { this.clubs = []; });
    },
};
