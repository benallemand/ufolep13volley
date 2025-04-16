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
              <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 mt-3 w-52 p-2 shadow">
                <li><a>Profile</a></li>
                <li><a>Settings</a></li>
                <li><a>Logout</a></li>
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