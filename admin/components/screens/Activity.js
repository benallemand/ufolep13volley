import { defineAsyncComponent } from 'vue';

/**
 * Journal d'activité (issue #265, lot 5).
 * Remplace `js/view/activity/Grid.js`.
 *
 * Écran de consultation : la trace de ce que les utilisateurs ont modifié,
 * alimentée par `Generic::addActivity()`. Pour un administrateur l'endpoint
 * renvoie tout ; pour un responsable d'équipe il se restreint de lui-même à son
 * équipe (le même endpoint sert la page « mon activité »).
 *
 * Le journal est **volumineux** : 9 696 lignes et 2,5 Mo en base de dev, ce que
 * chargeait la grille ExtJS. Comme pour les emails, on borne la fenêtre avec la
 * pagination du routeur (`_start`/`_end`, découpage serveur), la requête sortant
 * déjà en `ORDER BY activity_date DESC`.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Journal d'activité"
        entity-label="entrée"
        :columns="columns"
        :selectable="false"
        :fetch-url="fetchUrl">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <span>Dernières</span>
            <select v-model.number="windowSize" class="select select-bordered select-sm">
              <option :value="500">500</option>
              <option :value="2000">2000</option>
              <option :value="100000">toutes</option>
            </select>
            <span class="text-base-content/60">entrées</span>
          </label>
          <span class="text-xs text-base-content/60">
            Les plus récentes d'abord. « Toutes » représente environ 2,5 Mo.
          </span>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            windowSize: 500,
            columns: [
                { key: 'date', label: 'Date' },
                { key: 'nom_equipe', label: 'Équipe' },
                { key: 'competition', label: 'Compétition' },
                {
                    key: 'description', label: 'Description',
                    // `build_activity()` compose du HTML (`<br/>` entre les
                    // champs modifiés) : on l'aplatit en texte.
                    format: (v) => String(v ?? '')
                        .replace(/<br\s*\/?>/gi, ' · ')
                        .replace(/<[^>]*>/g, '')
                        .replace(/\s+/g, ' ')
                        .trim(),
                },
                { key: 'utilisateur', label: 'Utilisateur' },
                { key: 'email_utilisateur', label: 'Email' },
            ],
        };
    },
    computed: {
        fetchUrl() {
            return `/rest/action.php/activity/getActivity?_start=0&_end=${this.windowSize - 1}`;
        },
    },
};
