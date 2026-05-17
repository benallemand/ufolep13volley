export default {
    data() {
        return {
            emails: [],
            selectedEmail: null,
            isLoading: false,
            errorMessage: null,
            idEquipe: null,
        };
    },
    computed: {
        unreadCount() {
            return this.emails.filter(e => !parseInt(e.is_read)).length;
        }
    },
    methods: {
        fetchEmails() {
            if (!this.idEquipe) return;
            this.isLoading = true;
            this.errorMessage = null;
            axios
                .get(`/rest/action.php/emails/get_team_emails?id_equipe=${this.idEquipe}`)
                .then((response) => {
                    this.emails = response.data;
                })
                .catch((error) => {
                    this.errorMessage = error.response?.data?.message || "Erreur lors du chargement des messages.";
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        openEmail(email) {
            this.selectedEmail = email;
            if (!parseInt(email.is_read)) {
                this.setReadStatus(email, 1);
            }
        },
        closeDetail() {
            this.selectedEmail = null;
        },
        setReadStatus(email, is_read) {
            const formData = new FormData();
            formData.append('id', email.id);
            formData.append('is_read', is_read);
            axios
                .post(`/rest/action.php/emails/set_read_status`, formData)
                .then(() => {
                    email.is_read = is_read;
                    if (this.selectedEmail && this.selectedEmail.id === email.id) {
                        this.selectedEmail.is_read = is_read;
                    }
                })
                .catch((error) => {
                    alert(error.response?.data?.message || "Erreur lors de la mise à jour du statut.");
                });
        },
        markAllRead() {
            const formData = new FormData();
            formData.append('id_equipe', this.idEquipe);
            axios
                .post(`/rest/action.php/emails/mark_all_read`, formData)
                .then(() => {
                    this.emails.forEach(e => { e.is_read = 1; });
                })
                .catch((error) => {
                    alert(error.response?.data?.message || "Erreur lors de la mise à jour.");
                });
        },
        bodyPreview(email) {
            const stripped = email.body_preview
                ? email.body_preview.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
                : '';
            return stripped.length > 120 ? stripped.substring(0, 120) + '…' : stripped;
        },
    },
    created() {
        axios.get('/session_user.php').then((response) => {
            if (response.data && !response.data.error) {
                this.idEquipe = response.data.id_equipe;
                this.fetchEmails();
            }
        });
    },
    template: `
      <div>
        <div class="flex items-center gap-3 mb-4">
          <p class="text-xl font-bold">Messages</p>
          <span v-if="unreadCount > 0" class="badge badge-error">{{ unreadCount }} non lu{{ unreadCount > 1 ? 's' : '' }}</span>
          <button v-if="unreadCount > 0 && !selectedEmail" class="btn btn-xs btn-outline ml-auto" @click="markAllRead">Tout marquer comme lu</button>
        </div>

        <div v-if="isLoading" class="flex justify-center p-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="errorMessage" class="alert alert-error">{{ errorMessage }}</div>

        <div v-else-if="!selectedEmail">
          <div v-if="emails.length === 0" class="alert alert-info">Aucun message pour votre équipe.</div>
          <div v-else class="overflow-x-auto">
            <table class="table table-zebra w-full">
              <thead>
                <tr>
                  <th></th>
                  <th>Date</th>
                  <th>Sujet</th>
                  <th>À / CC</th>
                  <th>Aperçu</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="email in emails"
                  :key="email.id"
                  :class="!parseInt(email.is_read) ? 'font-bold bg-base-200' : ''"
                  class="cursor-pointer hover"
                  @click="openEmail(email)">
                  <td>
                    <span v-if="!parseInt(email.is_read)" class="badge badge-error badge-sm">?</span>
                  </td>
                  <td class="whitespace-nowrap">{{ email.creation_date_fmt }}</td>
                  <td>{{ email.subject }}</td>
                  <td class="text-xs">{{ email.to_email }}<span v-if="email.cc" class="text-base-content/60"> / {{ email.cc }}</span></td>
                  <td class="text-sm text-base-content/70">{{ bodyPreview(email) }}</td>
                  <td @click.stop>
                    <button
                      v-if="!parseInt(email.is_read)"
                      class="btn btn-xs btn-outline"
                      @click="setReadStatus(email, 1)">
                      Marquer lu
                    </button>
                    <button
                      v-else
                      class="btn btn-xs btn-ghost"
                      @click="setReadStatus(email, 0)">
                      Marquer non lu
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else>
          <div class="flex items-center gap-2 mb-4">
            <button class="btn btn-sm btn-ghost" @click="closeDetail">
              <i class="fas fa-arrow-left mr-1"></i>Retour
            </button>
            <button
              v-if="!parseInt(selectedEmail.is_read)"
              class="btn btn-sm btn-outline"
              @click="setReadStatus(selectedEmail, 1)">
              Marquer comme lu
            </button>
            <button
              v-else
              class="btn btn-sm btn-ghost"
              @click="setReadStatus(selectedEmail, 0)">
              Marquer comme non lu
            </button>
          </div>
          <div class="card bg-base-100 shadow-md">
            <div class="card-body">
              <h2 class="card-title text-lg">{{ selectedEmail.subject }}</h2>
              <div class="text-sm text-base-content/70 grid grid-cols-1 gap-1 mb-4">
                <div><span class="font-semibold">À :</span> {{ selectedEmail.to_email }}</div>
                <div v-if="selectedEmail.cc"><span class="font-semibold">Cc :</span> {{ selectedEmail.cc }}</div>
                <div v-if="selectedEmail.bcc"><span class="font-semibold">Bcc :</span> {{ selectedEmail.bcc }}</div>
                <div><span class="font-semibold">Reçu le :</span> {{ selectedEmail.creation_date_fmt }}</div>
                <div v-if="selectedEmail.sent_date_fmt"><span class="font-semibold">Envoyé le :</span> {{ selectedEmail.sent_date_fmt }}</div>
                <div>
                  <span class="font-semibold">Statut :</span>
                  <span :class="parseInt(selectedEmail.is_read) ? 'badge badge-success badge-sm ml-1' : 'badge badge-error badge-sm ml-1'">
                    {{ parseInt(selectedEmail.is_read) ? 'Lu' : 'Non lu' }}
                  </span>
                </div>
              </div>
              <div class="divider"></div>
              <div class="prose max-w-none" v-html="selectedEmail.body"></div>
            </div>
          </div>
        </div>
      </div>
    `
};
