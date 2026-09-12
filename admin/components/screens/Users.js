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
        'admin-picker-modal': defineAsyncComponent(() => import('../grid/AdminPickerModal.js')),
    },
    template: `
      <admin-grid
        ref="grid"
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
                  @click="openLinks('teams', selection[0], rows)">
            <i class="fas fa-people-group"></i> Équipes liées…
          </button>
          <button class="btn btn-outline btn-sm"
                  :disabled="selection.length !== 1 || isLoading"
                  @click="openLinks('clubs', selection[0], rows)">
            <i class="fas fa-sitemap"></i> Clubs liés…
          </button>
          <button class="btn btn-outline btn-sm"
                  :disabled="selection.length !== 1 || isLoading"
                  @click="toggleAdmin(selection[0], rows, reload)">
            <i class="fas fa-user-shield"></i> {{ adminLabel(selection, rows) }}
          </button>
          <button class="btn btn-outline btn-sm"
                  :disabled="selection.length !== 1 || isLoading"
                  @click="actAs(selection[0], rows)">
            <i class="fas fa-user-secret"></i> Agir en tant que
          </button>
        </template>
      </admin-grid>

      <admin-picker-modal v-if="picker"
                          :title="picker.title"
                          :help="picker.help"
                          :items="picker.items"
                          :selected="picker.selected"
                          multiple
                          confirm-label="Enregistrer les liens"
                          :is-busy="isLoading"
                          @confirm="saveLinks"
                          @close="picker = null"></admin-picker-modal>
    `,
    data() {
        return {
            isLoading: false,
            teams: [],
            clubs: [],
            picker: null,
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
    created() {
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
        axios.get('/rest/action.php/club/get')
            .then(({ data }) => { this.clubs = data; })
            .catch(() => { this.clubs = []; });
    },
    methods: {
        /**
         * Rattachement du compte à des équipes ou à des clubs.
         *
         * Ce sont ces liens qui **portent les rôles** : une ligne dans
         * `users_teams` fait un responsable d'équipe, une ligne dans
         * `users_clubs` un responsable de club (issue #245). Les cocher change
         * donc les droits du compte, pas seulement un affichage.
         *
         * L'API renvoie les liens actuels séparément de la liste complète : on
         * charge les deux avant d'ouvrir la fenêtre, sinon les cases seraient
         * décochées et enregistrer détacherait tout.
         */
        openLinks(kind, id, rows) {
            const compte = rows.find((r) => String(r.id) === String(id));
            const libelle = compte ? (compte.login || compte.email) : 'ce compte';
            const url = kind === 'teams'
                ? '/rest/action.php/usermanager/getUserTeamIds'
                : '/rest/action.php/usermanager/getUserClubIds';
            this.isLoading = true;
            axios.get(url, { params: { user_id: id } })
                .then(({ data }) => {
                    // L'API renvoie un tableau plat d'entiers (`[1, 4]`), pas
                    // des objets : `array_column` est fait côté PHP.
                    const actuels = (data || [])
                        .map((row) => (row !== null && typeof row === 'object'
                            ? (kind === 'teams' ? row.team_id : row.club_id)
                            : row))
                        .filter((v) => v !== undefined && v !== null);
                    this.picker = {
                        kind,
                        userId: id,
                        title: kind === 'teams'
                            ? `Équipes de ${libelle}`
                            : `Clubs de ${libelle}`,
                        help: kind === 'teams'
                            ? "Une équipe cochée fait de ce compte un responsable d'équipe."
                            : 'Un club coché fait de ce compte un responsable de club.',
                        selected: actuels.map(String),
                        items: kind === 'teams'
                            ? this.teams.map((t) => ({
                                value: String(t.id_equipe),
                                label: t.nom_equipe,
                                hint: t.club,
                            }))
                            : this.clubs.map((c) => ({ value: String(c.id), label: c.nom })),
                    };
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
        saveLinks(values) {
            const { kind, userId } = this.picker;
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append(kind === 'teams' ? 'team_ids' : 'club_ids', values.join(','));
            this.isLoading = true;
            axios.post(
                kind === 'teams'
                    ? '/rest/action.php/usermanager/updateUserTeams'
                    : '/rest/action.php/usermanager/updateUserClubs',
                formData
            )
                .then((response) => {
                    onSuccess(this, response);
                    this.picker = null;
                    this.$refs.grid.fetchRows();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
        /**
         * Le compte sélectionné, ou null. Les identifiants transitent en
         * chaînes côté sélection et en entiers côté API, d'où la comparaison
         * sur `String`.
         */
        selectedRow(selection, rows) {
            if (selection.length !== 1) {
                return null;
            }
            return rows.find((r) => String(r.id) === String(selection[0])) || null;
        },
        /**
         * Le libellé dit ce que le clic va faire, pas l'état courant : sans
         * sélection il n'y a rien à annoncer, on garde le terme neutre.
         */
        adminLabel(selection, rows) {
            const compte = this.selectedRow(selection, rows);
            if (compte === null) {
                return 'Rôle administrateur';
            }
            return Number(compte.is_admin)
                ? 'Retirer le rôle administrateur'
                : 'Donner le rôle administrateur';
        },
        /**
         * Donne ou retire le rôle administrateur (issue #301).
         *
         * Troisième rôle éditable depuis cet écran, après les liens équipes et
         * clubs — et le plus lourd de conséquences, d'où la confirmation
         * nominative. Le refus de se rétrograder soi-même est posé côté
         * serveur : c'est là qu'il tient, la console suffirait à contourner un
         * contrôle fait ici.
         */
        toggleAdmin(id, rows, reload) {
            const compte = rows.find((r) => String(r.id) === String(id));
            const libelle = compte ? (compte.login || compte.email) : 'ce compte';
            const donner = !Number(compte ? compte.is_admin : 0);
            if (!window.confirm(
                donner
                    ? `Donner le rôle administrateur à ${libelle} ?\n\n`
                      + "Ce compte pourra tout modifier sur le site, y compris les "
                      + "droits des autres comptes."
                    : `Retirer le rôle administrateur à ${libelle} ?`
            )) {
                return;
            }
            const formData = new FormData();
            formData.append('user_id', id);
            formData.append('is_admin', donner ? 'true' : 'false');
            this.isLoading = true;
            axios.post('/rest/action.php/usermanager/setAdmin', formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
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
