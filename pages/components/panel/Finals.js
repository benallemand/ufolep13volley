import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'limit-date-navbar': defineAsyncComponent(() => import('../navbar/LimitDate.js')),
        'commission-member': defineAsyncComponent(() => import('../navbar/CommissionMember.js')),
        'matchs-list': defineAsyncComponent(() => import('../list/Matchs.js')),
        'tournament-bracket-viewer': defineAsyncComponent(() => import('./TournamentBracketViewer.js')),
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
                    (parseInt(m.id_equipe_dom) === parseInt(t1) && parseInt(m.id_equipe_ext) === parseInt(t2)) ||
                    (parseInt(m.id_equipe_dom) === parseInt(t2) && parseInt(m.id_equipe_ext) === parseInt(t1))
                ) : null;

                if (realMatch) {
                    // Vrai match inséré : on conserve toutes ses données (date, gymnase, score)
                    matches.push({
                        ...realMatch,
                        journee: '1/8 de finale',
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

            // 1/4 de finale (placeholders avec info de réception et noms des vainqueurs si connus)
            for (let i = 0; i < 4; i++) {
                const quarterNum = i + 1;
                const winner1 = 2 * i + 1;
                const winner2 = 2 * i + 2;
                const hostWinner = hostDraw['1_4'] ? hostDraw['1_4'][quarterNum] : null;
                const team1IsHost = hostWinner === winner1;
                const team2IsHost = hostWinner === winner2;
                
                const team1Name = this.getWinnerName(winner1, '1/8');
                const team2Name = this.getWinnerName(winner2, '1/8');
                
                // Chercher un vrai match dont une équipe vient du 1/8 #winner1 et l'autre du 1/8 #winner2
                const ids1 = this.getEighthTeamIds(winner1 - 1);
                const ids2 = this.getEighthTeamIds(winner2 - 1);
                const realQuarterMatch = this.findRealMatch(ids1, ids2);
                
                if (realQuarterMatch) {
                    // Vrai match trouvé : equipe_dom est déjà l'équipe qui reçoit
                    matches.push({
                        ...realQuarterMatch,
                        journee: '1/4 de finale',
                        equipe_dom: '🏠 ' + realQuarterMatch.equipe_dom,
                        equipe_ext: realQuarterMatch.equipe_ext,
                    });
                } else {
                    // Pas de vrai match : placeholder
                    matches.push({
                        id_match: 2000 + i,
                        journee: '1/4 de finale',
                        equipe_dom: (team1IsHost ? '🏠 ' : '') + (team1Name || 'Vainqueur 1/8 #' + winner1),
                        equipe_ext: (team2IsHost ? '🏠 ' : '') + (team2Name || 'Vainqueur 1/8 #' + winner2),
                    });
                }
            }

            // 1/2 finale
            // Chaque 1/2 oppose le vainqueur de 2 quarts de finale consécutifs
            // 1/2 #1 : vainqueur 1/4 #1 vs vainqueur 1/4 #2
            // 1/2 #2 : vainqueur 1/4 #3 vs vainqueur 1/4 #4
            for (let i = 0; i < 2; i++) {
                const semiNum = i + 1;
                const quarter1 = 2 * i + 1; // 1/4 #1 ou #3
                const quarter2 = 2 * i + 2; // 1/4 #2 ou #4
                const hostWinner = hostDraw['1_2'] ? hostDraw['1_2'][semiNum] : null;
                const team1IsHost = hostWinner === quarter1;
                const team2IsHost = hostWinner === quarter2;
                
                // Collecter les IDs de toutes les équipes possibles pour chaque 1/4
                // 1/4 #N oppose les équipes des 1/8 #(2N-1) et #(2N)
                const q1_eighth1 = (quarter1 - 1) * 2; // index des 1/8 pour le 1/4 #quarter1
                const q1_eighth2 = q1_eighth1 + 1;
                const q2_eighth1 = (quarter2 - 1) * 2; // index des 1/8 pour le 1/4 #quarter2
                const q2_eighth2 = q2_eighth1 + 1;
                
                const idsQuarter1 = new Set([
                    ...this.getEighthTeamIds(q1_eighth1),
                    ...this.getEighthTeamIds(q1_eighth2)
                ]);
                const idsQuarter2 = new Set([
                    ...this.getEighthTeamIds(q2_eighth1),
                    ...this.getEighthTeamIds(q2_eighth2)
                ]);
                
                const realSemiMatch = this.findRealMatch(idsQuarter1, idsQuarter2);
                
                if (realSemiMatch) {
                    matches.push({
                        ...realSemiMatch,
                        journee: '1/2 finale',
                        equipe_dom: '🏠 ' + realSemiMatch.equipe_dom,
                        equipe_ext: realSemiMatch.equipe_ext,
                    });
                } else {
                    // Placeholder : chercher les noms des vainqueurs des 1/4 si disponibles
                    const team1Name = this.getQuarterWinnerName(quarter1);
                    const team2Name = this.getQuarterWinnerName(quarter2);
                    matches.push({
                        id_match: 3000 + i,
                        journee: '1/2 finale',
                        equipe_dom: (team1IsHost ? '🏠 ' : '') + (team1Name || 'Vainqueur 1/4 #' + quarter1),
                        equipe_ext: (team2IsHost ? '🏠 ' : '') + (team2Name || 'Vainqueur 1/4 #' + quarter2),
                    });
                }
            }

            // Finale
            // Oppose les vainqueurs des 2 demi-finales
            // Les équipes possibles pour la 1/2 #1 viennent des 1/4 #1 et #2 (= 1/8 #1-4)
            // Les équipes possibles pour la 1/2 #2 viennent des 1/4 #3 et #4 (= 1/8 #5-8)
            const idsSemiTop = new Set([
                ...this.getEighthTeamIds(0), ...this.getEighthTeamIds(1),
                ...this.getEighthTeamIds(2), ...this.getEighthTeamIds(3)
            ]);
            const idsSemiBottom = new Set([
                ...this.getEighthTeamIds(4), ...this.getEighthTeamIds(5),
                ...this.getEighthTeamIds(6), ...this.getEighthTeamIds(7)
            ]);
            const realFinalMatch = this.findRealMatch(idsSemiTop, idsSemiBottom);
            
            if (realFinalMatch) {
                matches.push({
                    ...realFinalMatch,
                    journee: 'Finale',
                    equipe_dom: '🏠 ' + realFinalMatch.equipe_dom,
                    equipe_ext: realFinalMatch.equipe_ext,
                });
            } else {
                const finalist1Name = this.getSemiWinnerName(1);
                const finalist2Name = this.getSemiWinnerName(2);
                matches.push({
                    id_match: 4000,
                    journee: 'Finale',
                    equipe_dom: finalist1Name || 'Vainqueur 1/2 #1',
                    equipe_ext: finalist2Name || 'Vainqueur 1/2 #2',
                });
            }

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
        // Trouve un vrai match en base entre 2 ensembles d'IDs d'équipes
        findRealMatch(ids1, ids2) {
            return this.finalsMatches.find(m => {
                const domId = parseInt(m.id_equipe_dom);
                const extId = parseInt(m.id_equipe_ext);
                return (ids1.has(domId) && ids2.has(extId)) || (ids2.has(domId) && ids1.has(extId));
            }) || null;
        },

        // Retourne les IDs des 2 équipes d'un match de 1/8 du tirage
        getEighthTeamIds(eighthIndex) {
            const draw = this.drawData?.rounds?.['1_8']?.[eighthIndex];
            if (!draw) return new Set();
            const ids = new Set();
            if (draw.team1_resolved) ids.add(parseInt(draw.team1_resolved.id_equipe));
            if (draw.team2_resolved) ids.add(parseInt(draw.team2_resolved.id_equipe));
            return ids;
        },

        // Retourne le nom du vainqueur d'un match en se basant sur score_equipe_dom/ext
        getMatchWinner(match) {
            if (!match) return null;
            const dom = parseInt(match.score_equipe_dom);
            const ext = parseInt(match.score_equipe_ext);
            if (isNaN(dom) || isNaN(ext) || (dom === 0 && ext === 0)) return null;
            if (dom > ext) return match.equipe_dom;
            if (ext > dom) return match.equipe_ext;
            return null;
        },

        // Retourne le nom du vainqueur d'un 1/4 de finale si terminé
        getQuarterWinnerName(quarterNum) {
            const eighth1Index = (quarterNum - 1) * 2;
            const eighth2Index = eighth1Index + 1;
            const ids1 = this.getEighthTeamIds(eighth1Index);
            const ids2 = this.getEighthTeamIds(eighth2Index);
            return this.getMatchWinner(this.findRealMatch(ids1, ids2));
        },

        // Retourne le nom du vainqueur d'une 1/2 finale si terminée
        getSemiWinnerName(semiNum) {
            const q1 = (semiNum - 1) * 2; // index 1/8 pour le 1er quart
            const q2 = q1 + 2;            // index 1/8 pour le 2e quart
            const idsQuarter1 = new Set([...this.getEighthTeamIds(q1), ...this.getEighthTeamIds(q1 + 1)]);
            const idsQuarter2 = new Set([...this.getEighthTeamIds(q2), ...this.getEighthTeamIds(q2 + 1)]);
            return this.getMatchWinner(this.findRealMatch(idsQuarter1, idsQuarter2));
        },

        // Retourne le nom du vainqueur d'un match de 1/8 si terminé
        getWinnerName(matchNumber, round) {
            if (round !== '1/8') return null;
            const ids1 = this.getEighthTeamIds(matchNumber - 1);
            if (!ids1.size) return null;
            // Pour un 1/8, les 2 équipes sont dans le même match du tirage
            // On cherche un match dont les 2 équipes viennent de ce même set
            const realMatch = this.finalsMatches.find(m => {
                const domId = parseInt(m.id_equipe_dom);
                const extId = parseInt(m.id_equipe_ext);
                return ids1.has(domId) && ids1.has(extId);
            });
            return this.getMatchWinner(realMatch);
        },

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
