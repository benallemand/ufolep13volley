/**
 * Étape « Sondage fair-play » de la page de match (#490), reprise de
 * l'ancienne page du sondage. Échelle -- - = + ++ (issue #350), préremplie à
 * =, un commentaire exigé pour une note à --. Un sondage par équipe et par
 * match (#482).
 */

/** Échelle -2..+2, stockée telle quelle (`survey.scale_version = 2`). */
export const SURVEY_SCALE = [
    {value: -2, label: '--', meaning: 'Très insatisfaisant : problème sérieux, à expliquer en commentaire'},
    {value: -1, label: '-', meaning: "Insatisfaisant, en dessous de ce qu'on attend"},
    {value: 0, label: '=', meaning: 'Conforme, rien à signaler'},
    {value: 1, label: '+', meaning: "Bien, au-dessus de ce qu'on attend"},
    {value: 2, label: '++', meaning: 'Excellent, remarquable'},
];
export const SURVEY_CRITERIA = [
    {key: 'on_time', label: 'Ponctualité'},
    {key: 'spirit', label: "Etat d'esprit"},
    {key: 'referee', label: 'Arbitrage'},
    {key: 'catering', label: 'Apéro'},
    {key: 'global', label: 'Global'},
];

export default {
    props: {
        surveyData: {type: Object, default: () => ({})},
        opponent: {type: String, default: ''},
    },
    emits: ['save', 'invalid'],
    data() {
        return {form: {...this.surveyData}, scale: SURVEY_SCALE, criteria: SURVEY_CRITERIA};
    },
    watch: {
        surveyData() {
            this.form = {...this.surveyData};
        },
    },
    computed: {
        commentRequired() {
            return this.criteria.some(c => Number(this.form[c.key]) === -2);
        },
        hasComment() {
            return !!(this.form.comment && this.form.comment.trim());
        },
    },
    methods: {
        submit() {
            if (this.commentRequired && !this.hasComment) {
                this.$emit('invalid', 'Une note à -- doit être expliquée en commentaire.');
                return;
            }
            const payload = {};
            this.criteria.forEach(c => {
                payload[c.key] = this.form[c.key];
            });
            if (this.form.comment) payload.comment = this.form.comment;
            if (this.form.id) payload.id = this.form.id;
            this.$emit('save', payload);
        },
    },
    template: `
      <form @submit.prevent="submit" data-testid="survey-step">
        <p v-if="form.id" class="alert alert-success text-sm mb-3">
          <i class="fa-solid fa-check" aria-hidden="true"></i>Sondage envoyé : vous pouvez encore le modifier.
        </p>
        <p v-if="opponent" class="text-sm mb-2">Votre avis sur <strong>{{ opponent }}</strong> :</p>
        <details class="mb-3 rounded-box border border-base-300 bg-base-200 p-3" data-testid="survey-legend" open>
          <summary class="font-semibold cursor-pointer">Comment noter ?</summary>
          <ul class="text-sm space-y-1 mt-2">
            <li v-for="note in scale" :key="note.value">
              <span class="badge badge-outline font-mono w-10 mr-2">{{ note.label }}</span>{{ note.meaning }}
            </li>
          </ul>
          <p class="text-sm mt-2">Chaque critère est à <strong>=</strong> par défaut : ne changez que ce qui mérite de l'être.</p>
        </details>
        <div class="flex flex-col gap-3 mb-3">
          <div v-for="criterion in criteria" :key="criterion.key" class="flex flex-col sm:flex-row sm:items-center gap-1">
            <span class="sm:basis-1/3 font-medium text-sm">{{ criterion.label }}</span>
            <div class="join w-full sm:w-auto" role="radiogroup" :aria-label="criterion.label">
              <input v-for="note in scale" :key="note.value" type="radio"
                     class="join-item btn btn-sm flex-1 sm:flex-none sm:w-12 font-mono"
                     :name="criterion.key" :value="note.value" :aria-label="note.label" :title="note.meaning"
                     v-model.number="form[criterion.key]"/>
            </div>
          </div>
        </div>
        <label class="block text-sm font-semibold">Commentaire
          <span v-if="commentRequired" class="text-error text-xs">(obligatoire : une note est à --)</span>
          <textarea v-model="form.comment" class="textarea w-full mt-1" rows="2"
                    :class="{'textarea-error': commentRequired && !hasComment}" :required="commentRequired"
                    placeholder="Ajouter un commentaire"></textarea>
        </label>
        <button class="btn btn-primary w-full mt-3" type="submit" data-testid="save-survey">
          <i class="fas fa-paper-plane" aria-hidden="true"></i>Envoyer le sondage
        </button>
      </form>
    `,
};
