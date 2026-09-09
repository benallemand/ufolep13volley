import { defineAsyncComponent } from 'vue';

/**
 * Calendrier de la page d'accueil (issue #265, lot 4 ; écran créé par #253).
 * Remplace `js/view/calendar_events/{AdminGrid,Edit}.js`.
 *
 * Le formulaire ExtJS séparait date et heure en quatre champs, que le
 * contrôleur recomposait avant l'envoi. Un `datetime-local` fait la même chose
 * en un champ : `AdminEditModal` convertit vers le `aaaa-mm-jj hh:mm:ss`
 * attendu par `saveCalendarEvent`.
 *
 * Une **heure à 00:00 signifie « journée entière »** : c'est ce que lit
 * `getCalendarEvents` pour n'afficher aucune heure sur la home (cas des
 * fériés). Ne pas « corriger » en forçant une heure par défaut.
 *
 * `date_end` vide vaut « événement ponctuel ».
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        title="Calendrier de la page d'accueil"
        entity-label="événement"
        :columns="columns"
        :fields="fields"
        :row-filter="rowFilter"
        delete-mode="id"
        fetch-url="/rest/action.php/calendarevents/getAllCalendarEvents"
        save-url="/rest/action.php/calendarevents/saveCalendarEvent"
        delete-url="/rest/action.php/calendarevents/deleteCalendarEvent">

        <template #filters="{ rows }">
          <label class="flex items-center gap-2 text-sm">
            <span>Saison</span>
            <select v-model="season" class="select select-bordered select-sm">
              <option value="">toutes</option>
              <option v-for="s in seasonsOf(rows)" :key="s" :value="s">{{ s }}</option>
            </select>
          </label>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            season: '',
        };
    },
    computed: {
        // Les colonnes sont calculées et non déclarées dans `data()` : leurs
        // `format` appellent une méthode du composant.
        columns() {
            return [
                { key: 'season', label: 'Saison' },
                { key: 'label', label: 'Libellé' },
                { key: 'date_start', label: 'Début', format: (v) => this.humanize(v) },
                {
                    key: 'date_end', label: 'Fin',
                    format: (v) => (v ? this.humanize(v) : '— (ponctuel)'),
                },
            ];
        },
        fields() {
            return [
                {
                    name: 'season', label: 'Saison', required: true,
                    placeholder: '2026-2027', pattern: '\\d{4}-\\d{4}',
                    help: 'Format attendu : 2026-2027',
                },
                {
                    name: 'label', label: 'Libellé', required: true,
                    placeholder: 'Championnats, Férié / pont, Réunion calendrier…',
                },
                { name: 'date_start', label: 'Début', type: 'datetime', required: true },
                {
                    name: 'date_end', label: 'Fin', type: 'datetime',
                    help: 'Vide = événement ponctuel',
                },
            ];
        },
        rowFilter() {
            const season = this.season;
            return season ? (r) => r.season === season : null;
        },
    },
    methods: {
        seasonsOf(rows) {
            return [...new Set(rows.map((r) => r.season).filter(Boolean))].sort().reverse();
        },
        /** `aaaa-mm-jj hh:mm:ss` -> `jj/mm/aaaa` ou `jj/mm/aaaa hh:mm`. */
        humanize(value) {
            const m = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
            if (!m) {
                return value ?? '';
            }
            const jour = `${m[3]}/${m[2]}/${m[1]}`;
            // 00:00 = journée entière, la home n'affiche alors pas d'heure
            return (m[4] === '00' && m[5] === '00') ? jour : `${jour} ${m[4]}:${m[5]}`;
        },
    },
};
