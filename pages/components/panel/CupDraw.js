export default {
    template: `
      <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Tirage au sort des poules de Coupe Isoardi</h1>
        
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        
        <div v-else-if="data.total_teams === 0" class="alert alert-warning">
          Aucune équipe inscrite en coupe pour cette compétition.
        </div>
        
        <div v-else>
          <!-- Résumé -->
          <div class="stats shadow mb-6">
            <div class="stat">
              <div class="stat-title">Équipes inscrites</div>
              <div class="stat-value">{{ data.total_teams }}</div>
            </div>
            <div class="stat">
              <div class="stat-title">Tableau Haut</div>
              <div class="stat-value">{{ data.tableau_haut.teams.length }}</div>
              <div class="stat-desc">équipes</div>
            </div>
            <div class="stat">
              <div class="stat-title">Tableau Bas</div>
              <div class="stat-value">{{ data.tableau_bas.teams.length }}</div>
              <div class="stat-desc">équipes</div>
            </div>
          </div>
          
          <!-- Bouton Imprimer -->
          <div class="mb-6">
            <button class="btn btn-primary" @click="printCards">
              <i class="fas fa-print mr-2"></i>Imprimer les cartes pour le tirage
            </button>
          </div>
          
          <!-- Règles du tirage -->
          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
              <h3 class="font-bold">Règles du tirage</h3>
              <ul class="list-disc list-inside text-sm">
                <li>Les équipes du <strong>tableau haut</strong> ne peuvent pas jouer contre les équipes du <strong>tableau bas</strong></li>
                <li>Pour former une poule : tirer une équipe dans chaque chapeau (max 4 chapeaux = max 4 équipes par poule)</li>
              </ul>
            </div>
          </div>
          
          <!-- Tableau Haut -->
          <div class="mb-8">
            <h2 class="text-xl font-bold mb-4 text-success flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
              Tableau Haut ({{ data.tableau_haut.teams.length }} équipes → {{ data.tableau_haut.nb_pools }} poules de {{ data.tableau_haut.chapeaux.length }} équipes)
            </h2>
            <!-- Légende des chapeaux Tableau Haut -->
            <div class="mb-4">
              <div class="flex flex-wrap gap-2">
                <span v-for="(chapeau, index) in data.tableau_haut.chapeaux" 
                      :key="'haut-'+chapeau.numero"
                      class="badge badge-lg"
                      :class="getChapeauColorClass(index)">
                  Chapeau {{ chapeau.numero }} ({{ chapeau.size }} éq.)
                </span>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="table table-zebra w-full">
                <thead>
                  <tr class="bg-success/20">
                    <th class="text-center">Rang</th>
                    <th>Équipe</th>
                    <th class="text-center">Division</th>
                    <th class="text-center">Chapeau</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(chapeau, chapeauIndex) in data.tableau_haut.chapeaux">
                    <tr v-for="team in getTeamsFromChapeau(data.tableau_haut.teams, chapeau.numero)" 
                        :key="'haut-team-'+team.id_equipe"
                        :class="getChapeauRowClass(chapeauIndex)">
                      <td class="text-center font-bold">{{ team.rang }}</td>
                      <td>
                        <router-link :to="'/teams/' + team.id_equipe" class="link link-primary">
                          {{ team.equipe }}
                        </router-link>
                      </td>
                      <td class="text-center">{{ team.division }}</td>
                      <td class="text-center">
                        <span class="badge" :class="getChapeauColorClass(chapeauIndex)">
                          Chapeau {{ chapeau.numero }}
                        </span>
                      </td>
                    </tr>
                    <!-- Séparateur entre chapeaux -->
                    <tr v-if="chapeauIndex < data.tableau_haut.chapeaux.length - 1" 
                        class="border-b-4 border-base-300">
                      <td colspan="4"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Séparateur Tableau Haut / Tableau Bas -->
          <div class="divider divider-error my-8">
            <span class="badge badge-error badge-lg gap-2 py-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              LIMITE TABLEAU HAUT / TABLEAU BAS
            </span>
          </div>
          
          <!-- Tableau Bas -->
          <div>
            <h2 class="text-xl font-bold mb-4 text-error flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
              Tableau Bas ({{ data.tableau_bas.teams.length }} équipes → {{ data.tableau_bas.nb_pools }} poules de {{ data.tableau_bas.chapeaux.length }} équipes)
            </h2>
            <!-- Légende des chapeaux Tableau Bas -->
            <div class="mb-4">
              <div class="flex flex-wrap gap-2">
                <span v-for="(chapeau, index) in data.tableau_bas.chapeaux" 
                      :key="'bas-'+chapeau.numero"
                      class="badge badge-lg"
                      :class="getChapeauColorClass(index)">
                  Chapeau {{ chapeau.numero }} ({{ chapeau.size }} éq.)
                </span>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="table table-zebra w-full">
                <thead>
                  <tr class="bg-error/20">
                    <th class="text-center">Rang</th>
                    <th>Équipe</th>
                    <th class="text-center">Division</th>
                    <th class="text-center">Chapeau</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(chapeau, chapeauIndex) in data.tableau_bas.chapeaux">
                    <tr v-for="team in getTeamsFromChapeau(data.tableau_bas.teams, chapeau.numero)" 
                        :key="'bas-team-'+team.id_equipe"
                        :class="getChapeauRowClass(chapeauIndex)">
                      <td class="text-center font-bold">{{ team.rang }}</td>
                      <td>
                        <router-link :to="'/teams/' + team.id_equipe" class="link link-primary">
                          {{ team.equipe }}
                        </router-link>
                      </td>
                      <td class="text-center">{{ team.division }}</td>
                      <td class="text-center">
                        <span class="badge" :class="getChapeauColorClass(chapeauIndex)">
                          Chapeau {{ chapeau.numero }}
                        </span>
                      </td>
                    </tr>
                    <!-- Séparateur entre chapeaux -->
                    <tr v-if="chapeauIndex < data.tableau_bas.chapeaux.length - 1" 
                        class="border-b-4 border-base-300">
                      <td colspan="4"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Section Phases Finales -->
          <div class="divider divider-warning my-8">
            <span class="badge badge-warning badge-lg gap-2 py-4">
              <i class="fas fa-trophy"></i>
              PHASES FINALES (1/8 de finale)
            </span>
          </div>
          
          <div class="mb-6">
            <div class="alert alert-warning mb-4">
              <i class="fas fa-info-circle"></i>
              <div>
                <strong>16 équipes qualifiées pour les 1/8 de finale :</strong>
                {{ finalsData.nb_first_places }} premiers de poule + {{ finalsData.nb_best_seconds }} meilleurs 2e
              </div>
            </div>
            
            <button class="btn btn-warning mb-4" @click="printFinalsCards">
              <i class="fas fa-print mr-2"></i>Imprimer les cartes phases finales
            </button>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
              <div v-for="(q, idx) in finalsData.qualified" :key="'final-'+idx" 
                   class="p-3 rounded-lg text-center border-2"
                   :class="getFinalsQualifiedClass(q)">
                {{ q.label }}
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            code_competition: this.$route.params.code_competition || 'm',
            data: {
                teams: [],
                total_teams: 0,
                half_point: 0,
                tableau_haut: { teams: [], chapeaux: [], nb_pools: 0 },
                tableau_bas: { teams: [], chapeaux: [], nb_pools: 0 }
            },
            finalsData: {
                qualified: [],
                nb_pools: 0,
                nb_first_places: 0,
                nb_best_seconds: 0
            },
            loading: true
        };
    },
    watch: {
        '$route.params.code_competition': {
            handler(newVal) {
                if (newVal) {
                    this.code_competition = newVal;
                    this.fetch();
                }
            },
            immediate: true
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            axios
                .get(`/rest/action.php/rank/getCupDrawData?code_competition=${this.code_competition}`)
                .then((response) => {
                    this.data = response.data;
                    // Fetch finals data based on total pools
                    const nb_pools = (this.data.tableau_haut.nb_pools || 0) + (this.data.tableau_bas.nb_pools || 0);
                    if (nb_pools > 0) {
                        this.fetchFinalsData(nb_pools);
                    }
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchFinalsData(nb_pools) {
            axios
                .get(`/rest/action.php/rank/getCupFinalsDraw?nb_pools=${nb_pools}&has_tableau=1`)
                .then((response) => {
                    this.finalsData = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des données phases finales :", error);
                });
        },
        getTeamsFromChapeau(teams, chapeauNumero) {
            return teams.filter(team => team.chapeau === chapeauNumero);
        },
        getChapeauColorClass(index) {
            const colors = [
                'badge-primary',
                'badge-secondary', 
                'badge-accent',
                'badge-info',
                'badge-warning',
                'badge-success'
            ];
            return colors[index % colors.length];
        },
        getChapeauRowClass(index) {
            const colors = [
                'bg-primary/5',
                'bg-secondary/5',
                'bg-accent/5',
                'bg-info/5',
                'bg-warning/5',
                'bg-success/5'
            ];
            return colors[index % colors.length];
        },
        printCards() {
            const printWindow = window.open('', '_blank');
            let cardsHtml = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <title>Cartes Tirage Coupe Isoardi</title>
                    <style>
                        @page { margin: 10mm; }
                        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                        .cards-container { 
                            display: flex; 
                            flex-wrap: wrap; 
                            gap: 5mm;
                            justify-content: flex-start;
                        }
                        .card {
                            width: 85mm;
                            height: 50mm;
                            border: 2px solid #333;
                            border-radius: 3mm;
                            padding: 3mm;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            page-break-inside: avoid;
                        }
                        .card-team {
                            font-size: 14pt;
                            font-weight: bold;
                            text-align: center;
                            margin-bottom: 2mm;
                        }
                        .card-club {
                            font-size: 10pt;
                            text-align: center;
                            color: #666;
                            margin-bottom: 3mm;
                        }
                        .card-info {
                            display: flex;
                            justify-content: space-between;
                            font-size: 11pt;
                            border-top: 1px solid #ccc;
                            padding-top: 2mm;
                        }
                        .card-tableau {
                            font-weight: bold;
                        }
                        .tableau-haut { color: #22c55e; }
                        .tableau-bas { color: #ef4444; }
                        h2 { 
                            width: 100%; 
                            margin: 5mm 0 3mm 0; 
                            page-break-before: always;
                        }
                        h2:first-child { page-break-before: avoid; }
                    </style>
                </head>
                <body>
                    <h2>TABLEAU HAUT</h2>
                    <div class="cards-container">
            `;
            
            // Tableau Haut cards
            this.data.tableau_haut.teams.forEach(team => {
                cardsHtml += `
                    <div class="card">
                        <div class="card-team">${team.equipe}</div>
                        <div class="card-club">${team.club || ''}</div>
                        <div class="card-info">
                            <span>Chapeau ${team.chapeau}</span>
                            <span class="card-tableau tableau-haut">TABLEAU HAUT</span>
                        </div>
                    </div>
                `;
            });
            
            cardsHtml += `
                    </div>
                    <h2>TABLEAU BAS</h2>
                    <div class="cards-container">
            `;
            
            // Tableau Bas cards
            this.data.tableau_bas.teams.forEach(team => {
                cardsHtml += `
                    <div class="card">
                        <div class="card-team">${team.equipe}</div>
                        <div class="card-club">${team.club || ''}</div>
                        <div class="card-info">
                            <span>Chapeau ${team.chapeau}</span>
                            <span class="card-tableau tableau-bas">TABLEAU BAS</span>
                        </div>
                    </div>
                `;
            });
            
            cardsHtml += `
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(cardsHtml);
            printWindow.document.close();
            printWindow.onload = () => {
                printWindow.print();
            };
        },
        getFinalsQualifiedClass(q) {
            if (q.position === 2) return 'bg-warning/30 border-warning';
            if (q.tableau === 'haut') return 'bg-success/30 border-success';
            if (q.tableau === 'bas') return 'bg-error/30 border-error';
            return 'bg-base-300 border-base-300';
        },
        printFinalsCards() {
            const printWindow = window.open('', '_blank');
            let html = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <title>Cartes Phases Finales - Coupe Isoardi</title>
                    <style>
                        @page { margin: 10mm; }
                        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                        .cards-container { 
                            display: flex; 
                            flex-wrap: wrap; 
                            gap: 5mm;
                            justify-content: flex-start;
                        }
                        .card {
                            width: 85mm;
                            height: 40mm;
                            border: 2px solid #333;
                            border-radius: 3mm;
                            padding: 3mm;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            page-break-inside: avoid;
                        }
                        .card-label {
                            font-size: 16pt;
                            font-weight: bold;
                            text-align: center;
                        }
                        h1 { text-align: center; margin-bottom: 5mm; }
                    </style>
                </head>
                <body>
                    <h1>Phases Finales - Coupe Isoardi</h1>
                    <div class="cards-container">
            `;
            
            this.finalsData.qualified.forEach(q => {
                html += `
                    <div class="card">
                        <div class="card-label">${q.label}</div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.onload = () => {
                printWindow.print();
            };
        }
    },
    created() {
        this.fetch();
    }
};
