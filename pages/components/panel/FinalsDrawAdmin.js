export default {
    template: `
      <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">
          <i class="fas fa-edit mr-2"></i>
          Tirage au sort des phases finales - {{ competitionLabel }}
        </h1>
        
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        
        <div v-else>
          <div class="alert alert-info mb-6">
            <i class="fas fa-info-circle"></i>
            <div>
              <strong>Saisie du tirage au sort des 1/8 de finale</strong>
              <p class="text-sm">Sélectionnez les positions qualifiées pour chaque match. Les noms d'équipes seront résolus automatiquement à partir des classements de poules.</p>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div v-for="matchNum in 8" :key="'match-' + matchNum"
                 class="card bg-base-200 shadow-md">
              <div class="card-body p-4">
                <h3 class="card-title text-sm">Match {{ matchNum }}</h3>
                <div class="flex flex-col gap-2">
                  <select class="select select-bordered w-full" 
                          v-model="draw[matchNum].team1"
                          @change="markDirty">
                    <option value="">-- Équipe 1 --</option>
                    <option v-for="pos in availablePositions" :key="'t1-' + matchNum + '-' + pos" :value="pos">
                      {{ pos }}
                    </option>
                  </select>
                  <div class="text-center font-bold text-sm">VS</div>
                  <select class="select select-bordered w-full" 
                          v-model="draw[matchNum].team2"
                          @change="markDirty">
                    <option value="">-- Équipe 2 --</option>
                    <option v-for="pos in availablePositions" :key="'t2-' + matchNum + '-' + pos" :value="pos">
                      {{ pos }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Résumé des positions utilisées -->
          <div class="mb-6">
            <div class="collapse collapse-arrow bg-base-200">
              <input type="checkbox" />
              <div class="collapse-title font-medium">
                Positions utilisées : {{ usedPositions.length }} / {{ availablePositions.length }}
              </div>
              <div class="collapse-content">
                <div class="flex flex-wrap gap-2">
                  <span v-for="pos in availablePositions" :key="'used-' + pos"
                        class="badge" :class="usedPositions.includes(pos) ? 'badge-success' : 'badge-ghost'">
                    {{ pos }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Boutons d'action -->
          <div class="flex gap-2">
            <button class="btn btn-primary" @click="saveDraw" :disabled="saving">
              <span v-if="saving" class="loading loading-spinner loading-sm"></span>
              <i v-else class="fas fa-save mr-2"></i>
              Sauvegarder le tirage
            </button>
            <router-link :to="'/finals/' + code_competition" class="btn btn-ghost">
              <i class="fas fa-arrow-left mr-2"></i>Retour
            </router-link>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            code_competition: this.$route.params.code_competition,
            loading: true,
            saving: false,
            isDirty: false,
            draw: {},
            qualifiedPositions: [],
            competitionLabel: '',
        };
    },
    computed: {
        availablePositions() {
            return this.qualifiedPositions;
        },
        usedPositions() {
            const used = [];
            for (let i = 1; i <= 8; i++) {
                if (this.draw[i]?.team1) used.push(this.draw[i].team1);
                if (this.draw[i]?.team2) used.push(this.draw[i].team2);
            }
            return used;
        }
    },
    methods: {
        initDraw() {
            const draw = {};
            for (let i = 1; i <= 8; i++) {
                draw[i] = { team1: '', team2: '' };
            }
            this.draw = draw;
        },
        fetchData() {
            this.loading = true;
            
            // Determine parent competition for generating positions
            const parentCode = this.code_competition === 'cf' ? 'c' : 'kh';
            const hasTableau = this.code_competition === 'cf';
            this.competitionLabel = this.code_competition === 'cf' ? 'Coupe Isoardi' : 'Coupe Khoury Hanna';
            
            // Fetch number of pools to generate position labels
            const poolsPromise = axios.get(`/rest/action.php/rank/getDivisionsFromCompetition?code_competition=${parentCode}`);
            // Fetch existing draw
            const drawPromise = axios.get(`/rest/action.php/rank/getFinalsDrawRaw?code_competition_finals=${this.code_competition}`);
            
            Promise.all([poolsPromise, drawPromise])
                .then(([poolsResponse, drawResponse]) => {
                    const nbPools = poolsResponse.data.length;
                    this.generatePositions(nbPools, hasTableau);
                    
                    // Load existing draw if any
                    const existingDraw = drawResponse.data;
                    if (existingDraw && Object.keys(existingDraw).length > 0) {
                        for (const [matchNum, match] of Object.entries(existingDraw)) {
                            const num = parseInt(matchNum);
                            if (this.draw[num]) {
                                this.draw[num].team1 = match.team1 || '';
                                this.draw[num].team2 = match.team2 || '';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement:', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        generatePositions(nbPools, hasTableau) {
            const positions = [];
            const nbQualified = 16;
            const nbBestSeconds = Math.max(0, nbQualified - nbPools);
            
            for (let i = 1; i <= nbPools; i++) {
                positions.push(`1er poule ${i}`);
            }
            for (let i = 1; i <= nbBestSeconds; i++) {
                positions.push(`meilleur 2e ${i}/${nbBestSeconds}`);
            }
            
            this.qualifiedPositions = positions;
        },
        markDirty() {
            this.isDirty = true;
        },
        saveDraw() {
            this.saving = true;
            
            const drawArray = [];
            for (let i = 1; i <= 8; i++) {
                if (this.draw[i].team1 && this.draw[i].team2) {
                    drawArray.push({
                        match: i,
                        team1: this.draw[i].team1,
                        team2: this.draw[i].team2,
                    });
                }
            }
            
            const params = new URLSearchParams();
            params.append('code_competition_finals', this.code_competition);
            params.append('drawJson', JSON.stringify(drawArray));
            
            axios.post('/rest/action.php/rank/saveFullFinalsDraw', params)
                .then(response => {
                    if (response.data.success) {
                        this.isDirty = false;
                        alert(`Tirage sauvegardé : ${response.data.entries_count} entrées`);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la sauvegarde:', error);
                    alert('Erreur lors de la sauvegarde du tirage');
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    created() {
        this.initDraw();
        this.fetchData();
    }
};
