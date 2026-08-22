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

            <!-- Compte rattaché à plusieurs clubs : club courant des écrans club -->
            <div v-if="clubs.length > 1" class="dropdown">
              <div tabindex="0" role="button" class="btn btn-ghost">
                <span><i class="mr-2 fas fa-people-group"></i>club: {{ currentClub?.nom }}<i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul tabindex="0"
                  class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-72 p-2 shadow max-h-96 overflow-y-auto">
                <li v-for="club in clubs" :key="club.id">
                  <a @click="switchClub(club.id)" :class="{ 'font-bold': club.id == user?.id_club }">
                    {{ club.nom }}
                  </a>
                </li>
              </ul>
            </div>

            <!-- Sélecteur d'équipe : équipes du club courant (plus les équipes
                 propres au compte), y compris celles sans compte responsable -->
            <div v-if="selectableTeams.length > 0" class="dropdown">
              <div tabindex="0" role="button" :class="currentTeam ? 'btn btn-ghost' : 'btn btn-primary'">
                <span><i class="mr-2 fas fa-user"></i>
                  <template v-if="currentTeam">équipe: {{ currentTeam.nom_equipe }}</template>
                  <template v-else>choisir une équipe</template>
                  <i class="ml-1 fas fa-chevron-down"/></span>
              </div>
              <ul tabindex="0"
                  class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 p-2 shadow flex-nowrap
                         w-80 max-w-[calc(100vw-2rem)] max-h-96 overflow-y-auto overflow-x-hidden">
                <template v-for="group in teamsByClub" :key="group.id_club">
                  <li v-if="teamsByClub.length > 1" class="menu-title px-2 py-1">{{ group.club_name }}</li>
                  <li v-for="team in group.teams" :key="team.id_equipe">
                    <a @click="switchTeam(team.id_equipe)" class="flex items-start gap-3 py-2"
                       :class="{ 'active': team.id_equipe == user?.id_equipe }">
                      <!-- pastille d'engagement : verte si l'équipe est engagée cette saison -->
                      <i class="fas fa-circle text-[0.55rem] mt-1.5 shrink-0"
                         :class="parseInt(team.nb_competitions) > 0 ? 'text-success' : 'opacity-30'"></i>
                      <span class="min-w-0 flex-1">
                        <span class="block break-words leading-tight"
                              :class="{ 'font-bold': team.id_equipe == user?.id_equipe }">
                          {{ team.nom_equipe }}
                        </span>
                        <span class="block text-xs opacity-70 break-words leading-tight mt-0.5">
                          {{ parseInt(team.nb_competitions) > 0 ? team.competitions : 'non engagée cette saison' }}
                        </span>
                        <span v-if="!parseInt(team.has_leader_account)"
                              class="block text-xs text-warning break-words leading-tight mt-0.5">
                          <i class="fas fa-user-slash mr-1"></i>aucun compte responsable
                        </span>
                      </span>
                    </a>
                  </li>
                </template>
              </ul>
            </div>

            <!-- Menus per-équipe : visibles dès que le compte gère une équipe (cumul possible avec le rôle club) -->
            <template v-if="isTeamLeader">
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
                  <router-link to="/club_registrations"><span><i class="mr-2 fas fa-file-signature"></i>inscriptions</span></router-link>
                </li>
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
            unreadCount: 0,
            isClubLeader: false,
            isTeamLeader: false,
            isActingAs: false,
            // équipes sélectionnables : celles du compte (users_teams) + celles
            // des clubs gérés (users_clubs), avec ou sans compte responsable
            manageableTeams: [],
            clubs: [],
        };
    },
    computed: {
        // le sélecteur reste cadré sur le club courant ; les équipes rattachées
        // au compte lui-même restent visibles même si elles sont dans un autre club
        selectableTeams() {
            if (!this.user?.id_club) {
                return this.manageableTeams;
            }
            return this.manageableTeams.filter(
                (team) => team.id_club == this.user.id_club || parseInt(team.is_my_team));
        },
        currentTeam() {
            return this.manageableTeams.find((team) => team.id_equipe == this.user?.id_equipe) || null;
        },
        currentClub() {
            return this.clubs.find((club) => club.id == this.user?.id_club) || null;
        },
        // regroupement par club : n'affiche un intitulé que si le compte voit
        // des équipes de plusieurs clubs (ses propres équipes hors club courant)
        teamsByClub() {
            const groups = [];
            this.selectableTeams.forEach((team) => {
                let group = groups.find((g) => g.id_club === team.id_club);
                if (!group) {
                    group = {id_club: team.id_club, club_name: team.club_name, teams: []};
                    groups.push(group);
                }
                group.teams.push(team);
            });
            return groups;
        },
    },
    methods: {
        fetchUnreadCount() {
            axios.get(`/session_user.php?_dc=${Date.now()}`).then((response) => {
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
                .get(`/session_user.php?_dc=${Date.now()}`)
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                        return;
                    }
                    this.user = response.data;
                    this.isActingAs = this.user.is_acting_as === true;
                    // rôles cumulables (issue #245) : un compte peut gérer un club ET des équipes
                    this.isClubLeader = this.user.is_club_leader === true;
                    this.isTeamLeader = this.user.is_team_leader === true;
                    // équipes sélectionnables, tous clubs gérés confondus
                    axios.get(`/rest/action.php/usermanager/getMyManageableTeams?_dc=${Date.now()}`)
                        .then((r) => {
                            this.manageableTeams = r.data;
                        })
                        .catch(() => {});
                    if (this.isClubLeader) {
                        // sélecteur de club courant si le compte gère plusieurs clubs
                        axios.get(`/rest/action.php/club/getMyClubs?_dc=${Date.now()}`)
                            .then((r) => {
                                this.clubs = r.data;
                            })
                            .catch(() => {});
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
        switchClub(id_club) {
            const formData = new FormData();
            formData.append('id_club', id_club);
            axios
                .post(`/rest/action.php/usermanager/switchCurrentUserClub`, formData)
                .then(() => {
                    window.location.reload();
                })
                .catch((error) => {
                    console.error('Erreur lors du changement de club:', error);
                    alert(error.response?.data?.message || 'Erreur lors du changement de club');
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
