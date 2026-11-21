/**
 * Signatures d'une étape de la page de match (#490) : fiche équipes
 * (`kind` 'team') ou feuille de match ('match').
 *
 * L'état des deux équipes, puis l'action du compte connecté : signer, ou
 * annuler sa signature. Les deux passent par une confirmation dans la page
 * (pas de `window.confirm`, peu lisible sur téléphone). Mêmes règles que
 * `MatchMgr::is_action_allowed` ; le serveur reste juge.
 */
import {matchState} from '/utils/signatureSteps.js';

const ATTEST = {
    team: "Je confirme la liste des joueurs et joueuses présent(e)s. Signer fige les présents des deux équipes. "
        + "Il n'est plus nécessaire de fournir de fiche équipe papier.",
    match: "Je confirme le score saisi sur le site. Signer fige le score. "
        + "Il n'est plus nécessaire de fournir de feuille de match papier.",
};

export default {
    props: {
        matchData: {type: Object, required: true},
        kind: {type: String, required: true},
        // côté de l'équipe du compte ; null pour la commission
        mySide: {type: String, default: null},
        isCommission: {type: Boolean, default: false},
        // pourquoi la signature n'est pas encore possible ('' si elle l'est)
        notReadyReason: {type: String, default: ''},
    },
    emits: ['sign', 'unsign'],
    data() {
        return {confirming: null};
    },
    computed: {
        s() {
            return matchState(this.matchData);
        },
        signed() {
            return this.kind === 'team' ? this.s.team : this.s.sheet;
        },
        otherSide() {
            return this.mySide === 'dom' ? 'ext' : 'dom';
        },
        attest() {
            return ATTEST[this.kind];
        },
        signLabel() {
            return this.kind === 'team' ? 'Signer la fiche équipes' : 'Signer la feuille de match';
        },
        canSign() {
            if (this.isCommission) return !(this.signed.dom && this.signed.ext);
            return !!this.mySide && !this.signed[this.mySide];
        },
        // annulation : sa propre signature, aux conditions du serveur
        canUnsign() {
            if (this.s.certified) return false;
            if (this.isCommission) return this.signed.dom || this.signed.ext;
            if (!this.mySide || !this.signed[this.mySide]) return false;
            return this.kind === 'team' ? !this.s.scoreFrozen : !this.signed[this.otherSide];
        },
        unsignBlockedReason() {
            if (this.isCommission || !this.mySide || !this.signed[this.mySide] || this.canUnsign) return '';
            return this.kind === 'team'
                ? "Annulation impossible : la feuille de match est signée. Il faut d'abord annuler cette signature."
                : 'Match clos : les deux équipes ont signé. Seule la commission peut le rouvrir.';
        },
        signWarning() {
            if (this.kind === 'team') {
                return this.s.frozen ? 'Votre signature confirme les présents, déjà figés.'
                    : 'Signer fige les présents des deux équipes et permet de saisir le score.';
            }
            return 'Signer fige le score : il ne pourra plus être modifié, sauf si vous annulez votre signature '
                + "avant que l'autre équipe ne signe.";
        },
        unsignConsequence() {
            if (this.isCommission) {
                return this.kind === 'team' ? 'Les présents redeviennent modifiables par les deux équipes.'
                    : 'Le score redevient modifiable.';
            }
            const other = this.matchData[`equipe_${this.otherSide}`];
            if (this.kind === 'team') {
                return this.signed[this.otherSide]
                    ? `Les présents resteront figés tant que ${other} n'aura pas annulé sa signature aussi.`
                    : 'Les présents redeviendront modifiables par les deux équipes.';
            }
            return 'Le score redeviendra modifiable ; la feuille de match sera à signer de nouveau.';
        },
    },
    methods: {
        confirm() {
            const action = this.confirming;
            this.confirming = null;
            this.$emit(action);
        },
    },
    template: `
      <div :data-testid="'signatures-' + kind">
        <ul class="mb-3 space-y-1">
          <li v-for="side in ['dom', 'ext']" :key="side" class="flex items-center gap-2 text-sm">
            <i v-if="signed[side]" class="fa-solid fa-check text-success" aria-hidden="true"></i>
            <i v-else class="fa-regular fa-clock opacity-60" aria-hidden="true"></i>
            <span>
              <strong>{{ matchData['equipe_' + side] }}</strong>{{ side === mySide ? ' (votre équipe)' : '' }} :
              {{ signed[side] ? 'a signé' : 'pas encore signé' }}
            </span>
          </li>
        </ul>

        <div v-if="confirming === 'sign'" role="alertdialog"
             class="rounded-box border-2 border-primary bg-primary/10 p-3 mb-2 flex flex-col gap-3"
             data-testid="confirm-sign">
          <span class="text-sm">{{ attest }}</span>
          <div class="flex flex-col sm:flex-row gap-2">
            <button type="button" class="btn btn-primary" @click="confirm()">
              <i class="fas fa-signature" aria-hidden="true"></i>Je signe
            </button>
            <button type="button" class="btn btn-ghost" @click="confirming = null">Annuler</button>
          </div>
        </div>
        <div v-else-if="confirming === 'unsign'" role="alertdialog"
             class="rounded-box border-2 border-error bg-error/10 p-3 mb-2 flex flex-col gap-3"
             data-testid="confirm-unsign">
          <span class="text-sm">{{ isCommission ? 'Annuler les signatures ?' : 'Annuler votre signature ?' }}
            {{ unsignConsequence }} L'autre équipe sera prévenue par email.</span>
          <div class="flex flex-col sm:flex-row gap-2">
            <button type="button" class="btn btn-error" @click="confirm()">Confirmer l'annulation</button>
            <button type="button" class="btn btn-ghost" @click="confirming = null">Garder ma signature</button>
          </div>
        </div>

        <template v-else>
          <template v-if="canSign">
            <p v-if="notReadyReason" class="text-sm opacity-80">{{ notReadyReason }}</p>
            <template v-else>
              <p class="text-sm mb-2"><i class="fa-solid fa-triangle-exclamation text-warning mr-1" aria-hidden="true"></i>{{ signWarning }}</p>
              <button type="button" class="btn btn-primary w-full sm:w-auto" @click="confirming = 'sign'">
                <i class="fas fa-signature" aria-hidden="true"></i>
                {{ isCommission ? signLabel + ' (2 équipes)' : signLabel }}
              </button>
            </template>
          </template>
          <div v-if="canUnsign" class="mt-3">
            <button type="button" class="btn btn-sm btn-outline btn-error" @click="confirming = 'unsign'">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
              {{ isCommission ? 'Annuler les signatures (commission)' : 'Annuler ma signature' }}
            </button>
          </div>
          <p v-if="unsignBlockedReason" class="text-xs opacity-70 mt-2">
            <i class="fa-solid fa-lock mr-1" aria-hidden="true"></i>{{ unsignBlockedReason }}
          </p>
        </template>
      </div>
    `,
};
