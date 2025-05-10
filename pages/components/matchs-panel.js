export default {
    components: {
        'match-card': () => import('./match-card.js')
    },
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
          <label class="flex items-center gap-2">
            <input
                type="checkbox"
                v-model="filter.showNonPlayedMatchesOnly"
                class="checkbox checkbox-primary"
            />
            <span>non joués</span>
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
          <label class="flex items-center space-x-2">
            <input
                type="checkbox"
                v-model="filter.showNotCertified"
                class="checkbox checkbox-primary"
            />
            <span>non certifiés</span>
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
                <match-card :match="match"></match-card>
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
                showNotCertified: true,
                showForbiddenPlayer: false,
                showPlayedMatchesOnly: false,
                showNonPlayedMatchesOnly: false,
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
                const matchesNotCertif =
                    !this.filter.showNotCertified || match.certif === 0;
                const matchesForbiddenPlayers =
                    !this.filter.showForbiddenPlayer ||
                    match.has_forbidden_player === 1;
                const matchesPlayed =
                    !this.filter.showPlayedMatchesOnly ||
                    match.is_match_score_filled === 1;
                const matchesNonPlayed =
                    !this.filter.showNonPlayedMatchesOnly ||
                    match.is_match_score_filled === 0;
                return matchesSearch
                    && matchesCertif
                    && matchesNotCertif
                    && matchesForbiddenPlayers
                    && matchesPlayed
                    && matchesNonPlayed;
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
            this.filter.showNonPlayedMatchesOnly = false;
            this.filter.showCertified = false;
            this.filter.showNotCertified = false;
            this.filter.showForbiddenPlayer = false;
            this.searchQuery = "";
        },
    },
    created() {
        this.fetchUserDetails();
        this.fetch();
    },
};