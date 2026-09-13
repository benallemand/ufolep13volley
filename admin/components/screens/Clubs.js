import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des clubs (issue #265, lot 1).
 * Remplace `js/view/club/{Grid,Edit}.js`.
 *
 * L'action « Créer le compte du club » est arrivée avec #326 : le référent d'un
 * club, c'est son compte (`users_clubs`), et 18 clubs engagés sur 35 n'en
 * avaient pas. L'indicateur « Clubs engagés sans compte de club » ouvre cet
 * écran filtré sur eux ; l'action les traite un par un.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
        'admin-picker-modal': defineAsyncComponent(() => import('../grid/AdminPickerModal.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Gestion des clubs"
        entity-label="club"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/club/get"
        save-url="/rest/action.php/club/saveClub"
        delete-url="/rest/action.php/club/deleteClubs">

        <template #actions="{ selection, rows }">
          <button class="btn btn-sm btn-outline"
                  :disabled="selection.length !== 1 || isBusy"
                  @click="openAccountPicker(selection[0], rows)">
            <i class="fas fa-user-plus"></i> Créer le compte du club
          </button>
        </template>
      </admin-grid>

      <admin-picker-modal v-if="picker"
                          :title="picker.title"
                          :help="picker.help"
                          :items="picker.items"
                          confirm-label="Créer le compte"
                          :is-busy="isBusy"
                          @confirm="createAccount"
                          @close="picker = null"></admin-picker-modal>
    `,
    data() {
        return {
            isBusy: false,
            picker: null,
            columns: [
                { key: 'nom', label: 'Nom' },
                { key: 'affiliation_number', label: "N° d'affiliation" },
                // Le référent du club, c'est son compte — les cinq colonnes de
                // coordonnées libres ont été retirées par #327.
                { key: 'comptes', label: 'Compte(s)' },
                { key: 'referents', label: 'Référent(s)' },
            ],
            fields: [
                { name: 'nom', label: 'Nom', required: true },
                { name: 'affiliation_number', label: "Numéro d'affiliation", required: true },
            ],
        };
    },
    methods: {
        /**
         * Les adresses proposées viennent des coordonnées du club et de ses
         * personnes (`club/getAccountCandidates`) : le rattrapage se fait sans
         * ressaisir une adresse, donc sans la saisir de travers.
         */
        openAccountPicker(id, rows) {
            const club = rows.find((r) => String(r.id) === String(id));
            this.isBusy = true;
            axios.get('/rest/action.php/club/getAccountCandidates', { params: { id_club: id } })
                .then(({ data }) => {
                    const items = (data || []).map((c) => ({
                        value: c.email,
                        label: c.email,
                        hint: [c.label, c.origine].filter(Boolean).join(' — '),
                    }));
                    if (items.length === 0) {
                        onError(this, new Error(
                            "Aucune adresse connue pour ce club : renseignez-en une sur une personne du club, puis recommencez."
                        ));
                        return;
                    }
                    this.picker = {
                        clubId: id,
                        title: `Compte de ${club ? club.nom : 'ce club'}`,
                        help: "Les identifiants partent immédiatement à cette adresse. "
                            + "Si un compte l'utilise déjà, il est simplement rattaché au club.",
                        items,
                    };
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
        createAccount(values) {
            const formData = new FormData();
            formData.append('id_club', this.picker.clubId);
            formData.append('email', values[0]);
            this.isBusy = true;
            axios.post('/rest/action.php/club/createClubAccount', formData)
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
