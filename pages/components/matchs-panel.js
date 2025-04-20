export default {
    template: `
      <div>
        <div class="flex flex-wrap gap-4 m-2">
          <label class="flex items-center gap-2">
            <input
                type="checkbox"
                v-model="filter.showPlayedMatchesOnly"
                class="checkbox checkbox-primary"
            />
            <span>joués</span>
          </label>
          <label class="flex items-center space-x-2">
            <input
                type="checkbox"
                v-model="filter.showForbiddenPlayer"
                class="checkbox checkbox-primary"
            />
            <span>joueurs non homologués</span>
          </label>
          <label class="flex items-center space-x-2">
            <input
                type="checkbox"
                v-model="filter.showCertified"
                class="checkbox checkbox-primary"
            />
            <span>certifiés</span>
          </label>
          <input
              type="text"
              v-model="searchQuery"
              placeholder="Rechercher un match..."
              class="input input-bordered flex-grow"
          />
          <button @click="resetFilters" class="btn btn-outline">Réinitialiser</button>
        </div>
        <div class="bg-base-200 border border-2 border-base-300 p-4">
          <!-- Loop through each journee group -->
          <div v-for="group in matchesByJournee" :key="group.journee" class="mb-8">
            <!-- Display journee as section title -->
            <h2 class="text-xl font-bold mb-4 p-2 bg-base-300 rounded-lg">{{ group.journee }}</h2>
            <!-- Display matches in this journee -->
            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <li v-for="match in group.matches" :key="match.id_match" class="card shadow-md bg-base-100">
                <div class="card-body">
                  <h2 class="card-title text-lg font-bold font-medium">{{ match.code_match }}
                    <span>
                        <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                           class="link link-info hover:underline">
                            <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                        <a :href="'mailto:'+match.email_dom+','+match.email_ext" target="_blank"
                           class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                        </a>
                    </span>
                  </h2>
                  <p class="text-sm font-medium">
                    <span class="">Compétition :</span> {{ match.libelle_competition }}
                  </p>
                  <p class="text-sm font-medium">
                    <span class="">Division :</span> {{ match.division }}
                  </p>
                  <p class="text-sm font-medium">
                    <span class="">Rencontre :</span>
                    <span>{{ match.equipe_dom }} <span class="font-medium"><a
                        :href="'mailto:'+match.email_dom" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                      </a></span>
                    </span>
                    vs
                    <span>{{ match.equipe_ext }} <span class="font-medium"><a
                        :href="'mailto:'+match.email_ext" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                      </a></span></span>
                  </p>
                  <p class="text-sm text-gray-600 font-medium"
                     v-if="match.score_equipe_dom > 0 || match.score_equipe_ext > 0">
                    <span class="text-gray-800">Score :</span>
                    <span class="text-xl">{{ match.score_equipe_dom }} - {{ match.score_equipe_ext }}</span>
                    <span v-if="match.set_1_dom > 0 || match.set_1_ext > 0">{{ match.set_1_dom }}/{{ match.set_1_ext }}
                    </span>
                    <span v-if="match.set_2_dom > 0 || match.set_2_ext > 0">{{ match.set_2_dom }}/{{ match.set_2_ext }}
                    </span>
                    <span v-if="match.set_3_dom > 0 || match.set_3_ext > 0">{{ match.set_3_dom }}/{{ match.set_3_ext }}
                    </span>
                    <span v-if="match.set_4_dom > 0 || match.set_4_ext > 0">{{ match.set_4_dom }}/{{ match.set_4_ext }}
                    </span>
                    <span v-if="match.set_5_dom > 0 || match.set_5_ext > 0">{{ match.set_5_dom }}/{{ match.set_5_ext }}
                    </span>
                  </p>
                  <p class="text-sm font-medium">
                    <span v-if="match.certif === 1" class="badge badge-success">Certifié</span>
                  </p>
                  <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_sign_team_dom + match.is_sign_team_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_sign_team_dom + match.is_sign_team_ext === 2 ? 'fiche équipe signée' : 'fiche équipe non signée'
                      }}
                      <span class="font-medium">
                            <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                               class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
                  </p>
                  <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_sign_match_dom + match.is_sign_match_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_sign_match_dom + match.is_sign_match_ext === 2 ? 'feuille de match signée' : 'feuille de match non signée'
                      }}
                      <span class="font-medium">
                            <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                               class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
                  </p>
                  <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_survey_filled_dom + match.is_survey_filled_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_survey_filled_dom + match.is_survey_filled_ext === 2 ? 'sondage rempli' : 'sondage non rempli'
                      }}
                    </span>
                  </p>
                  <p class="text-sm">
                    <span
                        class="badge badge-error"
                        v-if="match.has_forbidden_player === 1">
                        pb licence(s)
                        <span class="font-medium">
                            <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                               class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
                  </p>
                  <p class="text-sm font-medium">
                    <span class="">Date :</span> {{ match.date_reception }}
                  </p>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    `,
    props: {
        fetchUrl: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            matchs: [],
            searchQuery: "",
            filter: {
                showCertified: false,
                showForbiddenPlayer: false,
                showPlayedMatchesOnly: false,
            },
            user: null,
        };
    },
    computed: {
        displayedMatchs() {
            return this.matchs.filter((match) => {
                const matchesSearch =
                    match.equipe_dom.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.equipe_ext.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.code_match.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesCertif =
                    !this.filter.showCertified || match.certif === 1;
                const matchesForbiddenPlayers =
                    !this.filter.showForbiddenPlayer ||
                    match.has_forbidden_player === 1;
                const matchesPlayed =
                    !this.filter.showPlayedMatchesOnly ||
                    match.is_match_score_filled === 1;
                return matchesSearch
                    && matchesCertif
                    && matchesForbiddenPlayers
                    && matchesPlayed;
            }).sort((a, b) => a.date_reception_raw - b.date_reception_raw);
        },
        matchesByJournee() {
            const groupedMatches = {};
            this.displayedMatchs.forEach(match => {
                if (!groupedMatches[match.journee]) {
                    groupedMatches[match.journee] = [];
                }
                groupedMatches[match.journee].push(match);
            });
            // Convert to array of objects for v-for
            return Object.keys(groupedMatches).map(journee => ({
                journee: journee,
                matches: groupedMatches[journee]
            }));
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.matchs = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                    } else {
                        this.user = response.data;
                    }
                })
                .catch(() => {
                });
        },
        resetFilters() {
            this.filter.showPlayedMatchesOnly = false;
            this.filter.showCertified = false;
            this.filter.showForbiddenPlayer = false;
            this.searchQuery = "";
        },
    },
    created() {
        this.fetchUserDetails();
        this.fetch();
    },
};