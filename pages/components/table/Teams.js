export default {
    template: `
      <div class="bg-base-100">
        <div class="text-center mt-4 mb-6">
          <div class="text-2xl font-bold text-primary">annuaire</div>
        </div>

        <!-- Barre de recherche -->
        <div class="mb-4 px-4">
          <div class="flex flex-col *:w-full w-full max-w-md mx-auto">
            <input
                type="text"
                placeholder="Rechercher par club, équipe, responsable..."
                class="input w-full"
                v-model="searchQuery"
            />
          </div>
        </div>

        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th></th>
            <th>compétition</th>
            <th>division</th>
            <th>équipe</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          <template v-for="(item, index) in filteredItems" :key="item.id_equipe+item.code_competition+item.division">
            <!-- Ligne principale -->
            <tr class="hover:bg-base-200 cursor-pointer" @click="toggleRow(index)">
              <td>
                <button class="btn btn-ghost btn-xs">
                  <span v-if="expandedRows.has(index)">▼</span>
                  <span v-else>▶</span>
                </button>
              </td>
              <td>{{ item.libelle_competition }}</td>
              <td>{{ item.division }}</td>
              <td>
                <div class="flex items-center justify-between">
                  <router-link :to="'/teams/' + item.id_equipe" class="font-medium link link-primary hover:link-hover">
                    {{ item.nom_equipe }}
                  </router-link>
                  <div class="flex gap-1">
                    <div v-if="item.gymnasiums_list" class="badge badge-primary badge-xs">📍</div>
                    <div v-if="item.web_site" class="badge badge-info badge-xs">🌐</div>
                    <div v-if="item.path_photo" class="badge badge-success badge-xs">📷</div>
                  </div>
                </div>
              </td>
              <td class="text-right">
                <div class="text-xs text-base-content/50">{{ item.club }}</div>
              </td>
            </tr>

            <!-- Ligne extensible avec les détails -->
            <tr v-if="expandedRows.has(index)" class="bg-base-100">
              <td colspan="5" class="p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                  <!-- Informations de contact -->
                  <div class="space-y-3">
                    <h4 class="font-semibold text-sm flex items-center gap-2">
                      <span class="badge badge-secondary badge-sm">👥</span>
                      Contact
                    </h4>
                    <div class="space-y-2">
                      <div class="bg-base-200 rounded-lg p-3 border border-base-300">
                        <div class="space-y-2">
                          <div>
                            <div class="text-xs text-base-content/70 uppercase tracking-wide">Club</div>
                            <div class="font-medium">{{ item.club }}</div>
                          </div>
                          <div v-if="item.responsable">
                            <div class="text-xs text-base-content/70 uppercase tracking-wide">Responsable</div>
                            <div>{{ item.responsable }}</div>
                          </div>
                          <div v-if="item.telephone_1 || item.telephone_2" class="space-y-1">
                            <div class="text-xs text-base-content/70 uppercase tracking-wide">Téléphone</div>
                            <div v-if="item.telephone_1" class="flex items-center gap-2">
                              <span class="badge badge-ghost badge-xs">📞</span>
                              <span>{{ item.telephone_1 }}</span>
                            </div>
                            <div v-if="item.telephone_2" class="flex items-center gap-2">
                              <span class="badge badge-ghost badge-xs">📱</span>
                              <span class="text-sm">{{ item.telephone_2 }}</span>
                            </div>
                          </div>
                          <div v-if="item.email">
                            <div class="text-xs text-base-content/70 uppercase tracking-wide">Email</div>
                            <div class="break-all">{{ item.email }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Créneaux -->
                  <div v-if="item.gymnasiums_list" class="space-y-3">
                    <h4 class="font-semibold text-sm flex items-center gap-2">
                      <span class="badge badge-primary badge-sm">📍</span>
                      Créneaux
                    </h4>
                    <div class="space-y-2">
                      <div v-for="(schedule, scheduleIndex) in formatSchedules(item.gymnasiums_list)"
                           :key="index + '-schedule-' + scheduleIndex"
                           class="bg-base-200 rounded-lg p-3 border border-base-300">
                        <div class="flex items-center gap-2 mb-2">
                          <div class="badge badge-ghost badge-sm">📍</div>
                          <a class="link link-primary font-medium text-sm hover:text-primary-focus"
                             :href="'https://www.google.com/maps/place/'+schedule.gps"
                             target="_blank">
                            {{ schedule.ville }} - {{ schedule.nom }}
                          </a>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-base-content/70">
                          <div class="badge badge-ghost badge-xs">🕐</div>
                          <span>{{ schedule.horaire }}</span>
                          <span v-if="schedule.hasConstraint" class="badge badge-warning badge-sm">⚠️ Contrainte</span>
                        </div>
                        <div v-if="schedule.remarques" class="mt-2 text-xs text-info italic">
                          📝 {{ schedule.remarques }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Autres informations -->
                  <div class="space-y-4">
                    <!-- Site web -->
                    <div v-if="item.web_site">
                      <h4 class="font-semibold text-sm flex items-center gap-2 mb-2">
                        <span class="badge badge-info badge-sm">🌐</span>
                        Site web
                      </h4>
                      <a :href="item.web_site" target="_blank" class="link link-primary">{{ item.web_site }}</a>
                    </div>

                    <!-- Photo -->
                    <div v-if="item.path_photo">
                      <h4 class="font-semibold text-sm flex items-center gap-2 mb-2">
                        <span class="badge badge-success badge-sm">📷</span>
                        Photo
                      </h4>
                      <img :src="$url('/'+item.path_photo)" alt="photo équipe"
                           class="max-h-32 rounded-lg border border-base-300"/>
                    </div>

                    <!-- Fiche équipe -->
                    <div>
                      <h4 class="font-semibold text-sm flex items-center gap-2 mb-2">
                        <span class="badge badge-warning badge-sm">📄</span>
                        Fiche équipe
                      </h4>
                      <a :href="$url('/teamSheetPdf.php?id='+item.id_equipe)" target="_blank" class="btn btn-primary btn-sm">
                        📥 Télécharger la fiche
                      </a>
                    </div>
                  </div>

                </div>
              </td>
            </tr>
          </template>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            searchQuery: "",
            expandedRows: new Set(),
            fetchUrl: "/rest/action.php/team/getActiveTeams"
        };
    },
    computed: {
        filteredItems() {
            if (!this.searchQuery) {
                return this.items;
            }
            const query = this.searchQuery.toLowerCase();
            return this.items.filter(item => 
                (item.club && item.club.toLowerCase().includes(query)) ||
                (item.nom_equipe && item.nom_equipe.toLowerCase().includes(query)) ||
                (item.responsable && item.responsable.toLowerCase().includes(query)) ||
                (item.libelle_competition && item.libelle_competition.toLowerCase().includes(query)) ||
                (item.division && item.division.toLowerCase().includes(query))
            );
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.items = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement:", error);
                });
        },
        formatSchedules(gymnasiumsList) {
            if (!gymnasiumsList) return [];
            
            // Utiliser une regex pour séparer les créneaux correctement
            // Pattern: "ville - nom - adresse - coordonnées (horaire)" optionnellement suivi de "(CONTRAINTE...)" et "[remarques]"
            // La regex capture jusqu'aux coordonnées GPS qui sont toujours au format numérique avant les parenthèses
            const schedulePattern = /(.+?) - (.+?) - (.+?) - ([\d.,\s]+) \(([^)]+)\)(\s*\([^)]*CONTRAINTE[^)]*\))?(\s*\[([^\]]+)\])?/g;
            const schedules = [];
            let match;
            
            while ((match = schedulePattern.exec(gymnasiumsList)) !== null) {
                const [, ville, nom, adresse, gps, horaire, contrainte, , remarques] = match;
                schedules.push({
                    ville: ville.trim(),
                    nom: nom.trim(),
                    adresse: adresse.trim(),
                    gps: gps.trim(),
                    horaire: horaire.trim(),
                    hasConstraint: contrainte && contrainte.includes('CONTRAINTE'),
                    remarques: remarques ? remarques.trim() : null
                });
            }
            
            // Si aucun match trouvé, fallback vers l'ancien système
            if (schedules.length === 0) {
                return [{
                    ville: '',
                    nom: gymnasiumsList,
                    adresse: '',
                    gps: '',
                    horaire: '',
                    hasConstraint: false
                }];
            }
            
            return schedules;
        },
        toggleRow(index) {
            if (this.expandedRows.has(index)) {
                this.expandedRows.delete(index);
            } else {
                this.expandedRows.add(index);
            }
            // Force la réactivité pour le Set
            this.expandedRows = new Set(this.expandedRows);
        }
    },
    created() {
        this.fetch();
    },
};