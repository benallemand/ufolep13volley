import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Membres de la commission (issue #265, lot 4).
 * Remplace `js/view/grid/commission.js` + `js/view/form/commission.js`
 * + `js/controller/manage_commission.js`.
 *
 * L'écriture passe par `commission/save_with_args`, la variante variadique de
 * `Generic::save()` : le routeur l'appelle avec des arguments nommés, la
 * méthode les récupère tels quels. L'identifiant est `id_commission`.
 *
 * L'attribution des divisions est une action à part : elle réécrit la table de
 * liaison `commission_division` pour les membres sélectionnés. Une chaîne vide
 * retire toutes leurs divisions — c'est ce que faisait le prompt ExtJS.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Membres de la commission"
        entity-label="membre"
        id-field="id_commission"
        :columns="columns"
        :fields="fields"
        fetch-url="/rest/action.php/commission/get"
        save-url="/rest/action.php/commission/save_with_args"
        delete-url="/rest/action.php/commission/delete">

        <template #actions="{ selection, rows, reload }">
          <button class="btn btn-sm btn-outline"
                  :disabled="!selection.length || isBusy"
                  @click="setAttribution(selection, rows, reload)">
            <i class="fas fa-diagram-project"></i> Attribuer les divisions
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            isBusy: false,
            columns: [
                { key: 'nom', label: 'Nom' },
                { key: 'prenom', label: 'Prénom' },
                { key: 'fonction', label: 'Fonction' },
                { key: 'telephone1', label: 'Téléphone 1' },
                { key: 'telephone2', label: 'Téléphone 2' },
                { key: 'email', label: 'Email' },
                { key: 'type', label: 'Type' },
                { key: 'attribution', label: 'Divisions attribuées' },
            ],
            fields: [
                { name: 'nom', label: 'Nom' },
                { name: 'prenom', label: 'Prénom' },
                { name: 'fonction', label: 'Fonction' },
                { name: 'telephone1', label: 'Téléphone 1' },
                { name: 'telephone2', label: 'Téléphone 2' },
                { name: 'email', label: 'Email', type: 'email' },
                { name: 'photo', label: 'Photo', help: 'Nom du fichier dans images/' },
                { name: 'type', label: 'Type' },
            ],
        };
    },
    methods: {
        setAttribution(selection, rows, reload) {
            const noms = selection
                .map((id) => rows.find((r) => String(r.id_commission) === String(id)))
                .map((r) => (r ? `${r.prenom} ${r.nom}`.trim() : '?'));
            const actuelles = selection
                .map((id) => rows.find((r) => String(r.id_commission) === String(id)))
                .map((r) => r && r.attribution)
                .find(Boolean) || '';
            const divisions = window.prompt(
                `Divisions à attribuer à ${noms.join(', ')} ?\n\n`
                + 'Séparées par des virgules, au format compétition/division — ex. m/1,f/2.\n'
                + 'Laisser vide retire toutes les divisions.',
                actuelles
            );
            if (divisions === null) {
                return;
            }
            const formData = new FormData();
            formData.append('ids', selection.join(','));
            formData.append('divisions', divisions);
            this.isBusy = true;
            axios.post('/rest/action.php/commission/attribution', formData)
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
