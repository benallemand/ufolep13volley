import { defineAsyncComponent } from 'vue';

/**
 * Dates interdites pour tous les gymnases (issue #265, lot 3).
 * Remplace `js/view/grid/BlacklistDate.js` + `js/view/window/BlacklistDate.js`.
 *
 * Ce sont les jours fériés et vacances où aucun match ne peut être programmé.
 * Les scripts Python de génération lisent cette table (`blacklist_date`).
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Dates interdites (tous gymnases)"
        entity-label="date interdite"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/blacklistdate/getBlacklistDate"
        save-url="/rest/action.php/blacklistdate/saveBlacklistDate"
        delete-url="/rest/action.php/blacklistdate/delete"/>
    `,
    data() {
        return {
            columns: [
                { key: 'closed_date', label: 'Date interdite' },
            ],
            fields: [
                { name: 'closed_date', label: 'Date interdite', type: 'date', required: true },
            ],
        };
    },
};
