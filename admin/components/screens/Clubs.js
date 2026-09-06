import { defineAsyncComponent } from 'vue';

/**
 * Gestion des clubs (issue #265, lot 1).
 * Remplace `js/view/club/{Grid,Edit}.js`.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Gestion des clubs"
        entity-label="club"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/club/get"
        save-url="/rest/action.php/club/saveClub"
        delete-url="/rest/action.php/club/deleteClubs"/>
    `,
    data() {
        return {
            columns: [
                { key: 'nom', label: 'Nom' },
                { key: 'affiliation_number', label: "N° d'affiliation" },
                { key: 'nom_responsable', label: 'Responsable' },
                { key: 'prenom_responsable', label: 'Prénom' },
                { key: 'tel1_responsable', label: 'Tél. 1' },
                { key: 'tel2_responsable', label: 'Tél. 2' },
                { key: 'email_responsable', label: 'Email' },
            ],
            fields: [
                { name: 'nom', label: 'Nom', required: true },
                { name: 'affiliation_number', label: "Numéro d'affiliation", required: true },
                { name: 'nom_responsable', label: 'Nom du responsable', required: true },
                { name: 'prenom_responsable', label: 'Prénom du responsable', required: true },
                { name: 'tel1_responsable', label: 'Téléphone du responsable', required: true },
                { name: 'tel2_responsable', label: 'Autre téléphone' },
                { name: 'email_responsable', label: 'Email du responsable', type: 'email', required: true },
            ],
        };
    },
};
