import { defineAsyncComponent } from 'vue';

/**
 * Sondages d'après-match (issue #265, lot 4).
 * Remplace `js/view/grid/survey.js` + `js/controller/manage_survey.js`.
 *
 * Écran de consultation : les sondages sont saisis par les équipes après un
 * match, l'administration les lit.
 *
 * Deux comportements repris du modèle ExtJS, qui n'existent pas côté API :
 * - la colonne « Match » était un champ `convert` composant
 *   `code_match (dom vs ext)` ;
 * - le store filtrait les lignes **vides** — une ligne est créée dès qu'un
 *   sondage est ouvert, même sans réponse. On ne garde donc que celles qui
 *   portent au moins une note ou un commentaire, sinon la grille affiche
 *   surtout du bruit.
 *
 * La colonne « Compte » (`login`) du modèle ExtJS n'est pas reprise :
 * `get_survey` ne la renvoie pas, elle était vide.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Sondages d'après-match"
        entity-label="sondage"
        :columns="columns"
        :row-filter="rowFilter"
        :selectable="false"
        fetch-url="/rest/action.php/matchmgr/get_survey">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="showEmpty" type="checkbox" class="checkbox checkbox-sm"/>
            <span>Afficher aussi les sondages non renseignés</span>
          </label>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            showEmpty: false,
            columns: [
                {
                    key: 'match', label: 'Match',
                    format: (v, r) => `${r.code_match} (${r.equipe_dom} vs ${r.equipe_ext})`,
                },
                { key: 'surveyor', label: 'Équipe sondeuse' },
                { key: 'surveyed', label: 'Équipe sondée' },
                { key: 'surveyed_club', label: 'Club sondé' },
                { key: 'on_time', label: 'Ponctualité', align: 'right' },
                { key: 'spirit', label: "État d'esprit", align: 'right' },
                { key: 'referee', label: 'Arbitrage', align: 'right' },
                { key: 'catering', label: 'Apéro', align: 'right' },
                { key: 'global', label: 'Global', align: 'right' },
                { key: 'comment', label: 'Commentaire' },
            ],
        };
    },
    computed: {
        rowFilter() {
            return this.showEmpty ? null : (r) => this.isFilled(r);
        },
    },
    methods: {
        isFilled(row) {
            const notes = ['on_time', 'spirit', 'referee', 'catering', 'global']
                .reduce((total, key) => total + Number(row[key] ?? 0), 0);
            return notes > 0 || Boolean(String(row.comment ?? '').trim());
        },
    },
};
