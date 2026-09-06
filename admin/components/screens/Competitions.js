import { defineAsyncComponent } from 'vue';

/**
 * Gestion des compétitions (issue #265, lot 2).
 * Remplace `js/view/grid/Competitions.js` + `js/view/window/Competition.js`.
 *
 * Les actions de GÉNÉRATION du menu ExtJS ne sont pas reprises : journées,
 * matchs et phases finales sont générés par les scripts Python du dépôt
 * `ufolep13volley_python` (`calendar-agent/`). Seul le CRUD est migré.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des compétitions"
        entity-label="compétition"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/competition/getCompetitions"
        save-url="/rest/action.php/competition/saveCompetition"
        delete-url="/rest/action.php/competition/delete"/>
    `,
    data() {
        return {
            columns: [
                { key: 'code_competition', label: 'Code' },
                { key: 'libelle', label: 'Libellé' },
                { key: 'id_compet_maitre', label: 'Compétition maître' },
                { key: 'start_date', label: 'Début' },
                { key: 'start_register_date', label: 'Ouverture inscriptions' },
                { key: 'limit_register_date', label: 'Limite inscriptions' },
                { key: 'is_home_and_away', label: 'Aller-retour', format: (v) => (Number(v) ? 'oui' : 'non') },
            ],
            fields: [
                { name: 'code_competition', label: 'Code compétition', required: true },
                { name: 'libelle', label: 'Libellé', required: true },
                { name: 'id_compet_maitre', label: 'Code compétition maître' },
                { name: 'start_date', label: 'Date de début', placeholder: 'jj/mm/aaaa' },
                { name: 'start_register_date', label: "Ouverture des inscriptions", placeholder: 'jj/mm/aaaa' },
                { name: 'limit_register_date', label: "Date limite d'inscription", placeholder: 'jj/mm/aaaa' },
                { name: 'is_home_and_away', label: 'Matchs aller-retour ?', type: 'checkbox' },
            ],
        };
    },
};
