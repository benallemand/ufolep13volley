import { defineAsyncComponent } from 'vue';

/**
 * Gestion des joueurs (issue #265, lot 1).
 * Remplace `js/view/player/{Grid,Edit}.js`.
 *
 * C'est l'écran le plus fourni du lot : cinq filtres cumulables, en plus du
 * CRUD. Ils reprennent exactement les cases à cocher de la grille ExtJS.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
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
      </admin-grid>
    `,
    data() {
        return {
            clubs: [],
            filters: {
                withoutClub: false,
                withoutLicence: false,
                inactive: false,
                engaged: false,
            },
            filterDefs: [
                { key: 'withoutClub', label: 'Sans club' },
                { key: 'withoutLicence', label: 'Sans licence' },
                { key: 'inactive', label: 'Non valides' },
                { key: 'engaged', label: 'Engagés dans au moins une équipe' },
            ],
            columns: [
                { key: 'nom', label: 'Nom' },
                { key: 'prenom', label: 'Prénom' },
                { key: 'sexe', label: 'Sexe' },
                { key: 'num_licence', label: 'N° licence' },
                { key: 'date_homologation', label: 'Homologation' },
                { key: 'club', label: 'Club' },
                { key: 'active_teams_list', label: 'Équipes actives' },
                { key: 'inactive_teams_list', label: 'Équipes inactives' },
                { key: 'est_actif', label: 'Valide', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
        };
    },
    computed: {
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
                return true;
            };
        },
    },
    created() {
        axios.get('/rest/action.php/club/get')
            .then(({ data }) => { this.clubs = data; })
            .catch(() => { this.clubs = []; });
    },
};
