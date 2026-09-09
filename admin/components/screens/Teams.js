import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des équipes (issue #265, lot 1).
 * Remplace `js/view/team/{Grid,Edit}.js`.
 *
 * Les listes déroulantes club et compétition sont chargées à l'ouverture : les
 * `combo` d'ExtJS s'alimentaient sur des stores, on fait pareil en amont pour
 * que la fenêtre d'édition soit purement déclarative.
 *
 * « Nommer responsable » n'avait pas été reprise au lot 1 — relevée en recette
 * par Benjamin (#288).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
        'admin-picker-modal': defineAsyncComponent(() => import('../grid/AdminPickerModal.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Gestion des équipes"
        entity-label="équipe"
        id-field="id_equipe"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/team/getTeams"
        save-url="/rest/action.php/team/saveTeam"
        delete-url="/rest/action.php/team/delete">

        <template #actions="{ selection, rows }">
          <button class="btn btn-sm btn-outline"
                  :disabled="selection.length !== 1 || isBusy"
                  @click="openLeaderPicker(selection[0], rows)">
            <i class="fas fa-user-tie"></i> Nommer responsable
          </button>
        </template>
      </admin-grid>

      <admin-picker-modal v-if="picker"
                          :title="picker.title"
                          :help="picker.help"
                          :items="picker.items"
                          confirm-label="Nommer responsable"
                          :is-busy="isBusy"
                          @confirm="setLeader"
                          @close="picker = null"></admin-picker-modal>
    `,
    data() {
        return {
            clubs: [],
            competitions: [],
            isBusy: false,
            picker: null,
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
    methods: {
        /**
         * Nomme le responsable de l'équipe.
         *
         * La liste proposée est celle des joueurs **du club de l'équipe**
         * (`get_players_by_team`), pas seulement ceux déjà dans l'équipe : on
         * peut nommer quelqu'un qui n'y était pas encore. Le drapeau
         * « déjà dans l'équipe » est indiqué en second niveau, et l'API trie
         * ces joueurs en tête.
         */
        openLeaderPicker(id, rows) {
            const equipe = rows.find((r) => String(r.id_equipe) === String(id));
            this.isBusy = true;
            axios.get('/rest/action.php/player/get_players_by_team', { params: { id_team: id } })
                .then(({ data }) => {
                    this.picker = {
                        teamId: id,
                        title: `Responsable de ${equipe ? equipe.nom_equipe : "l'équipe"}`,
                        help: "Joueurs du club de l'équipe. Le responsable actuel est remplacé.",
                        items: (data || []).map((p) => ({
                            value: p.id,
                            label: `${p.prenom} ${p.nom}`,
                            hint: Number(p.is_in_team) ? "déjà dans l'équipe" : 'autre équipe du club',
                        })),
                    };
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
        setLeader(values) {
            const formData = new FormData();
            formData.append('ids', values[0]);
            formData.append('id_team', this.picker.teamId);
            this.isBusy = true;
            axios.post('/rest/action.php/player/set_leader', formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.picker = null;
                    this.$refs.grid.fetchRows();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
