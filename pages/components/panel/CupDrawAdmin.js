export default {
    template: `
      <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Administration du tirage au sort</h1>
        
        <!-- Sélection de la compétition -->
        <div class="card bg-base-200 mb-6">
          <div class="card-body">
            <h2 class="card-title">
              <i class="fas fa-trophy mr-2"></i>
              Sélection de la compétition
            </h2>
            <div class="form-control w-full max-w-xs">
              <label class="label">
                <span class="label-text">Compétition de coupe</span>
              </label>
              <select class="select select-bordered" v-model="selectedCompetition" @change="onCompetitionChange">
                <option value="">-- Sélectionner une compétition --</option>
                <option v-for="comp in competitions" :key="comp.code_competition" :value="comp.code_competition">
                  {{ comp.libelle }} ({{ comp.code_competition }})
                </option>
              </select>
            </div>
            
            <div v-if="selectedCompetition" class="mt-4 flex gap-2 flex-wrap">
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Nombre de poules</span>
                </label>
                <input type="number" class="input input-bordered w-24" v-model.number="nbPools" min="1" max="20" @change="initializePools">
              </div>
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Équipes par poule (max)</span>
                </label>
                <input type="number" class="input input-bordered w-24" v-model.number="teamsPerPool" min="2" max="6">
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        
        <div v-else-if="selectedCompetition && availableTeams.length > 0">
          <!-- Zone de tirage -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Liste des équipes disponibles -->
            <div class="lg:col-span-1">
              <div class="card bg-base-200 h-full">
                <div class="card-body">
                  <h2 class="card-title text-sm">
                    <i class="fas fa-users mr-2"></i>
                    Équipes disponibles ({{ unassignedTeams.length }})
                  </h2>
                  <div class="bg-base-100 rounded-lg p-2 min-h-[400px] max-h-[600px] overflow-y-auto"
                       @dragover.prevent
                       @drop="onDropToAvailable($event)">
                    <div v-for="team in unassignedTeams" 
                         :key="'avail-'+team.id_equipe"
                         class="card mb-2 cursor-grab active:cursor-grabbing"
                         :class="getTeamCardClass(team)"
                         draggable="true"
                         @dragstart="onDragStart($event, team)"
                         @dragend="onDragEnd">
                      <div class="card-body p-3">
                        <div class="flex items-center gap-2">
                          <i class="fas fa-grip-vertical text-base-content/50"></i>
                          <div class="flex-1">
                            <div class="font-bold text-sm">{{ team.nom_equipe }}</div>
                            <div class="text-xs opacity-70">{{ team.club }}</div>
                          </div>
                          <div class="flex flex-col items-end gap-1">
                            <span v-if="team.chapeau" class="badge badge-xs" :class="getChapeauClass(team.chapeau)">
                              C{{ team.chapeau }}
                            </span>
                            <span v-if="team.tableau" class="badge badge-xs" :class="team.tableau === 'haut' ? 'badge-success' : 'badge-error'">
                              {{ team.tableau === 'haut' ? '▲' : '▼' }}
                            </span>
                            <span v-if="!team.chapeau" class="badge badge-sm badge-ghost">#{{ team.rang_global }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-if="unassignedTeams.length === 0" class="text-center text-base-content/50 py-8">
                      <i class="fas fa-check-circle text-4xl mb-2"></i>
                      <p>Toutes les équipes ont été placées</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Grille des poules -->
            <div class="lg:col-span-2">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">
                  <i class="fas fa-sitemap mr-2"></i>
                  Poules ({{ nbPools }})
                </h2>
                <div class="flex gap-2">
                  <button class="btn btn-error btn-sm" @click="clearAllPools" :disabled="saving">
                    <i class="fas fa-trash mr-1"></i>Vider tout
                  </button>
                  <button class="btn btn-success" @click="savePools" :disabled="saving || !hasChanges">
                    <span v-if="saving" class="loading loading-spinner loading-sm"></span>
                    <i v-else class="fas fa-save mr-1"></i>
                    Sauvegarder
                  </button>
                </div>
              </div>
              
              <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                <div v-for="poolIndex in nbPools" :key="'pool-'+poolIndex"
                     class="card bg-base-200 border-2"
                     :class="getPoolClass(poolIndex)">
                  <div class="card-body p-3">
                    <h3 class="card-title text-sm justify-center">
                      Poule {{ poolIndex }}
                      <span class="badge badge-sm" :class="getPoolBadgeClass(poolIndex)">
                        {{ getPoolTeams(poolIndex).length }}/{{ teamsPerPool }}
                      </span>
                    </h3>
                    
                    <!-- Zone de drop -->
                    <div class="bg-base-100 rounded-lg p-2 min-h-[150px] transition-all"
                         :class="{'ring-2 ring-primary ring-offset-2': dragOverPool === poolIndex}"
                         @dragover.prevent="onDragOver(poolIndex)"
                         @dragleave="onDragLeave"
                         @drop="onDropToPool($event, poolIndex)">
                      
                      <div v-for="(team, idx) in getPoolTeams(poolIndex)" 
                           :key="'pool-'+poolIndex+'-team-'+team.id_equipe"
                           class="bg-base-300 rounded p-2 mb-1 text-xs cursor-grab flex items-center gap-1"
                           draggable="true"
                           @dragstart="onDragStart($event, team, poolIndex)"
                           @dragend="onDragEnd">
                        <i class="fas fa-grip-vertical text-base-content/30"></i>
                        <span class="badge badge-xs badge-primary">{{ idx + 1 }}</span>
                        <span class="flex-1 truncate font-medium">{{ team.nom_equipe }}</span>
                        <button class="btn btn-ghost btn-xs" @click="removeFromPool(poolIndex, team)">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                      
                      <div v-if="getPoolTeams(poolIndex).length === 0" 
                           class="text-center text-base-content/30 py-6 text-xs">
                        <i class="fas fa-arrow-down text-2xl mb-1"></i>
                        <p>Glisser ici</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Statistiques -->
          <div class="stats shadow mt-6 w-full">
            <div class="stat">
              <div class="stat-title">Équipes placées</div>
              <div class="stat-value text-success">{{ assignedTeamsCount }}</div>
              <div class="stat-desc">sur {{ availableTeams.length }} total</div>
            </div>
            <div class="stat">
              <div class="stat-title">Poules complètes</div>
              <div class="stat-value text-primary">{{ completePoolsCount }}</div>
              <div class="stat-desc">sur {{ nbPools }} poules</div>
            </div>
            <div class="stat">
              <div class="stat-title">Équipes restantes</div>
              <div class="stat-value" :class="unassignedTeams.length > 0 ? 'text-warning' : 'text-success'">
                {{ unassignedTeams.length }}
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="selectedCompetition && availableTeams.length === 0" class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Aucune équipe trouvée pour cette compétition. Vérifiez que les classements du championnat source existent.</span>
        </div>
        
        <!-- Toast notifications -->
        <div class="toast toast-end">
          <div v-if="successMessage" class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>{{ successMessage }}</span>
          </div>
          <div v-if="errorMessage" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ errorMessage }}</span>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            competitions: [],
            selectedCompetition: '',
            availableTeams: [],
            pools: {}, // { poolIndex: [team1, team2, ...] }
            nbPools: 14,
            teamsPerPool: 4,
            loading: false,
            saving: false,
            draggedTeam: null,
            draggedFromPool: null,
            dragOverPool: null,
            hasChanges: false,
            successMessage: '',
            errorMessage: '',
            originalPools: {}
        };
    },
    computed: {
        unassignedTeams() {
            const assignedIds = new Set();
            Object.values(this.pools).forEach(poolTeams => {
                poolTeams.forEach(team => assignedIds.add(team.id_equipe));
            });
            return this.availableTeams.filter(team => !assignedIds.has(team.id_equipe));
        },
        assignedTeamsCount() {
            return Object.values(this.pools).reduce((sum, poolTeams) => sum + poolTeams.length, 0);
        },
        completePoolsCount() {
            return Object.values(this.pools).filter(poolTeams => poolTeams.length >= this.teamsPerPool - 1).length;
        }
    },
    methods: {
        async fetchCompetitions() {
            try {
                const response = await axios.get('/rest/action.php/rank/getCupCompetitions');
                this.competitions = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement des compétitions:', error);
                this.showError('Erreur lors du chargement des compétitions');
            }
        },
        async onCompetitionChange() {
            if (!this.selectedCompetition) {
                this.availableTeams = [];
                this.pools = {};
                return;
            }
            
            this.loading = true;
            try {
                // Fetch available teams
                const teamsResponse = await axios.get(`/rest/action.php/rank/getTeamsForCupDraw?code_competition=${this.selectedCompetition}`);
                this.availableTeams = teamsResponse.data;
                
                // Fetch existing pool assignments
                const poolsResponse = await axios.get(`/rest/action.php/rank/getCupPoolAssignments?code_competition=${this.selectedCompetition}`);
                
                // Convert existing assignments to our format
                this.pools = {};
                const existingPools = poolsResponse.data;
                
                if (Object.keys(existingPools).length > 0) {
                    // Update nbPools based on existing data
                    this.nbPools = Math.max(this.nbPools, ...Object.keys(existingPools).map(k => parseInt(k)));
                    
                    // Map existing assignments
                    Object.entries(existingPools).forEach(([poolNum, teams]) => {
                        this.pools[parseInt(poolNum)] = teams.map(t => {
                            // Find full team info from availableTeams
                            const fullTeam = this.availableTeams.find(at => at.id_equipe == t.id_equipe);
                            return fullTeam || t;
                        });
                    });
                }
                
                this.initializePools();
                this.originalPools = JSON.parse(JSON.stringify(this.pools));
                this.hasChanges = false;
                
            } catch (error) {
                console.error('Erreur lors du chargement:', error);
                this.showError('Erreur lors du chargement des données');
            } finally {
                this.loading = false;
            }
        },
        initializePools() {
            // Ensure all pool indices exist
            for (let i = 1; i <= this.nbPools; i++) {
                if (!this.pools[i]) {
                    this.$set(this.pools, i, []);
                }
            }
            // Remove pools beyond nbPools
            Object.keys(this.pools).forEach(key => {
                if (parseInt(key) > this.nbPools) {
                    // Move teams back to available
                    this.$delete(this.pools, key);
                }
            });
        },
        getPoolTeams(poolIndex) {
            return this.pools[poolIndex] || [];
        },
        getPoolClass(poolIndex) {
            const teams = this.getPoolTeams(poolIndex);
            if (teams.length >= this.teamsPerPool) return 'border-success';
            if (teams.length > 0) return 'border-warning';
            return 'border-base-300';
        },
        getPoolBadgeClass(poolIndex) {
            const teams = this.getPoolTeams(poolIndex);
            if (teams.length >= this.teamsPerPool) return 'badge-success';
            if (teams.length > 0) return 'badge-warning';
            return 'badge-ghost';
        },
        onDragStart(event, team, fromPool = null) {
            this.draggedTeam = team;
            this.draggedFromPool = fromPool;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', team.id_equipe);
        },
        onDragEnd() {
            this.draggedTeam = null;
            this.draggedFromPool = null;
            this.dragOverPool = null;
        },
        onDragOver(poolIndex) {
            this.dragOverPool = poolIndex;
        },
        onDragLeave() {
            this.dragOverPool = null;
        },
        onDropToPool(event, poolIndex) {
            event.preventDefault();
            this.dragOverPool = null;
            
            if (!this.draggedTeam) return;
            
            // Check if pool is full
            const currentTeams = this.getPoolTeams(poolIndex);
            if (currentTeams.length >= this.teamsPerPool) {
                this.showError(`La poule ${poolIndex} est complète (max ${this.teamsPerPool} équipes)`);
                return;
            }
            
            // Remove from previous pool if coming from one
            if (this.draggedFromPool) {
                this.$set(this.pools, this.draggedFromPool, this.pools[this.draggedFromPool].filter(
                    t => t.id_equipe !== this.draggedTeam.id_equipe
                ));
            }
            
            // Add to new pool
            if (!this.pools[poolIndex]) {
                this.$set(this.pools, poolIndex, []);
            }
            
            // Check if team already in this pool
            if (!this.pools[poolIndex].find(t => t.id_equipe === this.draggedTeam.id_equipe)) {
                const newPool = [...this.pools[poolIndex], this.draggedTeam];
                this.$set(this.pools, poolIndex, newPool);
            }
            
            this.hasChanges = true;
            this.draggedTeam = null;
            this.draggedFromPool = null;
        },
        onDropToAvailable(event) {
            event.preventDefault();
            
            if (!this.draggedTeam || !this.draggedFromPool) return;
            
            // Remove from pool
            this.$set(this.pools, this.draggedFromPool, this.pools[this.draggedFromPool].filter(
                t => t.id_equipe !== this.draggedTeam.id_equipe
            ));
            
            this.hasChanges = true;
            this.draggedTeam = null;
            this.draggedFromPool = null;
        },
        removeFromPool(poolIndex, team) {
            this.$set(this.pools, poolIndex, this.pools[poolIndex].filter(
                t => t.id_equipe !== team.id_equipe
            ));
            this.hasChanges = true;
        },
        clearAllPools() {
            if (!confirm('Êtes-vous sûr de vouloir vider toutes les poules ?')) return;
            
            Object.keys(this.pools).forEach(key => {
                this.$set(this.pools, key, []);
            });
            this.hasChanges = true;
        },
        async savePools() {
            if (!this.selectedCompetition) return;
            
            this.saving = true;
            this.errorMessage = '';
            this.successMessage = '';
            
            try {
                // Prepare data for API: array of arrays with team IDs
                const poolsData = [];
                for (let i = 1; i <= this.nbPools; i++) {
                    const poolTeams = this.pools[i] || [];
                    poolsData.push(poolTeams.map(t => t.id_equipe));
                }
                
                // Send to API
                const formData = new FormData();
                formData.append('code_competition', this.selectedCompetition);
                formData.append('pools', JSON.stringify(poolsData));
                
                await axios.post('/rest/action.php/rank/saveCupPoolAssignments', formData);
                
                this.originalPools = JSON.parse(JSON.stringify(this.pools));
                this.hasChanges = false;
                this.showSuccess(`Tirage sauvegardé: ${this.assignedTeamsCount} équipes dans ${this.nbPools} poules`);
                
            } catch (error) {
                console.error('Erreur lors de la sauvegarde:', error);
                this.showError(error.response?.data?.message || 'Erreur lors de la sauvegarde');
            } finally {
                this.saving = false;
            }
        },
        showSuccess(message) {
            this.successMessage = message;
            setTimeout(() => { this.successMessage = ''; }, 4000);
        },
        showError(message) {
            this.errorMessage = message;
            setTimeout(() => { this.errorMessage = ''; }, 4000);
        },
        getTeamCardClass(team) {
            if (team.tableau === 'haut') return 'bg-success/20 border border-success/50';
            if (team.tableau === 'bas') return 'bg-error/20 border border-error/50';
            return 'bg-base-300';
        },
        getChapeauClass(chapeau) {
            const classes = ['badge-primary', 'badge-secondary', 'badge-accent', 'badge-info', 'badge-warning', 'badge-success'];
            return classes[(chapeau - 1) % classes.length];
        }
    },
    created() {
        this.fetchCompetitions();
    }
};
