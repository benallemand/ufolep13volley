import { defineAsyncComponent } from 'vue';

/**
 * Base de registres (issue #265, lot 4).
 * Remplace `js/view/grid/registry.js` + `js/view/form/registry.js`
 * + `js/controller/manage_registry.js`.
 *
 * Table clé / valeur de configuration. C'est notamment là que vit le tirage au
 * sort des phases finales, écrit par `FinalsDrawAdmin.js` et relu par
 * `generate_huitiemes.py` : y toucher à la main peut casser une génération.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Base de registres"
        entity-label="entrée"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/registry/get"
        save-url="/rest/action.php/registry/save_with_args"
        delete-url="/rest/action.php/registry/delete"/>
    `,
    data() {
        return {
            columns: [
                { key: 'registry_key', label: 'Clé' },
                { key: 'registry_value', label: 'Valeur' },
            ],
            fields: [
                { name: 'registry_key', label: 'Clé', required: true },
                { name: 'registry_value', label: 'Valeur' },
            ],
        };
    },
};
