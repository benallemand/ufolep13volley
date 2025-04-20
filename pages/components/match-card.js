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
          <span class="">Division :</span>
          <span>
            <a :href="'/new_site/#/championship/'+match.code_competition+'/'+match.division"
               class="link link-info hover:underline"
               target="_blank">
                 <span>{{ match.division }}</span><i class="fas fa-external-link-alt ml-1"></i>
            </a>
          </span>
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
          <span v-if="match.is_sign_team_dom + match.is_sign_team_ext === 2" class="badge badge-success">
            fiche équipe signée
            <span class="text-blue-500 font-medium">
                <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                    <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </span>
          </span>
          <span v-if="match.is_sign_team_dom === 0" class="badge badge-error text-xs">
            {{ match.equipe_dom }} fiche équipe non signée
            <span class="text-blue-500 font-medium">
                <a :href="'/team_sheets.php?id_match='+match.id_match"
                   target="_blank"
                   class="link link-info hover:underline">
                      <i class="fas fa-external-link-alt ml-1"></i>
                  </a>
              </span>
          </span>
          <span v-if="match.is_sign_team_ext === 0" class="badge badge-error text-xs">
            {{ match.equipe_ext }} fiche équipe non signée
            <span class="text-blue-500 font-medium">
                <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                      <i class="fas fa-external-link-alt ml-1"></i>
                  </a>
              </span>
          </span>
          <span v-if="match.is_sign_match_dom + match.is_sign_match_ext === 2" class="badge badge-success">
            feuille de match signée
            <span class="text-blue-500 font-medium">
                <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                    <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </span>
          </span>
          <span v-if="match.is_sign_match_dom === 0" class="badge badge-error text-xs">
            {{ match.equipe_dom }} feuille de match non signée
            <span class="text-blue-500 font-medium">
                <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                    <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </span>
          </span>
          <span v-if="match.is_sign_match_ext === 0" class="badge badge-error text-xs">
            {{ match.equipe_ext }} feuille de match non signée
            <span class="text-blue-500 font-medium">
                <a :href="'/match.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                    <i class="fas fa-external-link-alt ml-1"></i>
                </a>
            </span>
          </span>
          <span v-if="match.is_survey_filled_dom + match.is_survey_filled_ext === 2"
                class="badge badge-success">
            sondage rempli
          </span>
          <span v-if="match.is_survey_filled_dom === 0" class="badge badge-error text-xs">
            {{ match.equipe_dom }} sondage non rempli
          </span>
          <span v-if="match.is_survey_filled_ext === 0" class="badge badge-error text-xs">
            {{ match.equipe_ext }} sondage non rempli
          </span>
          <span v-if="match.has_forbidden_player === 1" class="badge badge-error">
              pb licence(s)
            <span class="text-blue-500 font-medium">
                <a :href="'/team_sheets.php?id_match='+match.id_match" target="_blank"
                   class="link link-info hover:underline">
                      <i class="fas fa-external-link-alt ml-1"></i>
                  </a>
              </span>
          </span>
          <span class="badge badge-neutral"
                v-if="['ASKED_BY_DOM', 'ASKED_BY_EXT'].includes(match.report_status)">report demandé
          </span>
          <span v-if="['ACCEPTED_BY_DOM', 'ACCEPTED_BY_EXT'].includes(match.report_status)"
                class="badge badge-accent">report accepté</span>
          <span v-if="['REFUSED_BY_DOM', 'REFUSED_BY_EXT', 'REFUSED_BY_ADMIN'].includes(match.report_status)"
                class="badge badge-error">report refusé</span>
          <span class="badge badge-error" v-if="match.is_forfait === 1">forfait</span>
        </p>
        <p class="text-sm font-medium">
          <span class="">Date :</span> {{ match.date_reception }}
        </p>
        <div v-if="!['null', ''].includes(match.note)" class="collapse">
          <input type="checkbox"/>
          <div class="collapse-title text-xs font-medium">voir les commentaires</div>
          <div class="collapse-content">
            <p>{{ match.note }}</p>
          </div>
        </div>
        <!-- Actions supplémentaires (pour admin) -->
        <slot name="actions"></slot>
      </div>
    `,
    props: {
        match: {
            type: Object,
            required: true,
        }
    }
};
