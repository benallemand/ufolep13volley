export default {
    template: `
      <div class="card-body">
        <h2 class="card-title text-lg font-bold font-medium">{{ match.code_match }}
          <span>
            <a :href="'/match.php?id_match='+match.id_match" target="_blank"
               class="link link-info hover:underline">
                <i class="fas fa-external-link-alt ml-1"></i>
            </a>
            <a :href="'mailto:'+match.email_dom+','+match.email_ext" target="_blank"
               class="link link-info hover:underline">
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
        <p class="text-sm text-gray-600 font-medium"
           v-if="match.score_equipe_dom > 0 || match.score_equipe_ext > 0">
          <span class="text-gray-800">Score :</span>
          <span class="text-xl">{{ match.score_equipe_dom }} - {{ match.score_equipe_ext }}</span>
          <span v-if="match.set_1_dom > 0 || match.set_1_ext > 0">{{ match.set_1_dom }}/{{ match.set_1_ext }}
          </span>
          <span v-if="match.set_2_dom > 0 || match.set_2_ext > 0">{{ match.set_2_dom }}/{{ match.set_2_ext }}
          </span>
          <span v-if="match.set_3_dom > 0 || match.set_3_ext > 0">{{ match.set_3_dom }}/{{ match.set_3_ext }}
          </span>
          <span v-if="match.set_4_dom > 0 || match.set_4_ext > 0">{{ match.set_4_dom }}/{{ match.set_4_ext }}
          </span>
          <span v-if="match.set_5_dom > 0 || match.set_5_ext > 0">{{ match.set_5_dom }}/{{ match.set_5_ext }}
          </span>
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
                  <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                     class="link link-info hover:underline">
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
                  <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                     class="link link-info hover:underline">
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
                  <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                     class="link link-info hover:underline">
                      <i class="fas fa-external-link-alt ml-1"></i>
                  </a>
              </span>
          </span>
        </p>
        <p class="text-sm font-medium">
          <span class="">Date :</span> {{ match.date_reception }}
        </p>
      </div>
    `,
    props: {
        match: {
            type: Object,
            required: true,
        }
    }
};
