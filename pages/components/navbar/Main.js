export default {
    template: `
      <div>
        <div class="flex justify-center">
          <a href="/" class="center">
            <img alt="Ufolep" src="../images/svg/logo-ufolep-vectorizer-no-background.svg" style="max-height:150px;">
          </a>
        </div>
        <div class="navbar bg-base-100 shadow-sm flex flex-wrap justify-center gap-2">
          <a href="/" class="btn btn-ghost">
            <span><i class="mr-2 fas fa-home"/>Accueil</span>
          </a>
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-calendar mr-2"/>Championnats<i class="ml-1 fas fa-chevron-down"/></span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
              <template v-for="group in groupedDivisions">
                <li class="menu-title">{{ group.libelle }}</li>
                <li v-for="division in group.divisions" :key="division.code_competition+division.division">
                  <router-link :to="'/divisions/'+division.code_competition+'/'+ division.division">
                    division {{ division.division }}
                  </router-link>
                </li>
              </template>
            </ul>
          </div>
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-calendar mr-2"/>Coupes<i class="ml-1 fas fa-chevron-down"/></span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
              <li class="menu-title">Coupe Isoardi</li>
              <li>
                <router-link to="/cup-draw/m">
                  <i class="fas fa-random mr-1"/>tirage coupe Isoardi
                </router-link>
              </li>
              <template v-for="group in groupedPools">
                <li class="menu-title">{{ group.libelle }}</li>
                <li>
                  <router-link :to="'/finals/'+group.code_competition_finals">
                    phases finales
                  </router-link>
                </li>
                <li>
                  <a target="_blank" :href="'/rank_for_cup.php?code_competition='+group.code_competition">
                    classement général
                  </a>
                </li>
                <li v-for="division in group.divisions" :key="division.code_competition+division.division">
                  <router-link :to="'/divisions/'+division.code_competition+'/'+ division.division">
                    poule {{ division.division }}
                  </router-link>
                </li>
              </template>
            </ul>
          </div>
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-info-circle mr-2"/>Informations<i class="ml-1 fas fa-chevron-down"/></span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-64 p-2 shadow">
              <li>
                <router-link :to="'/week-matchs'">
                  <i class="fas fa-fire mr-2"/>matchs de la semaine
                </router-link>
              </li>
              <li>
                <router-link :to="'/last-results'">
                  <i class="fas fa-fire mr-2"/>derniers résultats
                </router-link>
              </li>
              <li>
                <router-link :to="'/hall-of-fame'">
                  <i class="fas fa-dollar-sign mr-2"/>palmarès
                </router-link>
              </li>
              <li>
                <router-link :to="'/teams'">
                  <i class="fas fa-book mr-2"/>annuaire
                </router-link>
              </li>
              <li>
                <router-link :to="'/gymnasiums'">
                  <i class="fas fa-map mr-2"/>gymnases
                </router-link>
              </li>
              <li>
                <a href="https://docs.google.com/document/d/1jhAsF6npsuR7Qgf9v0Yw_30NT26Mz4sjTlSrYvyDnGQ/edit?usp=sharing"
                   target="_blank"><i class="fas fa-info-circle mr-2"/>Tuto Responsable
                  d'équipe</a></li>
              <li>
                <router-link :to="'/information'">
                  <i class="fas fa-info-circle mr-2"/>infos utiles
                </router-link>
              </li>
              <li>
                <router-link :to="'/commission'">
                  <i class="fas fa-certificate mr-2"/>commission
                </router-link>
              </li>
              <li>
                <a href="mailto:contact@ufolep13volley.org">
                  <i class="fas fa-envelope mr-2"/>contact@ufolep13volley.org
                </a>
              </li>
              <li>
                <router-link :to="'/accident'">
                  <i class="fas fa-hospital mr-2"/>déclaration de sinistre
                </router-link>
              </li>
              <li>
                <router-link :to="'/web-sites'">
                  <i class="fas fa-home mr-2"/>sites web des clubs
                </router-link>
              </li>
              <li>
                <a href="https://cd.ufolep.org/bouchesdurhone/" target="_blank"><i class="fas fa-link mr-2"/>site de
                  l'UFOLEP 13</a>
              </li>
              <li class="menu-title">Règlements</li>
              <li>
                <a href="https://www.fivb.com/wp-content/uploads/2025/06/FIVB-Volleyball_Rules2025_2028-FR-v04.pdf"
                   target="_blank">
                  <i class="fas fa-link mr-2"/>FIVB
                </a>
              </li>
              <li>
                <router-link :to="'/general-rules'">
                  <i class="fas fa-scale-balanced mr-2"/>général
                </router-link>
              </li>
              <li><a href="/infos_utiles/Media/ReglementFeminin.pdf" target="_blank"><i class="fas fa-link mr-2"/>Championnat féminin</a></li>
              <li><a href="/infos_utiles/Media/ReglementMasculin.pdf" target="_blank"><i class="fas fa-link mr-2"/>Championnat masculin</a></li>
              <li>
                <a href="/infos_utiles/Media/ReglementChampionnatMixte.pdf" target="_blank"><i class="fas fa-link mr-2"/>Championnat mixte</a>
              </li>
              <li><a href="/infos_utiles/Media/ReglementKouryHanna.pdf" target="_blank"><i class="fas fa-link mr-2"/>Coupe Khoury Hanna</a></li>
              <li><a href="/infos_utiles/Media/ReglementIsoardi.pdf" target="_blank"><i class="fas fa-link mr-2"/>Coupe Isoardi</a></li>
            </ul>
          </div>
          <div v-if="isConnected" class="flex gap-1">
            <a class="btn btn-primary"
               v-if="['ADMINISTRATEUR', 'COMMISSION', 'SUPPORT'].includes(this.user.profile_name)"
               href="/admin.php">
              <span><i class="fas fa-gear mr-2"/>administration</span>
            </a>
            <a class="btn btn-primary"
               v-if="!['ADMINISTRATEUR', 'COMMISSION', 'SUPPORT'].includes(this.user.profile_name)"
               href="/pages/my_page.html">
              <span><i class="fas fa-user mr-2"/>{{ this.user.login }}</span>
            </a>
            <a class="btn btn-error" href="/rest/action.php/usermanager/logout">
              <span><i class="fas fa-right-from-bracket mr-2"/>déconnexion</span>
            </a>
          </div>
          <div v-if="!isConnected" class="flex gap-1">
            <router-link :to="'/login'">
              <a class="btn btn-info">
                <span><i class="fas fa-right-to-bracket mr-2"/>connexion</span>
              </a>
            </router-link>
          </div>
          <a class="btn btn-secondary" href="/register.php">
            <span><i class="fas fa-user-plus mr-2"/>inscrire une équipe</span>
          </a>
        </div>
      </div>`,
    data() {
        return {
            divisions: [],
            user: null,
        };
    },
    computed: {
        groupedDivisions() {
            return this.divisions.filter((division) => {
                return ['m', 'f', 'mo'].includes(division.code_competition);
            }).reduce((acc, division) => {
                if (!acc[division.code_competition]) {
                    acc[division.code_competition] = {
                        libelle: division.libelle_competition,
                        code_competition: division.code_competition,
                        divisions: [],
                    };
                }
                acc[division.code_competition].divisions.push(division);
                return acc;
            }, {});
        },
        groupedPools() {
            return this.divisions.filter((division) => {
                return ['kh', 'c'].includes(division.code_competition);
            }).reduce((acc, division) => {
                if (!acc[division.code_competition]) {
                    acc[division.code_competition] = {
                        libelle: division.libelle_competition,
                        code_competition: division.code_competition,
                        code_competition_finals: division.code_competition === 'c' ? 'cf' : 'kf',
                        divisions: [],
                    };
                }
                acc[division.code_competition].divisions.push(division);
                return acc;
            }, {});
        }, isConnected() {
            return this.user !== null;
        },
    },
    methods: {
        fetch() {
            axios
                .get(`/rest/action.php/rank/getDivisions`)
                .then((response) => {
                    this.divisions = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement :", error);
                });
        }, fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                    } else {
                        this.user = response.data;
                    }
                })
                .catch((error) => {
                    console.log(error)
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
        this.fetch();
    },
};