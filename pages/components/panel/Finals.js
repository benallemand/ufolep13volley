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
            // Toujours construire la structure depuis le tirage,
            // et enrichir les 1/8 avec les vrais matchs insérés quand ils existent.
            if (!this.drawData || !this.drawData.rounds || !this.drawData.rounds['1_8']) {
                return [];
            }

            const matches = [];
            const hostDraw = this.drawData.host_draw || { '1_4': {}, '1_2': {} };

            // 1/8 de finale : tirage + enrichissement avec vrais matchs
            this.drawData.rounds['1_8'].forEach((drawMatch, index) => {
                const t1 = drawMatch.team1_resolved ? drawMatch.team1_resolved.id_equipe : null;
                const t2 = drawMatch.team2_resolved ? drawMatch.team2_resolved.id_equipe : null;

                // Chercher le vrai match correspondant par id_equipe
                // (pas de filtre sur journee : les matchs insérés peuvent avoir journee=null)
                const realMatch = (t1 && t2) ? this.finalsMatches.find(m =>
                    (m.id_equipe_dom === t1 && m.id_equipe_ext === t2) ||
                    (m.id_equipe_dom === t2 && m.id_equipe_ext === t1)
                ) : null;

                if (realMatch) {
                    // Vrai match inséré : on conserve toutes ses données (date, gymnase, score)
                    // et on ajoute les labels du tirage comme tooltips
                    matches.push({
                        ...realMatch,
                        equipe_dom: '🏠 ' + realMatch.equipe_dom,
                        tooltip_dom: drawMatch.team1_label,
                        tooltip_ext: drawMatch.team2_label,
                    });
                } else {
                    // Pas encore de match inséré : placeholder depuis le tirage
                    const team1Display = drawMatch.team1_resolved
                        ? '🏠 ' + drawMatch.team1_resolved.nom_equipe
                        : '🏠 ' + drawMatch.team1_label;
                    const team2Display = drawMatch.team2_resolved
                        ? drawMatch.team2_resolved.nom_equipe
                        : drawMatch.team2_label;
                    matches.push({
                        id_match: 1000 + index,
                        journee: '1/8 de finale',
                        equipe_dom: team1Display,
                        equipe_ext: team2Display,
                        id_equipe_dom: t1,
                        id_equipe_ext: t2,
                        tooltip_dom: drawMatch.team1_resolved ? drawMatch.team1_label : null,
                        tooltip_ext: drawMatch.team2_resolved ? drawMatch.team2_label : null,
                    });
                }
            });

            // 1/4 de finale (placeholders avec info de réception)
            for (let i = 0; i < 4; i++) {
                const quarterNum = i + 1;
                const winner1 = 2 * i + 1;
                const winner2 = 2 * i + 2;
                const hostWinner = hostDraw['1_4'] ? hostDraw['1_4'][quarterNum] : null;
                const team1IsHost = hostWinner === winner1;
                const team2IsHost = hostWinner === winner2;
                matches.push({
                    id_match: 2000 + i,
                    journee: '1/4 de finale',
                    equipe_dom: (team1IsHost ? '🏠 ' : '') + 'Vainqueur 1/8 #' + winner1,
                    equipe_ext: (team2IsHost ? '🏠 ' : '') + 'Vainqueur 1/8 #' + winner2,
                });
            }

            // 1/2 finale (placeholders avec info de réception)
            for (let i = 0; i < 2; i++) {
                const semiNum = i + 1;
                const winner1 = 2 * i + 1;
                const winner2 = 2 * i + 2;
                const hostWinner = hostDraw['1_2'] ? hostDraw['1_2'][semiNum] : null;
                const team1IsHost = hostWinner === winner1;
                const team2IsHost = hostWinner === winner2;
                matches.push({
                    id_match: 3000 + i,
                    journee: '1/2 finale',
                    equipe_dom: (team1IsHost ? '🏠 ' : '') + 'Vainqueur 1/4 #' + winner1,
                    equipe_ext: (team2IsHost ? '🏠 ' : '') + 'Vainqueur 1/4 #' + winner2,
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
            // L'endpoint filtre déjà par competition=kf/cf, tous les matchs retournés
            // sont des matchs de phases finales. Pas de filtre supplémentaire par journée
            // car les matchs insérés peuvent avoir journee=null.
            return axios.get(this.matchesFetchUrl)
                .then(response => {
                    this.finalsMatches = response.data;
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
