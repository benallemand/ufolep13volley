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
              <div class="stat-title">Chapeaux</div>
              <div class="stat-value">{{ data.chapeaux.length }}</div>
            </div>
            <div class="stat">
              <div class="stat-title">Poules</div>
              <div class="stat-value">{{ data.nb_pools }}</div>
              <div class="stat-desc">de {{ data.chapeaux.length }} équipes max</div>
            </div>
          </div>
          
          <!-- Légende des chapeaux -->
          <div class="mb-6">
            <h2 class="text-lg font-bold mb-2">Légende des chapeaux</h2>
            <div class="flex flex-wrap gap-2">
              <span v-for="(chapeau, index) in data.chapeaux" 
                    :key="chapeau.numero"
                    class="badge badge-lg"
                    :class="getChapeauColorClass(index)">
                Chapeau {{ chapeau.numero }} ({{ chapeau.size }} éq.)
              </span>
            </div>
          </div>
          
          <!-- Règles du tirage -->
          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
              <h3 class="font-bold">Règles du tirage</h3>
              <ul class="list-disc list-inside text-sm">
                <li>Les équipes sont triées par <strong>date d'inscription</strong></li>
                <li>Pour former une poule : tirer une équipe dans chaque chapeau</li>
                <li>Maximum {{ data.chapeaux.length }} équipes par poule</li>
              </ul>
            </div>
          </div>
          
          <!-- Liste des équipes -->
          <div>
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Équipes inscrites ({{ data.total_teams }} → {{ data.nb_pools }} poules de {{ data.chapeaux.length }} équipes)
            </h2>
            <div class="overflow-x-auto">
              <table class="table table-zebra w-full">
                <thead>
                  <tr class="bg-primary/20">
                    <th class="text-center">Ordre</th>
                    <th>Équipe</th>
                    <th>Club</th>
                    <th class="text-center">Date inscription</th>
                    <th class="text-center">Chapeau</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(chapeau, chapeauIndex) in data.chapeaux">
                    <tr v-for="team in getTeamsFromChapeau(chapeau.numero)" 
                        :key="'team-'+team.id_register"
                        :class="getChapeauRowClass(chapeauIndex)">
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
                      <td class="text-center">
                        <span class="badge" :class="getChapeauColorClass(chapeauIndex)">
                          Chapeau {{ chapeau.numero }}
                        </span>
                      </td>
                    </tr>
                    <!-- Séparateur entre chapeaux -->
                    <tr v-if="chapeauIndex < data.chapeaux.length - 1" 
                        class="border-b-4 border-base-300">
                      <td colspan="5"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            data: {
                teams: [],
                chapeaux: [],
                total_teams: 0,
                nb_pools: 0
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
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getTeamsFromChapeau(chapeauNumero) {
            return this.data.teams.filter(team => team.chapeau === chapeauNumero);
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
        }
    }
};
