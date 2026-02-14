export default {
    components: {
        'limit-date-navbar': () => import('../navbar/LimitDate.js'),
        'commission-member': () => import('../navbar/CommissionMember.js'),
        'matchs-list': () => import('../list/Matchs.js'),
        'tournament-bracket-viewer': () => import('./TournamentBracketViewer.js'),
    },
    template: `
      <div>
        <div class="flex flex-wrap gap-4 mb-4">
          <limit-date-navbar :key="'navbar-' + code_competition" :code_competition="code_competition" class="flex-1"></limit-date-navbar>
          <commission-member :key="'commission-' + code_competition + '-' + division" :code_competition="code_competition" :division="division" class="flex-none"></commission-member>
        </div>
        
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else>
          <!-- Onglets pour choisir la vue -->
          <div class="tabs tabs-boxed mb-4">
            <a class="tab" :class="{ 'tab-active': viewMode === 'draw' }" @click="viewMode = 'draw'">
              <i class="fas fa-sitemap mr-2"></i>Tableau des phases finales
            </a>
            <a class="tab" :class="{ 'tab-active': viewMode === 'bracket' }" @click="viewMode = 'bracket'">
              <i class="fas fa-project-diagram mr-2"></i>Arbre (brackets-viewer)
            </a>
            <a class="tab" :class="{ 'tab-active': viewMode === 'list' }" @click="viewMode = 'list'">
              <i class="fas fa-list mr-2"></i>Liste des matchs
            </a>
          </div>

          <!-- Vue tableau des phases finales (tirage résolu) -->
          <div v-if="viewMode === 'draw'" class="mb-6">
            <div v-if="!drawData || !drawData.rounds || !drawData.rounds['1_8'] || drawData.rounds['1_8'].length === 0" class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <span>Le tirage au sort des phases finales n'a pas encore été saisi.</span>
            </div>
            <div v-else>
              <h2 class="text-xl font-bold mb-4">1/8 de finale</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="match in drawData.rounds['1_8']" :key="'match-' + match.match"
                     class="card bg-base-200 shadow-md">
                  <div class="card-body p-4">
                    <h3 class="card-title text-sm text-base-content/60">Match {{ match.match }}</h3>
                    <div class="flex items-center justify-between gap-4">
                      <div class="flex-1 text-center p-3 rounded-lg" :class="match.team1_resolved ? 'bg-primary/10' : 'bg-warning/10'">
                        <div class="font-bold" :class="match.team1_resolved ? 'text-primary' : 'text-warning'">
                          {{ match.team1_resolved ? match.team1_resolved.nom_equipe : match.team1_label }}
                        </div>
                        <div v-if="match.team1_resolved" class="text-xs text-base-content/50 mt-1">{{ match.team1_label }}</div>
                      </div>
                      <div class="font-bold text-lg">VS</div>
                      <div class="flex-1 text-center p-3 rounded-lg" :class="match.team2_resolved ? 'bg-secondary/10' : 'bg-warning/10'">
                        <div class="font-bold" :class="match.team2_resolved ? 'text-secondary' : 'text-warning'">
                          {{ match.team2_resolved ? match.team2_resolved.nom_equipe : match.team2_label }}
                        </div>
                        <div v-if="match.team2_resolved" class="text-xs text-base-content/50 mt-1">{{ match.team2_label }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Admin link -->
              <div v-if="isAdmin" class="mt-4">
                <router-link :to="'/finals-draw-admin/' + code_competition" class="btn btn-outline btn-sm">
                  <i class="fas fa-edit mr-2"></i>Modifier le tirage
                </router-link>
              </div>
            </div>
          </div>

          <!-- Vue arbre de tournoi -->
          <div v-if="viewMode === 'bracket'" class="mb-6">
            <tournament-bracket-viewer 
              :matches="finalsMatches" 
              :tournament-type="'single_elimination'"
              :key="'bracket-viewer-' + code_competition + '-' + division"
            />
          </div>

          <!-- Vue liste traditionnelle -->
          <div v-if="viewMode === 'list'">
            <matchs-list :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-list>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            division: 1,
            code_competition: this.$route.params.code_competition,
            viewMode: 'draw',
            finalsMatches: [],
            drawData: null,
            loading: true,
            user: null,
        };
    },
    computed: {
        isAdmin() {
            return this.user && this.user.profile_name === 'ADMINISTRATEUR';
        },
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getMatches?competition=${this.code_competition}&division=${this.division}`;
        }
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                this.code_competition = newParams.code_competition;
                this.fetchDrawData();
            },
            immediate: true
        }
    },
    methods: {
        fetchDrawData() {
            this.loading = true;
            axios.get(`/rest/action.php/rank/getFinalsDrawResolved?code_competition_finals=${this.code_competition}`)
                .then(response => {
                    this.drawData = response.data;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du tirage:', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchFinalsMatches() {
            axios.get(this.matchesFetchUrl)
                .then(response => {
                    // Filtrer les matchs de phases finales
                    this.finalsMatches = response.data.filter(match => {
                        const journee = match.journee?.toLowerCase() || '';
                        return journee.includes('finale') || 
                               journee.includes('1/8') || 
                               journee.includes('1/4') || 
                               journee.includes('1/2') ||
                               journee.includes('quart') ||
                               journee.includes('demi');
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des matchs de phases finales:', error);
                });
        },
        fetchUserDetails() {
            axios.get('/session_user.php')
                .then(response => {
                    if (!response.data.error) {
                        this.user = response.data;
                    }
                })
                .catch(() => {
                    this.user = null;
                });
        },
    },
    created() {
        this.fetchUserDetails();
        this.fetchDrawData();
        this.fetchFinalsMatches();
    }
};
