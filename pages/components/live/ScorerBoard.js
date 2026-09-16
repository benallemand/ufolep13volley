/**
 * Écran de marque de l'arbitre : le terrain EST le bouton (issue #332).
 *
 * L'arbitre est debout, sur un téléphone, et son seul geste entre deux points
 * est « +1 pour cette équipe-là ». Ce geste devient donc le plus grand objet de
 * l'écran : deux moitiés pleine hauteur, qu'on peut viser sans regarder.
 *
 * Tout ce qui n'intervient pas entre deux points — positions, temps morts,
 * sets, fin de live — est descendu dans `ScorerControls`.
 */
export default {
    template: `
      <div class="flex flex-col flex-1 min-h-0">
        <div class="flex items-center justify-between px-4 h-14 py-2 bg-primary text-primary-content">
          <div class="flex items-baseline gap-2">
            <span class="font-bold">Set {{ score.set_en_cours }}</span>
            <span class="text-sm opacity-75 tabular-nums">({{ leftSets }}–{{ rightSets }})</span>
          </div>
          <span v-if="isLive" class="badge badge-sm gap-1">
            <i class="fas fa-circle text-error text-[0.5rem] animate-pulse"></i> LIVE
          </span>
        </div>

        <div class="flex-1 grid grid-cols-2 gap-[3px] bg-base-300 relative min-h-0">
          <button type="button"
                  :aria-label="'Point pour ' + leftTeamName"
                  class="bg-base-100 flex flex-col items-center justify-center gap-2 border-t-4 px-2"
                  :class="leftServing ? 'border-warning' : 'border-base-300'"
                  @click="$emit('point-left')">
            <span class="font-bold text-center leading-tight line-clamp-2">{{ leftTeamName }}</span>
            <span class="text-7xl font-extrabold tabular-nums leading-none text-primary">{{ leftScore }}</span>
            <span class="text-xs font-bold tracking-wide text-warning h-4">{{ leftServing ? 'SERVICE' : '' }}</span>
          </button>

          <button type="button"
                  :aria-label="'Point pour ' + rightTeamName"
                  class="bg-base-100 flex flex-col items-center justify-center gap-2 border-t-4 px-2"
                  :class="rightServing ? 'border-warning' : 'border-base-300'"
                  @click="$emit('point-right')">
            <span class="font-bold text-center leading-tight line-clamp-2">{{ rightTeamName }}</span>
            <span class="text-7xl font-extrabold tabular-nums leading-none text-secondary">{{ rightScore }}</span>
            <span class="text-xs font-bold tracking-wide text-warning h-4">{{ rightServing ? 'SERVICE' : '' }}</span>
          </button>

          <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none
                       badge badge-ghost text-[0.65rem] tracking-wider opacity-60">
            TOUCHER POUR +1
          </span>
        </div>
      </div>
    `,
    props: {
        score: { type: Object, required: true },
        leftTeamName: { type: String, default: '' },
        rightTeamName: { type: String, default: '' },
        leftTeamKey: { type: String, required: true },
        rightTeamKey: { type: String, required: true },
        servingTeam: { type: String, default: null },
        isLive: { type: Boolean, default: false },
    },
    emits: ['point-left', 'point-right'],
    computed: {
        leftScore() { return this.score['score_' + this.leftTeamKey]; },
        rightScore() { return this.score['score_' + this.rightTeamKey]; },
        leftSets() { return this.score['sets_' + this.leftTeamKey]; },
        rightSets() { return this.score['sets_' + this.rightTeamKey]; },
        leftServing() { return this.servingTeam === this.leftTeamKey; },
        rightServing() { return this.servingTeam === this.rightTeamKey; },
    },
};
