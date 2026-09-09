import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des utilisateurs (issue #265, lot 1).
 * Remplace `js/view/user/{Grid,Edit}.js`.
 *
 * Au-delà du CRUD, la grille ExtJS portait trois actions : réinitialiser le mot
 * de passe, et rattacher le compte à des équipes ou à des clubs. Elles passent
 * par le slot `actions` de la grille générique.
 *
 * « Agir en tant que » y est rattaché aussi (lot 5) : l'admin ExtJS en faisait
 * une fenêtre à part, avec sa propre liste de comptes
 * (`usermanager/get_users_for_act_as`). Puisque cet écran liste déjà les
 * comptes, l'action se pose dessus — même capacité, un clic de moins, et une
 * liste de moins à maintenir.
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

        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-warning btn-sm"
                  :disabled="selection.length !== 1"
                  @click="resetPassword(selection[0], reload)">
            <i class="fas fa-key"></i> Réinitialiser le mot de passe
          </button>
          <button class="btn btn-outline btn-sm"
                  :disabled="selection.length !== 1 || isLoading"
                  @click="actAs(selection[0], rows)">
            <i class="fas fa-user-secret"></i> Agir en tant que
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
        /**
         * Bascule la session sur le compte cible. Les rôles d'origine sont
         * sauvegardés côté serveur (`original_admin_*`), la navbar publique
         * propose le retour (`switch_back_to_admin`).
         *
         * On repart vers la home : après la bascule la session n'est plus
         * administratrice, l'admin se refuserait à elle-même.
         */
        actAs(id, rows) {
            const compte = rows.find((r) => String(r.id) === String(id));
            const libelle = compte ? (compte.login || compte.email) : 'ce compte';
            if (!window.confirm(
                `Agir en tant que ${libelle} ?\n\n`
                + "Votre session prend son identité et ses droits. Le retour se fait "
                + "depuis le bandeau du site, avec « revenir à mon compte admin »."
            )) {
                return;
            }
            const formData = new FormData();
            formData.append('target_user_id', id);
            this.isLoading = true;
            axios.post('/rest/action.php/usermanager/switch_to_user', formData)
                .then((response) => {
                    onSuccess(this, response);
                    window.location.href = '/pages/home.html';
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
    },
};
