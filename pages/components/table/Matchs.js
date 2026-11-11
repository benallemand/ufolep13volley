import {matchFilterMixin, filterBarTemplate} from "/utils/matchFilterMixin.js";

export default {
    mixins: [matchFilterMixin],
    template: `
      <div>
        ${filterBarTemplate}
        <div v-for="week in matchesByWeek" :key="week.label" class="mb-6">
          <h3 class="text-lg font-bold bg-primary/10 p-2 rounded-sm mb-2">{{ week.label }} ({{ week.matches.length }} matchs)</h3>
          <table class="table table-pin-rows bg-base-100">
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
          <tr v-for="match in week.matches" :key="match.id_match">
            <td>
              <a class="link link-primary" :href="$url('/match.html?id_match=' + match.id_match)"
                 target="_blank">{{ match.code_match }}
              </a>
              <a @click="addToGoogleCalendar(match)" class="btn btn-xs btn-primary ml-2"
                 title="Ajouter à Google Calendar">
                <i class="fas fa-calendar-plus"/>
              </a>
              <a v-if="isMatchToday(match) && !isMatchFinished(match)" :href="$url('/live.html?id_match=' + match.code_match)"
                 class="btn btn-xs btn-error ml-2 animate-pulse" title="Suivre en direct" target="_blank">
                <i class="fas fa-circle text-white text-xs mr-1"/>LIVE
              </a>
              <i v-if="match.certif === 1" class="fas fa-check-circle text-success ml-2" title="Match validé"></i>
            </td>
            <td>{{ match.date_reception }} {{ match.heure_reception }}<span v-if="match.match_status == 'NOT_CONFIRMED'"
                                                                            class="ml-1 badge badge-warning">date non confirmée</span>
            </td>
            <td :class="match.score_equipe_dom === 3 ? 'bg-success/5':''"><a
                :href="$url('/pages/home.html#/teams/' + match.id_equipe_dom)"
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
                :href="$url('/pages/home.html#/teams/' + match.id_equipe_ext)"
                class="link"
                target="_blank">
              {{ match.equipe_ext }}
            </a></td>
            <td>
            <!-- Vue retire les blancs entre balises : l'écart entre les sets passe par gap-x -->
            <div class="flex flex-wrap gap-x-2">
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
            </div>
            </td>
            <td class="max-w-48 w-48">
              <!-- sanctions du match (issue #477) : à décider en réunion, ou appliquées -->
              <div v-for="(s, i) in sanctionsOf(match)" :key="'s' + i" class="mb-1" data-testid="match-sanction">
                <span v-if="s.status === 'CONSTATEE'" class="badge badge-warning badge-sm whitespace-nowrap"
                      title="Manquement constaté : la commission décidera en réunion de fin de demi-saison">
                  <i class="fas fa-gavel mr-1"></i>sanction possible
                </span>
                <span v-else class="badge badge-error badge-sm whitespace-nowrap">
                  <i class="fas fa-gavel mr-1"></i>sanction appliquée
                </span>
                <p class="text-xs">
                  <strong>{{ s.equipe }}</strong> : {{ s.motif_label }}.
                  <template v-if="s.status === 'CONSTATEE'">
                    Sanction envisagée : {{ s.sanction_label.toLowerCase() }}, en attente de décision de la commission.
                  </template>
                  <template v-else>{{ s.sanction_label }}.</template>
                </p>
              </div>
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
        </div>
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
            // id_match -> sanctions à décider ou appliquées (issue #477)
            sanctions: {},
        };
    }, computed: {
        filteredMatchs() {
            return this.matchs.filter(match => this.applyBaseFilters(match));
        },
        matchesByWeek() {
            const grouped = {};
            const noDateMatches = [];
            this.filteredMatchs.forEach(match => {
                if (!match.date_reception_raw) {
                    noDateMatches.push(match);
                    return;
                }
                const date = new Date(match.date_reception_raw);
                const weekStart = this.getWeekStart(date);
                const weekKey = weekStart.toISOString().split('T')[0];
                if (!grouped[weekKey]) {
                    grouped[weekKey] = { matches: [], label: this.formatWeekLabel(weekStart), weekKey };
                }
                grouped[weekKey].matches.push(match);
            });
            const result = Object.values(grouped).sort((a, b) => a.weekKey.localeCompare(b.weekKey));
            if (noDateMatches.length > 0) {
                result.unshift({ matches: noDateMatches, label: 'Date non trouvée' });
            }
            return result;
        },
    }, methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.matchs = response.data.map(m => ({...m, showFullNote: false}));
                    this.fetchSanctions();
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
        // Sanctions des matchs affichés, par paquets pour garder des URL courtes.
        fetchSanctions() {
            const ids = this.matchs.map((m) => m.id_match).filter(Boolean);
            const chunks = [];
            for (let i = 0; i < ids.length; i += 200) {
                chunks.push(ids.slice(i, i + 200));
            }
            Promise.all(chunks.map((chunk) => axios
                .get('/rest/action.php/sanction/getMatchSanctions', {params: {ids: chunk.join(',')}})
                .then((response) => response.data)
                .catch(() => [])))
                .then((results) => {
                    const byMatch = {};
                    results.flat().forEach((s) => {
                        (byMatch[s.id_match] = byMatch[s.id_match] || []).push(s);
                    });
                    this.sanctions = byMatch;
                });
        },
        sanctionsOf(match) {
            return this.sanctions[match.id_match] || [];
        },
        resetFilters() {
            this.resetBaseFilters();
        },
        isMatchToday(match) {
            if (!match.date_reception) return false;
            const today = new Date().toLocaleDateString('fr-FR');
            return match.date_reception === today;
        },
        isMatchFinished(match) {
            return match.score_equipe_dom === 3 || match.score_equipe_ext === 3;
        },
        getWeekStart(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            return new Date(d.setDate(diff));
        },
        formatWeekLabel(weekStart) {
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 4);
            const options = { day: 'numeric', month: 'long' };
            const startStr = weekStart.toLocaleDateString('fr-FR', options);
            const endStr = weekEnd.toLocaleDateString('fr-FR', options);
            const year = weekStart.getFullYear();
            return `Semaine du ${startStr} au ${endStr} ${year}`;
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
