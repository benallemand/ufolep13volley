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
 *
 * Un clic sur une ligne ouvre le **rendu HTML** du message (issue #288) : la
 * grille n'en montre que le texte aplati, ce qui suffit pour survoler mais pas
 * pour vérifier un email. Le rendu se fait dans une `iframe` **sandboxée** —
 * un corps d'email est du HTML arbitraire, l'injecter dans la page exécuterait
 * ses scripts.
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
        row-clickable
        :fetch-url="fetchUrl"
        @row-click="opened = $event">

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

      <dialog v-if="opened" class="modal modal-open">
        <div class="modal-box max-w-5xl">
          <h3 class="font-bold text-lg">{{ opened.subject || '(sans sujet)' }}</h3>
          <div class="text-sm text-base-content/70 mt-2 space-y-1">
            <p><span class="font-semibold">De</span> : {{ opened.from_email }}</p>
            <p><span class="font-semibold">À</span> : {{ opened.to_email }}</p>
            <p v-if="opened.cc"><span class="font-semibold">Cc</span> : {{ opened.cc }}</p>
            <p v-if="opened.bcc"><span class="font-semibold">Cci</span> : {{ opened.bcc }}</p>
            <p>
              <span class="font-semibold">Créé le</span> {{ opened.creation_date }}
              <span v-if="opened.sent_date"> · <span class="font-semibold">envoyé le</span> {{ opened.sent_date }}</span>
              · <span :class="statusBadge(opened)">{{ opened.sending_status }}</span>
            </p>
          </div>

          <div class="mt-4 border border-base-300 rounded overflow-hidden bg-white">
            <iframe :srcdoc="opened.body || '<p>(corps vide)</p>'"
                    sandbox=""
                    class="w-full h-[55vh]"
                    title="Rendu du message"></iframe>
          </div>

          <div class="modal-action">
            <button class="btn btn-sm" @click="opened = null">Fermer</button>
          </div>
        </div>
        <div class="modal-backdrop" @click="opened = null"></div>
      </dialog>
    `,
    data() {
        return {
            windowSize: 500,
            isBusy: false,
            opened: null,
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
                    badge: (r) => this.statusBadge(r),
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
        /**
         * Trois valeurs en base : DONE, TO_DO, ERROR
         * (cf. `sql/retry_error_emails.sql`, qui repasse ERROR en TO_DO).
         */
        statusBadge(row) {
            return 'badge badge-sm ' + ({
                DONE: 'badge-success',
                TO_DO: 'badge-warning',
                ERROR: 'badge-error',
            }[row.sending_status] || 'badge-ghost');
        },
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
