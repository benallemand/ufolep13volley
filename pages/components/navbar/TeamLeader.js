export default {
    template: `
      <div>
        <div class="flex justify-center">
          <a href="/" class="center">
            <img alt="Ufolep" src="../images/logo-2026.png" style="max-height:150px;">
          </a>
        </div>
        <div v-if="isActingAs" class="alert alert-warning flex flex-wrap justify-center items-center gap-2 my-2">
          <span><i class="fas fa-user-secret mr-2"></i>Vous gérez l'équipe en tant que <strong>{{ user?.login }}</strong></span>
          <button class="btn btn-sm btn-neutral" @click="switchBackToClub">
            <i class="fas fa-arrow-left mr-1"></i>revenir au compte club
          </button>
        </div>
        <div class="navbar bg-base-100 shadow-sm flex flex-wrap justify-center gap-2">
            <a href="/" class="btn btn-ghost">
              <span><i class="mr-2 fas fa-arrow-left"></i>retour</span>
            </a>

            <!-- Responsable de club (hors act-as) : choisir un compte responsable à incarner -->
            <div v-if="isClubLeader" class="dropdown">
              <div tabindex="0" role="button" class="btn btn-primary">
                <span><i class="mr-2 fas fa-user-secret"></i>gérer une équipe<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul tabindex="0"
                  class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-72 p-2 shadow max-h-96 overflow-y-auto">
                <li v-if="actAsAccounts.length === 0" class="opacity-60 p-2 text-sm">
                  Aucun compte responsable rattaché. Créez-en un dans « gestion club ».
                </li>
                <li v-for="account in actAsAccounts" :key="account.user_id + '-' + account.id_equipe">
                  <a @click="actAs(account.user_id)" class="flex flex-col items-start gap-1 py-2">
                    <span><i class="fas fa-people-group mr-2"></i>{{ account.team_full_name }}
                      <span class="opacity-60">({{ account.login }})</span></span>
                    <span v-if="parseInt(account.nb_competitions) > 0" class="badge badge-success badge-sm gap-1">
                      <i class="fas fa-trophy"></i>{{ account.competitions }}
                    </span>
                    <span v-else class="badge badge-ghost badge-sm">non engagée cette saison</span>
                  </a>
                </li>
              </ul>
            </div>

            <!-- Responsable d'équipe (ou club en act-as) : sélecteur de son équipe -->
            <div v-if="!isClubLeader" class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-user"></i>mon équipe: {{ currentTeam?.nom_equipe }}<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
                <li v-for="team in teams" :key="team.id_equipe">
                  <a @click="switchTeam(team.id_equipe)">{{ team.nom_equipe }}</a>
                </li>
              </ul>
            </div>

            <!-- Menus per-équipe : cachés pour le responsable de club hors act-as -->
            <template v-if="!isClubLeader">
              <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost">
                  <span>gestion<i class="ml-1 fas fa-chevron-down"/></span>
                </div>
                <ul
                    tabindex="0"
                    class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
                  <li>
                    <router-link to="/players"><span><i class="mr-2 fas fa-user"></i>effectif</span></router-link>
                  </li>
                  <li>
                    <router-link to="/timeslots"><span><i class="mr-2 fas fa-clock"></i>créneaux</span></router-link>
                  </li>
                </ul>
              </div>
              <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost">
                  <span>infos<i class="ml-1 fas fa-chevron-down"/></span>
                </div>
                <ul
                    tabindex="0"
                    class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
                  <li><a href="/rest/action.php/team/download_calendar"><span><i class="mr-2 fas fa-download"></i>calendrier</span></a>
                  </li>
                  <li>
                    <router-link to="/team"><span><i class="mr-2 fas fa-edit"></i>coordonnées</span></router-link>
                  </li>
                  <li>
                    <router-link to="/history"><span><i class="mr-2 fas fa-calendar"></i>historique</span></router-link>
                  </li>
                </ul>
              </div>
              <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost">
                  <span>matchs<i class="ml-1 fas fa-chevron-down"/></span>
                </div>
                <ul
                    tabindex="0"
                    class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
                  <li>
                    <router-link to="/team_matchs"><span><i class="fas fa-volleyball mr-2"></i>
                      equipe</span></router-link>
                  </li>
                  <li>
                    <router-link to="/club_matchs"><span><i class="fas fa-volleyball mr-2"></i>club</span>
                    </router-link>
                  </li>
                </ul>
              </div>
              <router-link to="/messages" class="btn btn-ghost relative">
                <span><i class="mr-2 fas fa-envelope"></i>messages</span>
                <span v-if="unreadCount > 0" class="badge badge-error badge-xs absolute -top-1 -right-1">{{ unreadCount }}</span>
              </router-link>
            </template>

            <!-- Menu club-wide : visible seulement pour le responsable de club -->
            <div v-if="isClubLeader" class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-people-group"></i>gestion club<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul
                  tabindex="0"
                  class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-60 p-2 shadow">
                <li>
                  <router-link to="/club_gymnasium_closures"><span><i class="mr-2 fas fa-lock"></i>fermetures gymnases</span></router-link>
                </li>
                <li>
                  <router-link to="/club_team_unavailability"><span><i class="mr-2 fas fa-ban"></i>indispos équipes</span></router-link>
                </li>
                <li>
                  <router-link to="/club_team_leaders"><span><i class="mr-2 fas fa-user-gear"></i>comptes responsables</span></router-link>
                </li>
              </ul>
            </div>

            <router-link to="/preferences" class="btn btn-ghost">
              <span><i class="mr-2 fas fa-gear"></i>préférences</span>
            </router-link>
            <a href="/rest/action.php/usermanager/logout" class="btn btn-error">
              <span><i class="mr-2 fas fa-right-from-bracket"/>déconnexion</span>
            </a>
        </div>
      </div>`,
    data() {
        return {
            user: null,
            currentTeam: null,
            teams: null,
            unreadCount: 0,
            isClubLeader: false,
            isActingAs: false,
            actAsAccounts: [],
        };
    },
    methods: {
        fetchUnreadCount() {
            axios.get('/session_user.php').then((response) => {
                if (response.data && !response.data.error && response.data.id_equipe) {
                    axios.get(`/rest/action.php/emails/get_team_emails?id_equipe=${response.data.id_equipe}`)
                        .then((r) => {
                            this.unreadCount = r.data.filter(e => !parseInt(e.is_read)).length;
                        })
                        .catch(() => {});
                }
            }).catch(() => {});
        },
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                        return;
                    }
                    this.user = response.data;
                    this.isActingAs = this.user.is_acting_as === true;
                    this.isClubLeader = this.user.profile_name === 'RESPONSABLE_CLUB';
                    if (this.isClubLeader) {
                        // Liste des comptes responsables d'équipe du club, à incarner.
                        axios.get('/rest/action.php/usermanager/getMyClubTeamLeaders')
                            .then((r) => {
                                this.actAsAccounts = r.data.filter((row) => row.user_id);
                            })
                            .catch(() => {});
                    } else {
                        // Responsable d'équipe (ou club en act-as) : ses équipes.
                        axios.get(`/rest/action.php/usermanager/getUserTeams?user_id=${this.user.id_user}`)
                            .then((r) => {
                                this.teams = r.data;
                                this.currentTeam = this.teams.find((item) => item.id_equipe == this.user.id_equipe);
                            });
                    }
                })
                .catch((error) => {
                    console.log(error)
                });
        },
        switchTeam(id_equipe) {
            const formData = new FormData();
            formData.append('id_equipe', id_equipe);
            axios
                .post(`/rest/action.php/usermanager/switchCurrentUserTeam`, formData)
                .then(() => {
                    window.location.reload();
                })
                .catch((error) => {
                    console.error('Erreur lors du changement d\'équipe:', error);
                    alert('Erreur lors du changement d\'équipe');
                });
        },
        actAs(user_id) {
            const formData = new FormData();
            formData.append('target_user_id', user_id);
            axios
                .post(`/rest/action.php/usermanager/switch_to_club_team_leader`, formData)
                .then((response) => {
                    if (response.data.success) {
                        window.location.href = '/pages/my_page.html';
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                })
                .catch((error) => {
                    console.error('Erreur lors du changement de compte:', error);
                    alert(error.response?.data?.message || 'Erreur lors du changement de compte');
                });
        },
        switchBackToClub() {
            axios
                .post(`/rest/action.php/usermanager/switch_back_to_admin`)
                .then((response) => {
                    if (response.data.success) {
                        window.location.href = '/pages/my_page.html';
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                })
                .catch((error) => {
                    console.error('Erreur lors du retour au compte club:', error);
                    alert('Erreur de communication avec le serveur');
                });
        },
    },
    watch: {
        $route() {
            document.activeElement?.blur();
        }
    },
    created() {
        this.fetchUserDetails();
        this.fetchUnreadCount();
    },
};
