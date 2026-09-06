import { defineAsyncComponent } from 'vue';

/**
 * Gestion des équipes (issue #265, lot 1).
 * Remplace `js/view/team/{Grid,Edit}.js`.
 *
 * Les listes déroulantes club et compétition sont chargées à l'ouverture : les
 * `combo` d'ExtJS s'alimentaient sur des stores, on fait pareil en amont pour
 * que la fenêtre d'édition soit purement déclarative.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des équipes"
        entity-label="équipe"
        id-field="id_equipe"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/team/getTeams"
        save-url="/rest/action.php/team/saveTeam"
        delete-url="/rest/action.php/team/delete"/>
    `,
    data() {
        return {
            clubs: [],
            competitions: [],
            columns: [
                { key: 'nom_equipe', label: 'Équipe' },
                { key: 'club', label: 'Club' },
                { key: 'libelle_competition', label: 'Compétition' },
                { key: 'divisions', label: 'Division(s)' },
                { key: 'is_cup_registered', label: 'Coupe', format: (v) => (Number(v) ? 'oui' : 'non') },
                { key: 'is_active_team', label: 'Engagée', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
        };
    },
    computed: {
        fields() {
            return [
                { name: 'nom_equipe', label: 'Nom', required: true },
                {
                    name: 'id_club', label: 'Club', type: 'select', required: true,
                    options: this.clubs.map((c) => ({ value: c.id, label: c.nom })),
                },
                {
                    name: 'code_competition', label: 'Compétition', type: 'select', required: true,
                    options: this.competitions.map((c) => ({
                        value: c.code_competition, label: c.libelle,
                    })),
                },
                { name: 'is_cup_registered', label: 'Inscrite à la coupe ?', type: 'checkbox' },
            ];
        },
    },
    created() {
        axios.get('/rest/action.php/club/get')
            .then(({ data }) => { this.clubs = data; })
            .catch(() => { this.clubs = []; });
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
    },
};
