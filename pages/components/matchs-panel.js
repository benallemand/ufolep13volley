export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <li v-for="match in sortedMatchs" :key="match.id_match" class="card shadow-md bg-base-100">
            <div class="card-body">
              <h2 class="card-title text-lg font-bold font-medium">{{ match.code_match }}
                <span>
                        <a :href="'/match.php?id_match='+match.id_match" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                        <a :href="'mailto:'+match.email_dom+','+match.email_ext" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                        </a>
                    </span>
              </h2>
              <p class="text-sm font-medium">
                <span class="">Compétition :</span> {{ match.libelle_competition }}
              </p>
              <p class="text-sm font-medium">
                <span class="">Division :</span> {{ match.division }}
              </p>
              <p class="text-sm font-medium">
                <span class="">Rencontre :</span>
                <span>{{ match.equipe_dom }} <span class="font-medium"><a
                    :href="'mailto:'+match.email_dom" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                    </a></span>
                    </span>
                vs
                <span>{{ match.equipe_ext }} <span class="font-medium"><a
                    :href="'mailto:'+match.email_ext" target="_blank" class="link link-info hover:underline">
                            <i class="fas fa-envelope ml-1"></i>
                    </a></span></span>
              </p>
              <p class="text-sm font-medium">
                <span class="">Score :</span> {{ match.score_equipe_dom }} - {{
                  match.score_equipe_ext
                }}
              </p>
              <p class="text-sm font-medium">
                <span v-if="match.certif === 1" class="badge badge-success">Certifié</span>
              </p>
              <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_sign_team_dom + match.is_sign_team_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_sign_team_dom + match.is_sign_team_ext === 2 ? 'fiche équipe signée' : 'fiche équipe non signée'
                      }}
                      <span class="font-medium">
                            <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank" class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
              </p>
              <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_sign_match_dom + match.is_sign_match_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_sign_match_dom + match.is_sign_match_ext === 2 ? 'feuille de match signée' : 'feuille de match non signée'
                      }}
                      <span class="font-medium">
                            <a :href="'/match.php?id_match='+match.id_match" target="_blank" class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
              </p>
              <p class="text-sm">
                    <span
                        class="badge"
                        :class="match.is_survey_filled_dom + match.is_survey_filled_ext === 2 ? 'badge-success' : 'badge-error'">
                        {{
                        match.is_survey_filled_dom + match.is_survey_filled_ext === 2 ? 'sondage rempli' : 'sondage non rempli'
                      }}
                    </span>
              </p>
              <p class="text-sm">
                    <span
                        class="badge badge-error"
                        v-if="match.has_forbidden_player === 1">
                        pb licence(s)
                        <span class="font-medium">
                            <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank" class="link link-info hover:underline">
                                <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </span>
                    </span>
              </p>
              <p class="text-sm font-medium">
                <span class="">Date :</span> {{ match.date_reception }}
              </p>
            </div>
          </li>
        </ul>
      </div>
    `,
    props: {
        fetchUrl: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            matchs: [],
        };
    },
    computed: {
        sortedMatchs() {
            return this.matchs.sort((a, b) => a.date_reception_raw - b.date_reception_raw);
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.matchs = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
    },
    created() {
        this.fetch();
    },
};