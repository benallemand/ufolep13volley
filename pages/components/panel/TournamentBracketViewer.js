export default {
    template: `
      <div>
        <!-- Chargement des ressources brackets-viewer.js -->
        <div v-if="!libraryLoaded" class="flex justify-center items-center p-8">
          <div class="loading loading-spinner loading-lg"></div>
          <span class="ml-2">Chargement de l'arbre de tournoi...</span>
        </div>
        
        <!-- Container pour brackets-viewer -->
        <div 
          :id="containerId" 
          class="brackets-viewer"
          v-show="libraryLoaded"
        ></div>
        
        <!-- Message si pas de données -->
        <div v-if="libraryLoaded && !hasValidData" class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <span>Aucune donnée de phase finale disponible pour le moment.</span>
        </div>

        <!-- Modal pour les détails du match -->
        <div v-if="selectedMatch" class="modal modal-open">
          <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4">Détails du match</h3>
            
            <!-- Informations générales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div class="text-center">
                <h4 class="font-semibold text-lg">{{ selectedMatch.equipe_dom }}</h4>
                <div class="text-3xl font-bold" :class="{ 'text-success': isWinnerTeam(selectedMatch, 'dom') }">
                  {{ selectedMatch.score_equipe_dom || 0 }}
                </div>
              </div>
              <div class="text-center">
                <h4 class="font-semibold text-lg">{{ selectedMatch.equipe_ext }}</h4>
                <div class="text-3xl font-bold" :class="{ 'text-success': isWinnerTeam(selectedMatch, 'ext') }">
                  {{ selectedMatch.score_equipe_ext || 0 }}
                </div>
              </div>
            </div>

            <!-- Scores des sets -->
            <div v-if="hasSetScores(selectedMatch)" class="mb-6">
              <h4 class="font-semibold mb-3">Scores par set</h4>
              <div class="overflow-x-auto">
                <table class="table table-compact w-full">
                  <thead>
                    <tr>
                      <th>Set</th>
                      <th class="text-center">{{ selectedMatch.equipe_dom }}</th>
                      <th class="text-center">{{ selectedMatch.equipe_ext }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(set, index) in getSetDetails(selectedMatch)" :key="index">
                      <td class="font-medium">Set {{ index + 1 }}</td>
                      <td class="text-center font-bold" 
                          :class="{ 'text-success': set.domWin, 'text-error': !set.domWin && set.extWin }">
                        {{ set.domScore }}
                      </td>
                      <td class="text-center font-bold" 
                          :class="{ 'text-success': set.extWin, 'text-error': !set.extWin && set.domWin }">
                        {{ set.extScore }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Informations supplémentaires -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div v-if="selectedMatch.date_reception_raw">
                <strong>Date :</strong> {{ formatMatchDate(selectedMatch.date_reception_raw) }}
              </div>
              <div v-if="selectedMatch.heure_reception">
                <strong>Heure :</strong> {{ selectedMatch.heure_reception }}
              </div>
              <div v-if="selectedMatch.gymnasium">
                <strong>Lieu :</strong> {{ selectedMatch.gymnasium }}
              </div>
              <div v-if="selectedMatch.journee">
                <strong>Phase :</strong> {{ selectedMatch.journee }}
              </div>
            </div>

            <!-- Actions -->
            <div class="modal-action">
              <button class="btn" @click="closeMatchDetails">Fermer</button>
            </div>
          </div>
          <div class="modal-backdrop" @click="closeMatchDetails"></div>
        </div>
      </div>
    `,
    props: {
        matches: {
            type: Array,
            default: () => []
        },
        tournamentType: {
            type: String,
            default: 'single_elimination'
        }
    },
    data() {
        return {
            libraryLoaded: false,
            containerId: `bracket-container-${Math.random().toString(36).substr(2, 9)}`,
            hasValidData: false,
            selectedMatch: null
        };
    },
    watch: {
        matches: {
            handler() {
                if (this.libraryLoaded) {
                    this.renderBracket();
                }
            },
            deep: true
        }
    },
    async mounted() {
        await this.loadBracketsViewer();
        this.renderBracket();
    },
    methods: {
        async loadBracketsViewer() {
            try {
                // Charger le CSS
                if (!document.querySelector('link[href*="brackets-viewer"]')) {
                    const cssLink = document.createElement('link');
                    cssLink.rel = 'stylesheet';
                    cssLink.href = 'https://cdn.jsdelivr.net/npm/brackets-viewer@latest/dist/brackets-viewer.min.css';
                    document.head.appendChild(cssLink);
                }

                // Charger le JS si pas déjà chargé
                if (!window.bracketsViewer) {
                    await this.loadScript('https://cdn.jsdelivr.net/npm/brackets-viewer@latest/dist/brackets-viewer.min.js');
                }

                this.libraryLoaded = true;
            } catch (error) {
                console.error('Erreur lors du chargement de brackets-viewer:', error);
            }
        },

        loadScript(src) {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        renderBracket() {
            if (!this.libraryLoaded || !window.bracketsViewer || !this.matches.length) {
                this.hasValidData = false;
                return;
            }

            try {
                const bracketData = this.convertToBracketFormat();
                
                if (!bracketData.participants.length || !bracketData.matches.length) {
                    this.hasValidData = false;
                    return;
                }

                this.hasValidData = true;

                // Render avec brackets-viewer
                window.bracketsViewer.render(bracketData, {
                    selector: `#${this.containerId}`,
                    clear: true,
                    customRoundName: (info) => {
                        // Personnaliser les noms des rounds
                        const roundNames = {
                            1: '1/8 de finale',
                            2: '1/4 de finale', 
                            3: '1/2 finale',
                            4: 'Finale'
                        };
                        return roundNames[info.roundNumber] || `Round ${info.roundNumber}`;
                    },
                    onMatchClick: (match) => {
                        // Action lors du clic sur un match
                        this.showMatchDetails(match);
                    }
                });

            } catch (error) {
                console.error('Erreur lors du rendu du bracket:', error);
                this.hasValidData = false;
            }
        },

        convertToBracketFormat() {
            // Extraire les participants uniques
            const participantsSet = new Set();
            this.matches.forEach(match => {
                if (match.equipe_dom) participantsSet.add(match.equipe_dom);
                if (match.equipe_ext) participantsSet.add(match.equipe_ext);
            });

            const participants = Array.from(participantsSet).map((name, index) => ({
                id: index + 1,
                tournament_id: 1,
                name: name
            }));

            // Créer un mapping nom -> id
            const nameToId = {};
            participants.forEach(p => {
                nameToId[p.name] = p.id;
            });

            // Convertir les matchs
            const matches = this.matches.map((match, index) => {
                const opponent1 = match.equipe_dom ? {
                    id: nameToId[match.equipe_dom],
                    score: this.getMatchScore(match, 'dom'),
                    result: this.getMatchResult(match, 'dom')
                } : null;

                const opponent2 = match.equipe_ext ? {
                    id: nameToId[match.equipe_ext], 
                    score: this.getMatchScore(match, 'ext'),
                    result: this.getMatchResult(match, 'ext')
                } : null;

                return {
                    id: index + 1,
                    number: index + 1,
                    stage_id: 1,
                    group_id: 1,
                    round_id: this.getRoundId(match.journee),
                    child_count: 0,
                    status: this.getMatchStatus(match),
                    opponent1: opponent1,
                    opponent2: opponent2
                };
            });

            // Créer la structure de stage
            const stages = [{
                id: 1,
                tournament_id: 1,
                name: 'Phase finale',
                type: 'single_elimination',
                number: 1,
                settings: {}
            }];

            return {
                stages: stages,
                matches: matches,
                matchGames: [], // Pas de games séparés pour le volleyball
                participants: participants
            };
        },

        getRoundId(journee) {
            if (!journee) return 1;
            const journeeLower = journee.toLowerCase();
            
            if (journeeLower.includes('1/8')) return 1;
            if (journeeLower.includes('1/4') || journeeLower.includes('quart')) return 2;
            if (journeeLower.includes('1/2') || journeeLower.includes('demi')) return 3;
            if (journeeLower.includes('finale')) return 4;
            
            return 1;
        },

        getMatchScore(match, team) {
            if (team === 'dom') {
                return match.score_equipe_dom !== undefined ? match.score_equipe_dom : null;
            } else {
                return match.score_equipe_ext !== undefined ? match.score_equipe_ext : null;
            }
        },

        getMatchResult(match, team) {
            if (match.score_equipe_dom === undefined || match.score_equipe_ext === undefined) {
                return null;
            }

            const scoreDom = parseInt(match.score_equipe_dom);
            const scoreExt = parseInt(match.score_equipe_ext);

            if (scoreDom === scoreExt) return null; // Égalité (pas normal en volleyball)

            if (team === 'dom') {
                return scoreDom > scoreExt ? 'win' : 'loss';
            } else {
                return scoreExt > scoreDom ? 'win' : 'loss';
            }
        },

        getMatchStatus(match) {
            if (match.score_equipe_dom !== undefined && match.score_equipe_ext !== undefined) {
                return 4; // Completed
            }
            return 2; // Ready
        },

        showMatchDetails(bracketMatch) {
            // Retrouver le match original UFOLEP à partir du match brackets-viewer
            const originalMatch = this.findOriginalMatch(bracketMatch);
            if (originalMatch) {
                this.selectedMatch = originalMatch;
            }
        },

        findOriginalMatch(bracketMatch) {
            // Trouver le match original basé sur les participants
            if (!bracketMatch.opponent1 || !bracketMatch.opponent2) return null;
            
            const participant1Name = this.getParticipantName(bracketMatch.opponent1.id);
            const participant2Name = this.getParticipantName(bracketMatch.opponent2.id);
            
            return this.matches.find(match => 
                (match.equipe_dom === participant1Name && match.equipe_ext === participant2Name) ||
                (match.equipe_dom === participant2Name && match.equipe_ext === participant1Name)
            );
        },

        getParticipantName(participantId) {
            // Retrouver le nom du participant à partir de son ID
            const participantsSet = new Set();
            this.matches.forEach(match => {
                if (match.equipe_dom) participantsSet.add(match.equipe_dom);
                if (match.equipe_ext) participantsSet.add(match.equipe_ext);
            });

            const participants = Array.from(participantsSet);
            return participants[participantId - 1]; // Les IDs commencent à 1
        },

        closeMatchDetails() {
            this.selectedMatch = null;
        },

        isWinnerTeam(match, team) {
            if (match.score_equipe_dom === undefined || match.score_equipe_ext === undefined) return false;
            
            const scoreDom = parseInt(match.score_equipe_dom);
            const scoreExt = parseInt(match.score_equipe_ext);
            
            if (scoreDom === scoreExt) return false;

            if (team === 'dom') {
                return scoreDom > scoreExt;
            } else {
                return scoreExt > scoreDom;
            }
        },

        hasSetScores(match) {
            return match.set_1_dom !== undefined && match.set_1_ext !== undefined &&
                   (match.set_1_dom > 0 || match.set_1_ext > 0);
        },

        getSetDetails(match) {
            const sets = [];
            for (let i = 1; i <= 5; i++) {
                const domScore = match[`set_${i}_dom`];
                const extScore = match[`set_${i}_ext`];
                
                if (domScore !== undefined && extScore !== undefined && (domScore > 0 || extScore > 0)) {
                    sets.push({
                        domScore: domScore,
                        extScore: extScore,
                        domWin: domScore > extScore,
                        extWin: extScore > domScore
                    });
                }
            }
            return sets;
        },

        formatMatchDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('fr-FR', {
                weekday: 'long',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    },

    beforeUnmount() {
        // Nettoyer le container si nécessaire
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = '';
        }
    }
};
