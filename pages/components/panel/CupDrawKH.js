export default {
    template: `
      <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Tirage au sort des poules de Coupe Khoury Hanna</h1>
        
        <div v-if="loading" class="text-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>
        
        <div v-else-if="data.total_teams === 0" class="alert alert-warning">
          Aucune équipe inscrite pour la Coupe Khoury Hanna.
        </div>
        
        <div v-else>
          <!-- Résumé -->
          <div class="stats shadow mb-6">
            <div class="stat">
              <div class="stat-title">Équipes inscrites</div>
              <div class="stat-value">{{ data.total_teams }}</div>
            </div>
            <div class="stat">
              <div class="stat-title">Poules à former</div>
              <div class="stat-value">{{ data.nb_pools }}</div>
              <div class="stat-desc">de {{ data.teams_per_pool }} équipes max</div>
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
                <li>Tirage <strong>complètement au sort</strong> (un seul chapeau)</li>
                <li>Tirer les équipes une par une et les répartir dans les poules</li>
                <li>Maximum {{ data.teams_per_pool }} équipes par poule</li>
              </ul>
            </div>
          </div>
          
          <!-- Liste des équipes -->
          <div>
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Équipes inscrites ({{ data.total_teams }})
            </h2>
            <div class="overflow-x-auto">
              <table class="table table-zebra w-full">
                <thead>
                  <tr class="bg-primary/20">
                    <th class="text-center">#</th>
                    <th>Équipe</th>
                    <th>Club</th>
                    <th class="text-center">Date inscription</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="team in data.teams" :key="'team-'+team.id_register">
                    <td class="text-center font-bold">{{ team.rang }}</td>
                    <td>
                      <span v-if="team.id_equipe">
                        <router-link :to="'/teams/' + team.id_equipe" class="link link-primary">
                          {{ team.equipe }}
                        </router-link>
                      </span>
                      <span v-else>{{ team.equipe }}</span>
                    </td>
                    <td>{{ team.club }}</td>
                    <td class="text-center text-sm">{{ team.date_inscription }}</td>
                  </tr>
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
                   class="p-3 rounded-lg text-center border-2 bg-base-200 border-base-300">
                {{ q.label }}
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            data: {
                teams: [],
                total_teams: 0,
                nb_pools: 0,
                teams_per_pool: 4
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
    created() {
        this.fetch();
    },
    methods: {
        fetch() {
            this.loading = true;
            axios
                .get('/rest/action.php/rank/getKHCupDrawData')
                .then((response) => {
                    this.data = response.data;
                    // Fetch finals data based on number of pools
                    if (this.data.nb_pools > 0) {
                        this.fetchFinalsData(this.data.nb_pools);
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
                .get(`/rest/action.php/rank/getCupFinalsDraw?nb_pools=${nb_pools}&has_tableau=0`)
                .then((response) => {
                    this.finalsData = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des données phases finales :", error);
                });
        },
        printCards() {
            const printWindow = window.open('', '_blank');
            let cardsHtml = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <title>Cartes Tirage Coupe Khoury Hanna</title>
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
                            page-break-inside: avoid;
                        }
                        .card-team {
                            font-size: 14pt;
                            font-weight: bold;
                            text-align: center;
                            margin-bottom: 2mm;
                        }
                        .card-club {
                            font-size: 11pt;
                            text-align: center;
                            color: #666;
                        }
                        h1 { 
                            text-align: center;
                            margin-bottom: 5mm;
                        }
                    </style>
                </head>
                <body>
                    <h1>Coupe Khoury Hanna</h1>
                    <div class="cards-container">
            `;
            
            this.data.teams.forEach(team => {
                cardsHtml += `
                    <div class="card">
                        <div class="card-team">${team.equipe}</div>
                        <div class="card-club">${team.club}</div>
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
        printFinalsCards() {
            const printWindow = window.open('', '_blank');
            let html = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <title>Cartes Phases Finales - Coupe Khoury Hanna</title>
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
                    <h1>Phases Finales - Coupe Khoury Hanna</h1>
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
    }
};
