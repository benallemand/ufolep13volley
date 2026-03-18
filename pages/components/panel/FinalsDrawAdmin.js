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
          <!-- Section 1/8 de finale -->
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

          <!-- Section tirage de réception (quarts et demi-finales) -->
          <div class="divider"></div>
          
          <div class="alert alert-warning mb-6">
            <i class="fas fa-home"></i>
            <div>
              <strong>Tirage de réception des quarts et demi-finales</strong>
              <p class="text-sm">Indiquez quel vainqueur de 1/8 recevra en quart de finale, et quel vainqueur de 1/4 recevra en demi-finale.</p>
            </div>
          </div>

          <!-- Quarts de finale -->
          <h3 class="text-lg font-semibold mb-3"><i class="fas fa-trophy mr-2"></i>Quarts de finale</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div v-for="quarterNum in 4" :key="'quarter-' + quarterNum"
                 class="card bg-base-200 shadow-md">
              <div class="card-body p-4">
                <h4 class="card-title text-sm">Quart {{ quarterNum }}</h4>
                <p class="text-xs text-gray-500 mb-2">
                  Vainqueur 1/8 #{{ quarterNum * 2 - 1 }} vs Vainqueur 1/8 #{{ quarterNum * 2 }}
                </p>
                <select class="select select-bordered w-full" 
                        v-model="hostDraw['1_4'][quarterNum]"
                        @change="markHostDirty">
                  <option :value="null">-- Qui reçoit ? --</option>
                  <option :value="quarterNum * 2 - 1">Vainqueur 1/8 #{{ quarterNum * 2 - 1 }} <i class="fas fa-home"></i></option>
                  <option :value="quarterNum * 2">Vainqueur 1/8 #{{ quarterNum * 2 }} <i class="fas fa-home"></i></option>
                </select>
              </div>
            </div>
          </div>

          <!-- Demi-finales -->
          <h3 class="text-lg font-semibold mb-3"><i class="fas fa-trophy mr-2"></i>Demi-finales</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div v-for="semiNum in 2" :key="'semi-' + semiNum"
                 class="card bg-base-200 shadow-md">
              <div class="card-body p-4">
                <h4 class="card-title text-sm">Demi-finale {{ semiNum }}</h4>
                <p class="text-xs text-gray-500 mb-2">
                  Vainqueur 1/4 #{{ semiNum * 2 - 1 }} vs Vainqueur 1/4 #{{ semiNum * 2 }}
                </p>
                <select class="select select-bordered w-full" 
                        v-model="hostDraw['1_2'][semiNum]"
                        @change="markHostDirty">
                  <option :value="null">-- Qui reçoit ? --</option>
                  <option :value="semiNum * 2 - 1">Vainqueur 1/4 #{{ semiNum * 2 - 1 }} <i class="fas fa-home"></i></option>
                  <option :value="semiNum * 2">Vainqueur 1/4 #{{ semiNum * 2 }} <i class="fas fa-home"></i></option>
                </select>
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
            isHostDirty: false,
            draw: {},
            hostDraw: {
                '1_4': { 1: null, 2: null, 3: null, 4: null },
                '1_2': { 1: null, 2: null }
            },
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
            // Fetch existing host draw
            const hostDrawPromise = axios.get(`/rest/action.php/rank/getFinalsHostDraw?code_competition_finals=${this.code_competition}`);
            
            Promise.all([poolsPromise, drawPromise, hostDrawPromise])
                .then(([poolsResponse, drawResponse, hostDrawResponse]) => {
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
                    
                    // Load existing host draw if any
                    const existingHostDraw = hostDrawResponse.data;
                    if (existingHostDraw) {
                        if (existingHostDraw['1_4']) {
                            for (const [matchNum, hostWinner] of Object.entries(existingHostDraw['1_4'])) {
                                this.hostDraw['1_4'][parseInt(matchNum)] = hostWinner;
                            }
                        }
                        if (existingHostDraw['1_2']) {
                            for (const [matchNum, hostWinner] of Object.entries(existingHostDraw['1_2'])) {
                                this.hostDraw['1_2'][parseInt(matchNum)] = hostWinner;
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
        markHostDirty() {
            this.isHostDirty = true;
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
            
            // Save both draw and host draw
            const saveDrawPromise = axios.post('/rest/action.php/rank/saveFullFinalsDraw', params);
            
            const hostParams = new URLSearchParams();
            hostParams.append('code_competition_finals', this.code_competition);
            hostParams.append('hostDrawJson', JSON.stringify(this.hostDraw));
            const saveHostDrawPromise = axios.post('/rest/action.php/rank/saveFinalsHostDraw', hostParams);
            
            Promise.all([saveDrawPromise, saveHostDrawPromise])
                .then(([drawResponse, hostResponse]) => {
                    this.isDirty = false;
                    this.isHostDirty = false;
                    const drawCount = drawResponse.data.entries_count || 0;
                    const hostCount = hostResponse.data.entries_count || 0;
                    alert(`Tirage sauvegardé : ${drawCount} entrées 1/8 + ${hostCount} entrées réception`);
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
