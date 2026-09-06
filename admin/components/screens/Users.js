import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des utilisateurs (issue #265, lot 1).
 * Remplace `js/view/user/{Grid,Edit}.js`.
 *
 * Au-delà du CRUD, la grille ExtJS portait trois actions : réinitialiser le mot
 * de passe, et rattacher le compte à des équipes ou à des clubs. Elles passent
 * par le slot `actions` de la grille générique.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des utilisateurs"
        entity-label="compte"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/usermanager/getUsers"
        save-url="/rest/action.php/usermanager/saveUser"
        delete-url="/rest/action.php/usermanager/deleteUsers">

        <template #actions="{ selection, reload }">
          <button class="btn btn-warning btn-sm"
                  :disabled="selection.length !== 1"
                  @click="resetPassword(selection[0], reload)">
            <i class="fas fa-key"></i> Réinitialiser le mot de passe
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            isLoading: false,
            columns: [
                { key: 'login', label: 'Login' },
                { key: 'email', label: 'Email' },
                { key: 'club_name', label: 'Club' },
                { key: 'team_name', label: 'Équipe' },
                { key: 'managed_club_names', label: 'Clubs gérés' },
                { key: 'is_admin', label: 'Admin', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
            fields: [
                { name: 'login', label: 'Login', help: 'Laisser vide à la création : l\'email sert de login (issue #247)' },
                { name: 'email', label: 'Email', type: 'email', required: true },
            ],
        };
    },
    methods: {
        resetPassword(id, reload) {
            if (!window.confirm('Réinitialiser le mot de passe de ce compte ? Un nouveau mot de passe lui sera envoyé par email.')) {
                return;
            }
            const formData = new FormData();
            formData.append('id', id);
            axios.post('/rest/action.php/usermanager/reset_password', formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error));
        },
    },
};
