export default {
    template: `
      <div class="bg-base-100 min-h-screen">
        <!-- Header avec navigation -->
        <div class="bg-primary text-primary-content p-4 mb-6">
          <div class="container mx-auto">
            <div class="flex items-center gap-4">
              <button @click="goBack" class="btn btn-ghost btn-sm">
                ← Retour à l'annuaire
              </button>
              <div class="divider divider-horizontal"></div>
              <div v-if="team">
                <h1 class="text-2xl font-bold">{{ team.nom_equipe }}</h1>
                <div class="text-sm opacity-90">{{ team.club }} - {{ team.libelle_competition }}{{ team.division }}
                </div>
              </div>
              <div v-else class="skeleton h-8 w-64"></div>
            </div>
          </div>
        </div>

        <!-- Contenu principal -->
        <div class="container mx-auto px-4">
          <div v-if="loading" class="flex justify-center py-12">
            <span class="loading loading-spinner loading-lg"></span>
          </div>

          <div v-else-if="error" class="alert alert-error">
            <span>{{ error }}</span>
          </div>

          <div v-else-if="team" class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Informations générales -->
            <div class="card bg-base-200 shadow-lg">
              <div class="card-body">
                <h2 class="card-title flex items-center gap-2">
                  <span class="badge badge-secondary">👥</span>
                  Informations générales
                </h2>

                <div class="space-y-4">
                  <div>
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Club</div>
                    <div class="text-lg font-semibold">{{ team.club }}</div>
                  </div>
                  <div>
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Équipe</div>
                    <div class="text-xl font-bold text-primary">{{ team.nom_equipe }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact -->
            <div class="card bg-base-200 shadow-lg">
              <div class="card-body">
                <h2 class="card-title flex items-center gap-2">
                  <span class="badge badge-info">📞</span>
                  Contact
                </h2>

                <div class="space-y-4">
                  <div v-if="team.responsable">
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Responsable</div>
                    <div class="font-medium">{{ team.responsable }}</div>
                  </div>

                  <div v-if="team.telephone_1 || team.telephone_2" class="space-y-2">
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Téléphone</div>
                    <div v-if="team.telephone_1" class="flex items-center gap-2">
                      <span class="badge badge-ghost badge-sm">📞</span>
                      <a :href="'tel:' + team.telephone_1" class="link">{{ team.telephone_1 }}</a>
                    </div>
                    <div v-if="team.telephone_2" class="flex items-center gap-2">
                      <span class="badge badge-ghost badge-sm">📱</span>
                      <a :href="'tel:' + team.telephone_2" class="link">{{ team.telephone_2 }}</a>
                    </div>
                  </div>

                  <div v-if="team.email">
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Email</div>
                    <a :href="'mailto:' + team.email" class="link break-all">{{ team.email }}</a>
                  </div>

                  <div v-if="team.web_site">
                    <div class="text-xs text-base-content/70 uppercase tracking-wide mb-1">Site web</div>
                    <a :href="team.web_site" target="_blank" class="link link-primary flex items-center gap-2">
                      <span class="badge badge-info badge-sm">🌐</span>
                      {{ team.web_site }}
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Photo d'équipe -->
            <div v-if="team.path_photo" class="card bg-base-200 shadow-lg">
              <div class="card-body">
                <h2 class="card-title flex items-center gap-2">
                  <span class="badge badge-success">📷</span>
                  Photo d'équipe
                </h2>
                <figure class="px-4 pt-4">
                  <img :src="'/' + team.path_photo" :alt="'Photo de ' + team.nom_equipe"
                       class="rounded-xl max-h-64 mx-auto shadow-lg"/>
                </figure>
              </div>
            </div>

            <!-- Actions -->
            <div class="card bg-base-200 shadow-lg">
              <div class="card-body">
                <h2 class="card-title flex items-center gap-2">
                  <span class="badge badge-warning">📄</span>
                  Documents
                </h2>
                <div class="card-actions">
                  <a :href="'/teamSheetPdf.php?id=' + team.id_equipe" target="_blank"
                     class="btn btn-primary">
                    <span class="badge badge-ghost badge-sm">📥</span>
                    Télécharger la fiche équipe
                  </a>
                </div>
              </div>
            </div>

          </div>

          <!-- Créneaux (pleine largeur) -->
          <div v-if="team && team.gymnasiums_list" class="mt-8">
            <div class="card bg-base-200 shadow-lg">
              <div class="card-body">
                <h2 class="card-title flex items-center gap-2 mb-6">
                  <span class="badge badge-primary">📍</span>
                  Créneaux d'entraînement
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                  <div v-for="(schedule, index) in formatSchedules(team.gymnasiums_list)"
                       :key="'detail-schedule-' + index"
                       class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body p-4">
                      <div class="flex items-center gap-2 mb-3">
                        <div class="badge badge-primary badge-sm">📍</div>
                        <h3 class="font-semibold text-sm">{{ schedule.ville }}</h3>
                      </div>

                      <div class="space-y-2">
                        <div class="font-medium">{{ schedule.nom }}</div>
                        <div class="text-sm text-base-content/70">{{ schedule.adresse }}</div>

                        <div class="flex items-center gap-2 text-sm">
                          <div class="badge badge-ghost badge-xs">🕐</div>
                          <span>{{ schedule.horaire }}</span>
                          <span v-if="schedule.hasConstraint" class="badge badge-warning badge-sm">⚠️</span>
                        </div>

                        <div v-if="schedule.remarques" class="text-xs text-info italic mt-2">
                          📝 {{ schedule.remarques }}
                        </div>

                        <div class="card-actions pt-2">
                          <a :href="'https://www.google.com/maps/place/' + schedule.gps"
                             target="_blank"
                             class="btn btn-primary btn-sm btn-block">
                            🗺️ Voir sur Google Maps
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    `,
    data() {
        return {
            team: null,
            loading: true,
            error: null
        };
    },
    watch: {
        '$route.params.id': {
            handler(newId) {
                if (newId) {
                    this.fetchTeam(newId);
                }
            },
            immediate: true
        }
    },
    methods: {
        async fetchTeam(teamId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(`/rest/action.php/team/getTeam?id=${teamId}`);
                this.team = response.data;
            } catch (error) {
                console.error('Erreur lors du chargement de l\'équipe:', error);
                this.error = 'Impossible de charger les détails de l\'équipe.';
            } finally {
                this.loading = false;
            }
        },

        formatSchedules(gymnasiumsList) {
            if (!gymnasiumsList) return [];

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

        goBack() {
            this.$router.push('/teams');
        }
    }
};
