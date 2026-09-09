import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Gestion des joueurs (issue #265 lot 1, complété par #288).
 * Remplace `js/view/player/{Grid,Edit}.js`.
 *
 * L'écran le plus fourni de l'administration : cinq filtres cumulables, trois
 * actions hors CRUD, et une photo dans le formulaire.
 *
 * Le lot 1 n'avait repris ni les trois actions, ni le filtre « dans 2 équipes »,
 * ni la photo — relevé en recette par Benjamin (#288).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
        'admin-picker-modal': defineAsyncComponent(() => import('../grid/AdminPickerModal.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Gestion des joueurs"
        entity-label="joueur"
        :columns="columns"
        :fields="fields"
        :row-filter="rowFilter"
        fetch-url="/rest/action.php/player/getPlayers"
        save-url="/rest/action.php/player/savePlayer"
        delete-url="/rest/action.php/player/delete_players">

        <template #filters>
          <label v-for="f in filterDefs" :key="f.key" class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="filters[f.key]" class="checkbox checkbox-sm checkbox-primary"/>
            <span>{{ f.label }}</span>
          </label>
        </template>

        <template #actions="{ selection }">
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="openPicker('club', selection)">
            <i class="fas fa-sitemap"></i> Associer à un club
          </button>
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="openPicker('team', selection)">
            <i class="fas fa-people-group"></i> Associer à une équipe
          </button>
          <button class="btn btn-sm btn-outline" :disabled="isBusy" @click="importing = true">
            <i class="fas fa-file-import"></i> Importer un fichier de licences
          </button>
        </template>
      </admin-grid>

      <admin-picker-modal v-if="picker"
                          :title="picker.title"
                          :help="picker.help"
                          :items="picker.items"
                          :confirm-label="picker.confirmLabel"
                          :is-busy="isBusy"
                          @confirm="associate"
                          @close="picker = null"></admin-picker-modal>

      <dialog v-if="importing" class="modal modal-open">
        <div class="modal-box">
          <h3 class="font-bold text-lg mb-1">Import d'un fichier de licences</h3>
          <p class="text-sm text-base-content/60 mb-4">
            Le PDF de licences édité par l'UFOLEP. Les joueurs existants sont mis
            à jour (licence, homologation, photo), les autres sont créés.
          </p>
          <form @submit.prevent="importLicences">
            <input ref="licenceFile"
                   type="file"
                   accept="application/pdf"
                   class="file-input file-input-bordered w-full"
                   required/>
            <div class="modal-action">
              <button type="button" class="btn btn-ghost" @click="importing = false">Annuler</button>
              <button type="submit" class="btn btn-primary" :disabled="isBusy">
                <span v-if="isBusy" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-file-import"></i>
                Importer
              </button>
            </div>
          </form>
          <p class="text-xs text-base-content/50 mt-2">
            L'import lit tout le PDF : compter jusqu'à une minute.
          </p>
        </div>
        <div class="modal-backdrop" @click="importing = false"></div>
      </dialog>
    `,
    data() {
        return {
            clubs: [],
            teams: [],
            isBusy: false,
            importing: false,
            picker: null,
            /** Identifiants des joueurs en attente d'association */
            pending: [],
            filters: {
                withoutClub: false,
                withoutLicence: false,
                inactive: false,
                twoTeamsSameCompetition: false,
                engaged: false,
            },
            filterDefs: [
                { key: 'withoutClub', label: 'Sans club' },
                { key: 'withoutLicence', label: 'Sans licence' },
                { key: 'inactive', label: 'Non valides' },
                { key: 'twoTeamsSameCompetition', label: 'Dans 2 équipes (même compétition)' },
                { key: 'engaged', label: 'Engagés dans au moins une équipe' },
            ],
        };
    },
    computed: {
        // Calculées et non déclarées dans `data()` : leurs `format` appellent
        // une méthode du composant.
        columns() {
            return [
                { key: 'nom', label: 'Nom' },
                { key: 'prenom', label: 'Prénom' },
                { key: 'sexe', label: 'Sexe' },
                { key: 'num_licence', label: 'N° licence' },
                { key: 'date_homologation', label: 'Homologation' },
                { key: 'club', label: 'Club' },
                // Ces deux colonnes arrivent agrégées en HTML (`<br/>`) : on
                // les aplatit, la grille affiche du texte.
                { key: 'active_teams_list', label: 'Équipes actives', format: (v) => this.teamEntries(v).join(' · ') },
                { key: 'inactive_teams_list', label: 'Équipes inactives', format: (v) => this.teamEntries(v).join(' · ') },
                { key: 'est_actif', label: 'Valide', format: (v) => (Number(v) ? 'oui' : 'non') },
            ];
        },
        fields() {
            return [
                { name: 'nom', label: 'Nom', required: true },
                { name: 'prenom', label: 'Prénom', required: true },
                {
                    name: 'sexe', label: 'Sexe', type: 'select', required: true,
                    options: [{ value: 'M', label: 'Masculin' }, { value: 'F', label: 'Féminin' }],
                },
                { name: 'num_licence', label: 'Numéro de licence' },
                { name: 'date_homologation', label: "Date d'homologation", placeholder: 'jj/mm/aaaa' },
                { name: 'departement_affiliation', label: "Département d'affiliation" },
                {
                    name: 'id_club', label: 'Club', type: 'select',
                    options: this.clubs.map((c) => ({ value: c.id, label: c.nom })),
                },
                { name: 'telephone', label: 'Téléphone' },
                { name: 'email', label: 'Email', type: 'email' },
                { name: 'telephone2', label: 'Téléphone 2' },
                { name: 'email2', label: 'Email 2' },
                // `Players::save()` appelle `savePhoto()` avec `$_FILES` : le
                // fichier part dans le meme envoi que le reste du formulaire.
                {
                    name: 'photo', label: 'Photo', type: 'file', accept: 'image/*',
                    help: 'Laisser vide pour conserver la photo actuelle',
                },
            ];
        },
        /**
         * Filtres cumulables, appliqués côté client comme le faisait la grille
         * ExtJS (l'endpoint renvoie la liste complète).
         */
        rowFilter() {
            const f = this.filters;
            if (!Object.values(f).some(Boolean)) {
                return null;
            }
            return (row) => {
                if (f.withoutClub && String(row.club || '').trim() !== '') return false;
                if (f.withoutLicence && String(row.num_licence || '').trim() !== '') return false;
                if (f.inactive && Number(row.est_actif)) return false;
                if (f.engaged && String(row.active_teams_list || '').trim() === '') return false;
                if (f.twoTeamsSameCompetition && !this.hasTwoTeamsInSameCompetition(row)) return false;
                return true;
            };
        },
    },
    created() {
        axios.get('/rest/action.php/club/get')
            .then(({ data }) => { this.clubs = data; })
            .catch(() => { this.clubs = []; });
        axios.get('/rest/action.php/team/getTeams')
            .then(({ data }) => { this.teams = data; })
            .catch(() => { this.teams = []; });
    },
    methods: {
        /**
         * Un joueur engagé deux fois dans la même compétition : c'est
         * irrégulier, d'où le filtre.
         *
         * `active_teams_list` agrège les équipes actives en une chaîne de la
         * forme « Équipe (Compétition) », séparées par des `<br/>` — pas par
         * des virgules, et un nom d'équipe peut en contenir une. Le filtre
         * compte les compétitions qui reviennent.
         */
        hasTwoTeamsInSameCompetition(row) {
            const competitions = this.teamEntries(row.active_teams_list)
                .map((entree) => {
                    const m = entree.match(/\(([^)]*)\)\s*$/);
                    return m ? m[1].trim() : null;
                })
                .filter(Boolean);
            return new Set(competitions).size < competitions.length;
        },
        /** Découpe une liste d'équipes agrégée en HTML par l'API. */
        teamEntries(value) {
            return String(value || '')
                .split(/<br\s*\/?>/i)
                .map((s) => s.trim())
                .filter(Boolean);
        },
        openPicker(kind, selection) {
            this.pending = selection;
            if (kind === 'club') {
                this.picker = {
                    kind,
                    title: `Associer ${selection.length} joueur(s) à un club`,
                    help: 'Le club de rattachement du joueur, pas son équipe.',
                    confirmLabel: 'Associer au club',
                    items: this.clubs.map((c) => ({ value: c.id, label: c.nom })),
                };
                return;
            }
            this.picker = {
                kind,
                title: `Associer ${selection.length} joueur(s) à une équipe`,
                help: "Le joueur est aussi rattaché au club de l'équipe si besoin.",
                confirmLabel: "Associer à l'équipe",
                items: this.teams.map((t) => ({
                    value: t.id_equipe,
                    label: t.nom_equipe,
                    hint: t.team_full_name,
                })),
            };
        },
        associate(values) {
            const target = values[0];
            const kind = this.picker.kind;
            const formData = new FormData();
            formData.append('id_players', this.pending.join(','));
            formData.append(kind === 'club' ? 'id_club' : 'id_team', target);
            this.isBusy = true;
            axios.post(
                kind === 'club'
                    ? '/rest/action.php/player/addPlayersToClub'
                    : '/rest/action.php/player/addPlayersToTeam',
                formData
            )
                .then((response) => {
                    onSuccess(this, response);
                    this.picker = null;
                    this.$refs.grid.fetchRows();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
        importLicences() {
            const file = this.$refs.licenceFile.files[0];
            if (!file) {
                return;
            }
            const formData = new FormData();
            formData.append('licences', file);
            this.isBusy = true;
            axios.post('/rest/action.php/player/update_from_licence_file', formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.importing = false;
                    this.$refs.grid.fetchRows();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
