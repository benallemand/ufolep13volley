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
          <!-- Admin link (toujours visible pour les admins) -->
          <div v-if="isAdmin" class="mb-4">
            <router-link :to="'/finals-draw-admin/' + code_competition" class="btn btn-outline btn-sm">
              <i class="fas fa-edit mr-2"></i>Saisir / Modifier le tirage
            </router-link>
          </div>

          <!-- Onglets pour choisir la vue -->
          <div class="tabs tabs-boxed mb-4">
            <a class="tab" :class="{ 'tab-active': viewMode === 'bracket' }" @click="viewMode = 'bracket'">
              <i class="fas fa-project-diagram mr-2"></i>Arbre du tournoi
            </a>
            <a class="tab" :class="{ 'tab-active': viewMode === 'list' }" @click="viewMode = 'list'">
              <i class="fas fa-list mr-2"></i>Liste des matchs
            </a>
          </div>

          <!-- Vue arbre de tournoi (brackets-viewer) -->
          <div v-if="viewMode === 'bracket'" class="mb-6">
            <div v-if="!bracketMatches.length" class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <span>Le tirage au sort des phases finales n'a pas encore été saisi.</span>
            </div>
            <tournament-bracket-viewer 
              v-else
              :matches="bracketMatches" 
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
            viewMode: 'bracket',
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
        },
        bracketMatches() {
            // Combine real matches with draw data for bracket display
            // Priority: use real matches if they exist, otherwise use draw data
            
            // If we have real finals matches, use them
            if (this.finalsMatches && this.finalsMatches.length > 0) {
                return this.finalsMatches;
            }
            
            // Otherwise, convert draw data to bracket format
            if (!this.drawData || !this.drawData.rounds || !this.drawData.rounds['1_8']) {
                return [];
            }
            
            // Convert draw data to match format for brackets-viewer
            const matches = [];
            
            // 1/8 finals from draw - team name only, label for tooltip
            this.drawData.rounds['1_8'].forEach((match, index) => {
                matches.push({
                    id_match: 1000 + index,
                    journee: '1/8 de finale',
                    equipe_dom: match.team1_resolved ? match.team1_resolved.nom_equipe : match.team1_label,
                    equipe_ext: match.team2_resolved ? match.team2_resolved.nom_equipe : match.team2_label,
                    id_equipe_dom: match.team1_resolved ? match.team1_resolved.id_equipe : null,
                    id_equipe_ext: match.team2_resolved ? match.team2_resolved.id_equipe : null,
                    tooltip_dom: match.team1_label,
                    tooltip_ext: match.team2_label,
                });
            });
            
            // Add placeholder matches for 1/4, 1/2, finale
            for (let i = 0; i < 4; i++) {
                matches.push({
                    id_match: 2000 + i,
                    journee: '1/4 de finale',
                    equipe_dom: 'Vainqueur 1/8 #' + (2*i + 1),
                    equipe_ext: 'Vainqueur 1/8 #' + (2*i + 2),
                });
            }
            
            for (let i = 0; i < 2; i++) {
                matches.push({
                    id_match: 3000 + i,
                    journee: '1/2 finale',
                    equipe_dom: 'Vainqueur 1/4 #' + (2*i + 1),
                    equipe_ext: 'Vainqueur 1/4 #' + (2*i + 2),
                });
            }
            
            matches.push({
                id_match: 4000,
                journee: 'Finale',
                equipe_dom: 'Vainqueur 1/2 #1',
                equipe_ext: 'Vainqueur 1/2 #2',
            });
            
            return matches;
        }
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                this.code_competition = newParams.code_competition;
                this.fetchData();
            },
            immediate: true
        }
    },
    methods: {
        fetchData() {
            this.loading = true;
            Promise.all([
                this.fetchDrawData(),
                this.fetchFinalsMatches()
            ]).finally(() => {
                this.loading = false;
            });
        },
        fetchDrawData() {
            return axios.get(`/rest/action.php/rank/getFinalsDrawResolved?code_competition_finals=${this.code_competition}`)
                .then(response => {
                    this.drawData = response.data;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du tirage:', error);
                });
        },
        fetchFinalsMatches() {
            return axios.get(this.matchesFetchUrl)
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
    }
};
