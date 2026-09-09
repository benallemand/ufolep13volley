import { defineAsyncComponent } from 'vue';
import { onError, onSuccess } from '../../../toaster.js';

/**
 * Journal des emails (issue #265, lot 4).
 * Remplace `js/view/grid/email.js` + `js/controller/{manage_email,
 * retry_error_emails,send_mail_team_recap}.js`.
 *
 * Écran de consultation : aucune ligne ne se crée ni ne se modifie ici, les
 * emails sont produits par l'application. Deux actions globales, reprises de
 * la toolbar ExtJS : relancer les envois en erreur, et déclencher le récap des
 * créneaux aux équipes.
 *
 * **La table est volumineuse** : 6 257 lignes en base de dev, et un `body`
 * HTML par ligne — soit 11 Mo si on charge tout, ce que faisait la grille
 * ExtJS. On borne donc la fenêtre avec la pagination du routeur
 * (`_start`/`_end`, qui découpe côté serveur), les emails sortant déjà en
 * `ORDER BY id DESC`. Le sélecteur permet de l'élargir à la demande.
 */
export default {
    components: {
        'admin-grid': defineAsyncComponent(() => import('../grid/AdminGrid.js')),
    },
    template: `
      <admin-grid
        ref="grid"
        title="Journal des emails"
        entity-label="email"
        :columns="columns"
        :selectable="false"
        :fetch-url="fetchUrl">

        <template #filters>
          <label class="flex items-center gap-2 text-sm">
            <span>Derniers</span>
            <select v-model.number="windowSize" class="select select-bordered select-sm">
              <option :value="500">500</option>
              <option :value="2000">2000</option>
              <option :value="100000">tous</option>
            </select>
            <span class="text-base-content/60">emails</span>
          </label>
          <span class="text-xs text-base-content/60">
            Les plus récents d'abord. Charger « tous » représente plusieurs Mo.
          </span>
        </template>

        <template #actions="{ reload }">
          <button class="btn btn-sm btn-outline"
                  :disabled="isBusy"
                  @click="run('/rest/action.php/emails/retry_error_emails',
                              'Relancer tous les emails en erreur ?', reload)">
            <i class="fas fa-rotate-right"></i> Relancer les erreurs
          </button>
          <button class="btn btn-sm btn-outline"
                  :disabled="isBusy"
                  @click="run('/rest/action.php/emails/insert_email_team_recap',
                              'Envoyer à chaque équipe le récapitulatif de ses créneaux ?', reload)">
            <i class="fas fa-paper-plane"></i> Récap créneaux
          </button>
        </template>
      </admin-grid>
    `,
    data() {
        return {
            windowSize: 500,
            isBusy: false,
        };
    },
    computed: {
        fetchUrl() {
            return `/rest/action.php/emails/get?_start=0&_end=${this.windowSize - 1}`;
        },
        columns() {
            return [
                { key: 'creation_date', label: 'Créé le' },
                { key: 'sent_date', label: 'Envoyé le' },
                {
                    key: 'sending_status', label: 'Statut',
                    // Trois valeurs en base : DONE, TO_DO, ERROR
                    // (cf. `sql/retry_error_emails.sql`, qui repasse ERROR en TO_DO).
                    badge: (r) => 'badge badge-sm ' + ({
                        DONE: 'badge-success',
                        TO_DO: 'badge-warning',
                        ERROR: 'badge-error',
                    }[r.sending_status] || 'badge-ghost'),
                },
                { key: 'to_email', label: 'Destinataire' },
                { key: 'cc', label: 'Cc' },
                { key: 'subject', label: 'Sujet' },
                {
                    key: 'body', label: 'Contenu',
                    // Le corps est du HTML complet : on n'en montre que le
                    // texte, tronqué, sinon la grille est illisible.
                    format: (v) => {
                        const texte = String(v ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                        return texte.length > 120 ? texte.slice(0, 120) + '…' : texte;
                    },
                },
            ];
        },
    },
    methods: {
        run(url, question, reload) {
            if (!window.confirm(question)) {
                return;
            }
            this.isBusy = true;
            axios.post(url, new FormData())
                .then((response) => {
                    onSuccess(this, response);
                    reload();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isBusy = false; });
        },
    },
};
