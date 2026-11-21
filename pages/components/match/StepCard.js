/**
 * Une étape de la page de match (#490) : en-tête cliquable (numéro, titre,
 * état), corps replié sauf pour l'étape ouverte. Une étape verrouillée
 * montre pourquoi, sans s'ouvrir.
 */
export default {
    props: {
        stepKey: {type: String, required: true},
        num: {type: Number, required: true},
        title: {type: String, required: true},
        // 'done' | 'current' | 'todo' | 'locked'
        state: {type: String, default: 'todo'},
        status: {type: String, default: ''},
        open: {type: Boolean, default: false},
    },
    emits: ['toggle'],
    computed: {
        icon() {
            return {
                done: 'fa-circle-check text-success',
                current: 'fa-circle-dot text-primary',
                locked: 'fa-lock opacity-50',
            }[this.state] || 'fa-circle opacity-40';
        },
    },
    template: `
      <section :id="stepKey" :data-testid="'step-' + stepKey" :data-state="state"
               class="rounded-box bg-base-100 mb-3 scroll-mt-4"
               :class="state === 'current' ? 'border-2 border-primary' : 'border border-base-300'">
        <button type="button" class="w-full flex items-center gap-3 px-3 py-3 min-h-14 text-left"
                :disabled="state === 'locked'" :aria-expanded="open && state !== 'locked'"
                @click="$emit('toggle')">
          <i class="fa-solid text-xl shrink-0" :class="icon" aria-hidden="true"></i>
          <span class="font-semibold" :class="{'opacity-50': state === 'locked'}">{{ num }}. {{ title }}</span>
          <span class="ml-auto text-xs sm:text-sm text-right opacity-70">{{ status }}</span>
          <i v-if="state !== 'locked'" class="fa-solid fa-chevron-down text-xs shrink-0 transition-transform"
             :class="{'rotate-180': open}" aria-hidden="true"></i>
        </button>
        <!-- v-show : une étape repliée garde son contenu (listes déjà
             chargées, saisie en cours) ; une étape verrouillée n'est pas rendue -->
        <div v-if="state !== 'locked'" v-show="open" class="px-3 pb-4">
          <slot></slot>
        </div>
      </section>
    `,
};
