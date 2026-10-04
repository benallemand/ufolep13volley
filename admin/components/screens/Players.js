import { defineAsyncComponent } from 'vue';
import { persistedFilters } from '../grid/gridState.js';
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
    // Filtres de l'écran mémorisés entre deux visites (issue #311)
    mixins: [persistedFilters(['filters'])],
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
        'admin-picker-modal': defineAsyncComponent(() => import('../grid/AdminPickerModal.js')),
        'licence-import-modal': defineAsyncComponent(() => import('../../../pages/components/form/LicenceImportModal.js')),
        'player-merge-modal': defineAsyncComponent(() => import('./PlayerMergeModal.js')),
    },
    template: `
      <admin-grid
        @reset-view="resetPersistedFilters"
        ref="grid"
        title="Gestion des joueurs"
        entity-label="joueur"
        :columns="columns"
        :fields="fields"
        :detail="detail"
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

        <template #actions="{ selection, rows }">
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
          <!-- Doublons de saisie (#409) : deux fiches, une seule gardée. -->
          <button class="btn btn-sm btn-outline"
                  data-testid="player-merge-open"
                  :disabled="selection.length !== 2 || isBusy"
                  title="Sélectionnez exactement deux fiches du même joueur"
                  @click="merging = rows.filter((r) => selection.includes(r.id))">
            <i class="fas fa-code-merge"></i> Fusionner…
          </button>
          <button class="btn btn-sm btn-outline" :disabled="isBusy" @click="importing = true">
            <i class="fas fa-file-import"></i> Importer des licences
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

      <player-merge-modal v-if="merging"
                          :players="merging"
                          @close="merging = null"
                          @merged="merging = null; $refs.grid.fetchRows()"></player-merge-modal>

      <!-- Import groupé des licences (issue #394), partagé avec l'effectif. -->
      <licence-import-modal v-if="importing"
                            @close="importing = false"
                            @imported="$refs.grid.fetchRows()"></licence-import-modal>
    `,
    data() {
        return {
            clubs: [],
            teams: [],
            isBusy: false,
            importing: false,
            // Les deux fiches à fusionner (#409), ou null.
            merging: null,
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
        /**
         * Six colonnes depuis l'issue #308, contre onze auparavant : numéro de
         * licence, équipes inactives et validité sont passés dans le tiroir de
         * détail, qui les montre au clic sur la ligne. Ce qui reste sert à
         * retrouver un joueur ; le reste sert à le consulter.
         */
        columns() {
            return [
                // La vignette utilise `path_photo_low`, déjà renvoyé par
                // `getPlayers` : l'afficher ne coûte aucune donnée
                // supplémentaire (issue #295). `adjust_photo_path_from_results`
                // garantit une image de repli quand le fichier manque.
                {
                    key: 'path_photo_low', label: '', image: true,
                    alt: (row) => (row.prenom || '') + ' ' + (row.nom || ''),
                },
                { key: 'nom', label: 'Nom' },
                { key: 'prenom', label: 'Prénom' },
                { key: 'sexe', label: 'Sexe' },
                { key: 'date_homologation', label: 'Homologation' },
                { key: 'club', label: 'Club' },
                // Cette colonne arrive agrégée en HTML (`<br/>`) : on l'aplatit,
                // la grille affiche du texte.
                { key: 'active_teams_list', label: 'Équipes actives', format: (v) => this.teamEntries(v).join(' · ') },
            ];
        },
        /**
         * Tiroir de détail (issue #308). Il porte les colonnes retirées de la
         * grille, et surtout les champs que la grille n'a jamais montrés :
         * département d'affiliation, téléphones et emails. Tous sont déjà dans
         * la réponse de `getPlayers` — aucun appel supplémentaire.
         */
        detail() {
            return {
                title: (row) => ((row.prenom || '') + ' ' + (row.nom || '')).trim(),
                subtitle: (row) => row.club || '',
                image: (row) => row.path_photo_low || row.path_photo || '',
                badge: (row) => (Number(row.est_actif)
                    ? { label: 'Licence validée', tone: 'success' }
                    : { label: 'Licence non validée', tone: 'error' }),
                sections: [
                    {
                        title: 'Licence',
                        fields: [
                            { key: 'num_licence', label: 'N° de licence' },
                            { key: 'date_homologation', label: 'Homologation' },
                            { key: 'departement_affiliation', label: 'Département' },
                            { key: 'sexe', label: 'Sexe', format: (v) => (v === 'F' ? 'Féminin' : 'Masculin') },
                        ],
                    },
                    {
                        title: 'Contact',
                        fields: [
                            { key: 'email', label: 'Email' },
                            { key: 'telephone', label: 'Téléphone' },
                            { key: 'email2', label: 'Email 2' },
                            { key: 'telephone2', label: 'Téléphone 2' },
                        ],
                    },
                    {
                        title: 'Équipes',
                        fields: [
                            { key: 'active_teams_list', label: 'Actives', format: (v) => this.teamEntries(v).join(' · ') },
                            { key: 'inactive_teams_list', label: 'Inactives', format: (v) => this.teamEntries(v).join(' · ') },
                            // Les équipes où la personne figure sans y jouer
                            // (issue #325) : responsable d'une équipe féminine
                            // alors qu'elle joue en masculin, par exemple. Ces
                            // équipes restent dans les listes ci-dessus — une
                            // appartenance reste une appartenance — et cette
                            // ligne dit lesquelles ne comptent pas dans un
                            // effectif.
                            { key: 'non_playing_teams_list', label: 'Sans y jouer', format: (v) => this.teamEntries(v).join(' · ') },
                        ],
                    },
                ],
            };
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
         *
         * Les appartenances non jouantes (issue #325) sont retirées d'abord :
         * être responsable d'une équipe où l'on ne joue pas ne constitue pas
         * un double engagement.
         */
        hasTwoTeamsInSameCompetition(row) {
            const nonPlaying = new Set(this.teamEntries(row.non_playing_teams_list));
            const competitions = this.teamEntries(row.active_teams_list)
                .filter((entree) => !nonPlaying.has(entree))
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
    },
};
