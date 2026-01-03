import {matchFilterMixin, filterBarTemplate} from "/utils/matchFilterMixin.js";

export default {
    mixins: [matchFilterMixin],
    template: `
      <div>
        ${filterBarTemplate}
        <table class="table mt-2 table-pin-rows bg-base-100">
          <thead>
          <tr>
            <th>code</th>
            <th>date</th>
            <th></th>
            <th>résultat</th>
            <th></th>
            <th>score</th>
            <th class="max-w-48">commentaires</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="match in filteredMatchs" :key="match.id_match">
            <td>
              <a class="link link-primary" :href="'/match.php?id_match=' + match.id_match"
                 target="_blank">{{ match.code_match }}
              </a>
              <a @click="addToGoogleCalendar(match)" class="btn btn-xs btn-primary ml-2"
                 title="Ajouter à Google Calendar">
                <i class="fas fa-calendar-plus"/>
              </a>
              <i v-if="match.certif === 1" class="fas fa-check-circle text-success ml-2" title="Match validé"></i>
            </td>
            <td>{{ match.date_reception }} {{ match.heure_reception }}<span v-if="match.match_status == 'NOT_CONFIRMED'"
                                                                            class="ml-1 badge badge-warning">date non confirmée</span>
            </td>
            <td :class="match.score_equipe_dom === 3 ? 'bg-success/5':''"><a
                :href="'/pages/home.html#/teams/' + match.id_equipe_dom"
                class="link"
                target="_blank">
              {{ match.equipe_dom }}
            </a></td>
            <td>
              <span :class="match.score_equipe_dom === 3 ? 'text-success':''">{{ match.score_equipe_dom }}</span>
              /
              <span :class="match.score_equipe_ext === 3 ? 'text-success':''">{{ match.score_equipe_ext }}</span>
            </td>
            <td :class="match.score_equipe_ext === 3 ? 'bg-success/5':''"><a
                :href="'/pages/home.html#/teams/' + match.id_equipe_ext"
                class="link"
                target="_blank">
              {{ match.equipe_ext }}
            </a></td>
            <td>
            <span
                v-if="match.score_equipe_dom+match.score_equipe_ext >=1"><span>{{ match.set_1_dom }}</span>/<span>{{ match.set_1_ext }}</span></span>
              <span
                  v-if="match.score_equipe_dom+match.score_equipe_ext >=2"><span>{{ match.set_2_dom }}</span>/<span>{{ match.set_2_ext }}</span></span>
              <span
                  v-if="match.score_equipe_dom+match.score_equipe_ext >=3"><span>{{ match.set_3_dom }}</span>/<span>{{ match.set_3_ext }}</span></span>
              <span
                  v-if="match.score_equipe_dom+match.score_equipe_ext >=4"><span>{{ match.set_4_dom }}</span>/<span>{{ match.set_4_ext }}</span></span>
              <span
                  v-if="match.score_equipe_dom+match.score_equipe_ext >=5"><span>{{ match.set_5_dom }}</span>/<span>{{ match.set_5_ext }}</span></span>
            </td>
            <td class="max-w-48 w-48">
              <div v-if="match.note">
                <p v-if="!match.showFullNote" class="truncate">{{ match.note }}</p>
                <p v-else>{{ match.note }}</p>
                <button v-if="match.note.length > 30" @click="match.showFullNote = !match.showFullNote" class="link link-primary text-xs">
                  {{ match.showFullNote ? 'voir -' : 'voir +' }}
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
        <div v-if="filteredMatchs.length === 0" class="text-center text-gray-500 py-4">
          Aucun match ne correspond aux critères de recherche.
        </div>
      </div>
    `, props: {
        fetchUrl: {
            type: String, required: true,
        }
    }, data() {
        return {
            matchs: [],
        };
    }, computed: {
        filteredMatchs() {
            return this.matchs.filter(match => this.applyBaseFilters(match));
        },
    }, methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.matchs = response.data.map(m => ({...m, showFullNote: false}));
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
        resetFilters() {
            this.resetBaseFilters();
        },
        addToGoogleCalendar(match) {
            // Convertir le timestamp en date
            const matchDate = new Date(match.date_reception_raw);
            
            // Parser l'heure (format "HH:MM")
            const [hours, minutes] = match.heure_reception.split(':');
            matchDate.setHours(parseInt(hours), parseInt(minutes), 0, 0);
            
            // Date de fin (+ 2 heures)
            const endDate = new Date(matchDate);
            endDate.setHours(endDate.getHours() + 2);
            
            // Format pour Google Calendar (YYYYMMDDTHHMMSS)
            const formatGoogleDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hour = String(date.getHours()).padStart(2, '0');
                const minute = String(date.getMinutes()).padStart(2, '0');
                return `${year}${month}${day}T${hour}${minute}00`;
            };

            const startDate = formatGoogleDate(matchDate);
            const endDateFormatted = formatGoogleDate(endDate);

            const title = `${match.equipe_dom} contre ${match.equipe_ext}`;
            const details = 'Compétition Ufolep 13 Volley';
            const location = `https://www.ufolep13volley.org/pages/home.html#/teams/${match.id_equipe_dom}`;

            const url = `https://calendar.google.com/calendar/render?action=TEMPLATE` +
                `&text=${encodeURIComponent(title)}` +
                `&dates=${startDate}/${endDateFormatted}` +
                `&details=${encodeURIComponent(details)}` +
                `&location=${encodeURIComponent(location)}` +
                `&ctz=Europe/Paris`;

            window.open(url, '_blank');
        },
    }, created() {
        this.fetch();
    },
};
