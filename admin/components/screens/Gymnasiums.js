import { defineAsyncComponent } from 'vue';

/**
 * Gestion des gymnases (issue #265, lot 0).
 *
 * Premier écran porté sur le socle générique, pour le valider. Il remplace
 * `js/view/gymnasium/Grid.js` + `js/view/gymnasium/Edit.js` — 5 colonnes,
 * 8 champs — soit ~180 lignes d'ExtJS ramenées à une déclaration.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des gymnases"
        entity-label="gymnase"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/court/getGymnasiums"
        save-url="/rest/action.php/court/saveGymnasium"
        delete-url="/rest/action.php/court/delete"/>
    `,
    data() {
        return {
            columns: [
                { key: 'nom', label: 'Nom' },
                { key: 'adresse', label: 'Adresse' },
                { key: 'code_postal', label: 'CP' },
                { key: 'ville', label: 'Ville' },
                { key: 'nb_terrain', label: 'Terrains', align: 'right' },
                { key: 'remarques', label: 'Remarques' },
            ],
            fields: [
                { name: 'nom', label: 'Nom', required: true },
                { name: 'adresse', label: 'Adresse', required: true },
                { name: 'code_postal', label: 'Code postal', required: true },
                { name: 'ville', label: 'Ville', required: true },
                { name: 'gps', label: 'GPS', required: true, help: 'Coordonnées, ex. 43.2965,5.3698' },
                { name: 'nb_terrain', label: 'Nombre de terrains', type: 'number', required: true, min: 1, max: 6 },
                { name: 'remarques', label: 'Remarques', type: 'textarea' },
            ],
        };
    },
};
