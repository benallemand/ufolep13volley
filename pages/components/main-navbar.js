export default {
    template: `
      <div class="navbar bg-base-100 shadow-sm">
        <div class="navbar-start">
          <a class="btn btn-ghost" href="/">
            <span><i class="fas fa-home mr-2"/>Accueil</span>
          </a>
        </div>
        <div class="navbar-center flex gap-2">
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-calendar mr-2"/>Championnats</span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
              <template v-for="group in groupedDivisions">
                <li class="menu-title">{{ group.libelle }}</li>
                <li v-for="division in group.divisions" :key="division.code_competition+division.division">
                  <a :href="'/pages/division.html?division=' + division.division + '&code_competition=' + division.code_competition">
                    division {{ division.division }}
                  </a>
                </li>
              </template>
            </ul>
          </div>
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-calendar mr-2"/>Coupes</span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
              <template v-for="group in groupedPools">
                <li class="menu-title">{{ group.libelle }}</li>
                <li>
                  <a :href="'/pages/finals.html?code_competition=' + group.code_competition_finals">phases finales</a>
                </li>
                <li>
                  <a target="_blank" :href="'/rank_for_cup.php?code_competition='+group.code_competition">
                    classement général
                  </a>
                </li>
                <li v-for="division in group.divisions" :key="division.code_competition+division.division">
                  <a :href="'/pages/division.html?division=' + division.division + '&code_competition=' + division.code_competition">
                    poule {{ division.division }}
                  </a>
                </li>
              </template>
            </ul>
          </div>
          <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost">
              <span><i class="fas fa-info-circle mr-2"/>Informations</span>
            </div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-64 p-2 shadow">
              <li><a href="#weekMatches"><i class="fas fa-fire mr-2"/>Matchs de la semaine</a></li>
              <li><a href="#lastResults"><i class="fas fa-fire mr-2"/>Derniers résultats</a></li>
              <li><a href="#hallOfFame"><i class="fas fa-dollar-sign mr-2"/>Palmarès</a></li>
              <li><a href="#phonebooks"><i class="fas fa-book mr-2"/>Annuaire</a></li>
              <li><a href="#gymnasiums"><i class="fas fa-map mr-2"/>Gymnases</a></li>
              <li>
                <a href="https://docs.google.com/document/d/1jhAsF6npsuR7Qgf9v0Yw_30NT26Mz4sjTlSrYvyDnGQ/edit?usp=sharing"
                   target="_blank"><i class="fas fa-info-circle mr-2"/>Tuto Responsable
                  d'équipe</a></li>
              <li><a href="#usefulInformations"><i class="fas fa-info-circle mr-2"/>Infos
                utiles</a></li>
              <li><a href="#commission"><i class="fas fa-certificate mr-2"/>Commission</a></li>
              <li>
                <a href="mailto:contact@ufolep13volley.org">
                  <i class="fas fa-envelope mr-2"/>contact@ufolep13volley.org
                </a>
              </li>
              <li><a href="#accident"><i class="fas fa-hospital mr-2"/>Déclaration de sinistre</a></li>
              <li class="menu-title">Liens</li>
              <li><a href="#webSites" target="_blank"><i class="fas fa-link mr-2"/> Sites web des clubs</a></li>
              <li>
                <a href="http://ufolep13.org/" target="_blank"><i class="fas fa-link mr-2"/>Site de l'UFOLEP 13</a>
              </li>
              <li class="menu-title">Règlements</li>
              <li>
                <a href="https://www.fivb.com/wp-content/uploads/2024/03/FIVB-Volleyball_Rules2021_2024-FR-v2a.pdf"
                   target="_blank">
                  FIVB
                </a>
              </li>
              <li><a href="#generalRules" target="_blank">Général</a></li>
              <li><a href="../infos_utiles/Media/ReglementFeminin.pdf" target="_blank">Championnat féminin</a></li>
              <li><a href="../infos_utiles/Media/ReglementMasculin.pdf" target="_blank">Championnat masculin</a></li>
              <li>
                <a href="../infos_utiles/Media/ReglementChampionnatMixte.pdf" target="_blank">Championnat mixte</a>
              </li>
              <li><a href="../infos_utiles/Media/ReglementKouryHanna.pdf" target="_blank">Coupe Khoury Hanna</a></li>
              <li><a href="../infos_utiles/Media/ReglementIsoardi.pdf" target="_blank">Coupe Isoardi</a></li>
              <!--              <li>-->
              <!--                <a href="../infos_utiles/Media/ReglementCoupeFeminine6x6.pdf" target="_blank">Coupe Féminine 6x6</a>-->
              <!--              </li>-->
            </ul>
          </div>
        </div>
        <div class="navbar-end">
          <a class="btn btn-ghost" href="/">
            <span><i class="fas fa-right-from-bracket mr-2"/>Connexion</span>
          </a>
        </div>
      </div>`,
    data() {
        return {
            divisions: [],
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
        },
    },
    created() {
        this.fetch();
    },
};