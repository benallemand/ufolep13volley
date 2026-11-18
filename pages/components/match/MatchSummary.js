/**
 * Récapitulatif d'un match (logo, compétition, équipes, fiches/feuille/fair-play).
 * Remplace l'include PHP summary.php.
 *
 * Les boutons "signer" remontent des événements vers le parent
 * (sign-match / sign-team-sheets) car la logique de signature vit dans la page
 * qui possède reloadData() et l'état du match.
 *
 * Un bouton par action à faire (issue #488) : le site signe toujours pour
 * l'équipe du compte, quel que soit le bouton cliqué. Le responsable n'a
 * donc de boutons que dans la colonne de son équipe ; la colonne adverse
 * montre l'état (fait / en attente). La commission, qui signe pour les deux
 * équipes d'un coup, a un seul bouton par ligne.
 */
import {actingTeamId} from '/utils/reportUtils.js';
import {nextSignatureStep} from '/utils/signatureSteps.js';

export default {
    props: {
        matchData: {type: Object, required: true},
        // compte connecté : l'encadré « ce qu'il vous reste à faire » ne
        // s'affiche que pour une équipe qui joue le match (issue #486)
        user: {type: Object, default: null},
    },
    emits: ['sign-match', 'sign-team-sheets'],
    data() {
        return {sides: ['dom', 'ext']};
    },
    computed: {
        myTeamId() {
            return actingTeamId(this.matchData, this.user);
        },
        // 'dom' / 'ext' : la colonne de l'équipe du compte ; null sinon
        mySide() {
            if (this.myTeamId === null) return null;
            if (Number(this.matchData.id_equipe_dom) === this.myTeamId) return 'dom';
            if (Number(this.matchData.id_equipe_ext) === this.myTeamId) return 'ext';
            return null;
        },
        // admin qui ne joue pas ce match : il signe pour les deux équipes (#419)
        isCommission() {
            return !!this.user?.is_admin && this.mySide === null;
        },
        rows() {
            const m = this.matchData;
            return [
                {key: 'team', label: 'Fiche équipes', flag: 'is_sign_team', event: 'sign-team-sheets',
                    ready: !!m.is_match_player_filled, url: '/team_sheets.html', icon: 'fa-user'},
                {key: 'match', label: 'Feuille de match', flag: 'is_sign_match', event: 'sign-match',
                    ready: !!m.is_match_score_filled, url: '/match.html', icon: 'fa-volleyball'},
                {key: 'survey', label: 'Fair-play', flag: 'is_survey_filled', event: null,
                    ready: false, url: '/survey.html', icon: 'fa-square-poll-vertical'},
            ];
        },
        nextStep() {
            return nextSignatureStep(this.matchData, this.myTeamId);
        },
        // classes écrites en entier, pour que Tailwind les génère
        nextStepClass() {
            return {
                warning: 'alert-warning', info: 'alert-info', error: 'alert-error', success: 'alert-success',
            }[this.nextStep?.level] || 'alert-info';
        },
    },
    methods: {
        done(row, side) {
            return Number(this.matchData[`${row.flag}_${side}`]) === 1;
        },
        doneBoth(row) {
            return this.done(row, 'dom') && this.done(row, 'ext');
        },
    },
    template: `
      <div>
        <div class="flex justify-center items-center m-4">
          <img :src="$url('/images/ufolep-logo-cmjn-BOUCHES-DU.jpg')"
               class="rounded-lg border-4 border-gray-300 shadow-lg" alt=""/>
        </div>
        <div class="flex items-center justify-between mb-4">
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ matchData.libelle_competition }}</span>
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
            <span>
              <a :href="$url('/pages/home.html#/divisions/' + matchData.code_competition + '/' + matchData.division)"
                 class="link" target="_blank">
                Division {{ matchData.division }}
              </a>
            </span>
          </span>
        </div>
        <div class="flex items-center justify-center mb-4">
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-2xl font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ matchData.code_match }}</span>
        </div>
        <div class="flex items-center justify-center mb-4">
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-2xl font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 font-bold">{{ matchData.equipe_dom }} vs
            {{ matchData.equipe_ext }}</span>
        </div>
        <div class="flex items-center justify-between mb-4">
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
            {{ matchData.gymnasium }}
          </span>
          <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ matchData.date_reception }} {{ matchData.heure_reception }}</span>
        </div>

        <div v-if="nextStep" role="status" data-testid="next-step"
             :class="['alert', 'mb-4', nextStepClass]">
          <i :class="['fa-solid', nextStep.icon]"></i>
          <div>
            <div class="font-bold">{{ nextStep.title }}</div>
            <div v-if="nextStep.detail" class="text-sm">{{ nextStep.detail }}</div>
            <div v-if="nextStep.deadline" class="text-sm">{{ nextStep.deadline }}</div>
          </div>
        </div>

        <div class="border border-4" data-testid="match-steps">
          <div class="flex flex-row mb-4">
            <span class="basis-1/3"></span>
            <span v-for="side in sides" :key="side" class="basis-1/3">
              <a :href="$url('/pages/home.html#/teams/' + matchData['id_equipe_' + side])"
                 class="link" target="_blank" :class="{'font-bold': side === mySide}">
                {{ matchData['equipe_' + side] }}
              </a>
              <span v-if="side === mySide" class="badge badge-ghost badge-sm ml-1">votre équipe</span>
            </span>
          </div>
          <div v-for="row in rows" :key="row.key" class="flex flex-row mb-4" :data-testid="'step-' + row.key">
            <div class="basis-1/3 flex flex-col gap-2">
              <span>{{ row.label }}</span>
              <!-- la commission signe pour les deux équipes d'un coup : un seul bouton -->
              <template v-if="isCommission && row.event && !doneBoth(row)">
                <button v-if="row.ready" class="btn btn-secondary btn-sm" type="button" @click="$emit(row.event)">
                  <i class="fas fa-signature mr-1"></i><span>signer (2 équipes)</span>
                </button>
                <a v-else :href="$url(row.url + '?id_match=' + matchData.id_match)">
                  <button class="btn btn-primary btn-sm" type="button">
                    <i :class="['fa-solid', row.icon]"></i><span>remplir</span>
                  </button>
                </a>
              </template>
            </div>
            <div v-for="side in sides" :key="side" class="basis-1/3 flex gap-4 items-center">
              <input v-if="done(row, side)" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                     checked disabled/>
              <template v-else-if="side === mySide">
                <button v-if="row.event && row.ready" class="btn btn-secondary" type="button"
                        @click="$emit(row.event)">
                  <i class="fas fa-signature mr-1"></i><span>signer</span>
                </button>
                <a v-else :href="$url(row.url + '?id_match=' + matchData.id_match)">
                  <button class="btn btn-primary" type="button">
                    <i :class="['fa-solid', row.icon]"></i><span>remplir</span>
                  </button>
                </a>
              </template>
              <span v-else class="badge badge-ghost">en attente</span>
            </div>
          </div>
        </div>
      </div>
    `,
};
