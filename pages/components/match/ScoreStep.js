/**
 * Étape « Score » de la page de match (#490) : sets, arbitrage, commentaire.
 *
 * Saisissable dès que les présents sont figés (une signature de fiche équipes
 * suffit), figé dès qu'une équipe a signé la feuille de match. L'admin corrige
 * sans condition. La page enregistre, avec la version du match.
 */
export default {
    props: {
        matchData: {type: Object, required: true},
        editable: {type: Boolean, default: false},
        // pourquoi le score n'est pas modifiable ('' sinon)
        lockedReason: {type: String, default: ''},
    },
    emits: ['save'],
    data() {
        return {form: this.fromMatch()};
    },
    watch: {
        matchData() {
            this.form = this.fromMatch();
        },
    },
    computed: {
        sets() {
            return [1, 2, 3, 4, 5];
        },
        won() {
            const w = {dom: 0, ext: 0};
            this.sets.forEach(s => {
                const d = Number(this.form[`set_${s}_dom`]) || 0;
                const e = Number(this.form[`set_${s}_ext`]) || 0;
                if (d > e) w.dom++;
                else if (e > d) w.ext++;
            });
            return w;
        },
        complete() {
            return this.won.dom === 3 || this.won.ext === 3;
        },
    },
    methods: {
        fromMatch() {
            const m = this.matchData || {};
            const form = {referee: m.referee || 'HOME', note: m.note || ''};
            [1, 2, 3, 4, 5].forEach(s => {
                form[`set_${s}_dom`] = m[`set_${s}_dom`] ?? '';
                form[`set_${s}_ext`] = m[`set_${s}_ext`] ?? '';
            });
            return form;
        },
        save() {
            const payload = {referee: this.form.referee, note: this.form.note ?? ''};
            this.sets.forEach(s => {
                payload[`set_${s}_dom`] = this.form[`set_${s}_dom`] === '' ? 0 : Number(this.form[`set_${s}_dom`]);
                payload[`set_${s}_ext`] = this.form[`set_${s}_ext`] === '' ? 0 : Number(this.form[`set_${s}_ext`]);
            });
            this.$emit('save', payload);
        },
    },
    template: `
      <div data-testid="score-step">
        <div v-if="lockedReason" class="alert alert-success mb-3 text-sm" data-testid="score-locked">
          <i class="fa-solid fa-lock" aria-hidden="true"></i><span>{{ lockedReason }}</span>
        </div>
        <div class="grid grid-cols-[3.5rem_1fr_1fr] gap-2 items-center">
          <span></span>
          <span class="text-center text-sm font-semibold truncate">{{ matchData.equipe_dom }}</span>
          <span class="text-center text-sm font-semibold truncate">{{ matchData.equipe_ext }}</span>
          <template v-for="s in sets" :key="s">
            <label class="text-sm" :for="'set-' + s + '-dom'">Set {{ s }}</label>
            <input :id="'set-' + s + '-dom'" class="input input-lg text-center w-full" type="number"
                   inputmode="numeric" min="0" max="50" :disabled="!editable"
                   :aria-label="'Set ' + s + ' ' + matchData.equipe_dom" v-model="form['set_' + s + '_dom']"/>
            <input class="input input-lg text-center w-full" type="number" inputmode="numeric" min="0" max="50"
                   :disabled="!editable" :aria-label="'Set ' + s + ' ' + matchData.equipe_ext"
                   v-model="form['set_' + s + '_ext']"/>
          </template>
          <span class="text-sm font-semibold">Sets</span>
          <span class="text-center text-xl font-bold" data-testid="sets-dom">{{ won.dom }}</span>
          <span class="text-center text-xl font-bold" data-testid="sets-ext">{{ won.ext }}</span>
        </div>

        <label class="block mt-4 text-sm font-semibold" for="referee">Arbitrage</label>
        <select id="referee" v-model="form.referee" class="select w-full mt-1" :disabled="!editable">
          <option value="HOME">équipe à domicile</option>
          <option value="AWAY">équipe à l'extérieur</option>
          <option value="BOTH">les deux / auto-arbitrage</option>
        </select>

        <label class="block mt-4 text-sm font-semibold" for="note">Commentaire sur le match (facultatif)</label>
        <textarea id="note" v-model="form.note" class="textarea w-full mt-1" rows="2" :disabled="!editable"
                  placeholder="Incident, retard, remarque pour la commission…"></textarea>

        <template v-if="editable">
          <p v-if="!complete" class="text-xs opacity-70 mt-3">Une équipe doit avoir gagné 3 sets pour signer la feuille de match.</p>
          <button type="button" class="btn btn-primary w-full mt-3" data-testid="save-score" @click="save()">
            <i class="fas fa-floppy-disk" aria-hidden="true"></i>Enregistrer le score
          </button>
        </template>
        <p class="text-xs opacity-70 mt-3">
          Le scan des feuilles papier est facultatif : si besoin, l'envoyer par email à la commission.
        </p>
      </div>
    `,
};
