/**
 * Terrain d'une équipe : 6 postes, 4-3-2 devant, 5-6-1 derrière (issue #332).
 *
 * Partagé par la feuille « Positions » de l'écran de marque et par l'écran de
 * composition. Le poste 1 est mis en avant : c'est le serveur, et c'est la
 * seule position qui compte vraiment pendant l'échange.
 *
 * Chaque case porte la **vignette** du joueur, puis son prénom et l'initiale de
 * son nom. C'est la photo qui fait reconnaître quelqu'un d'un coup d'œil ; un
 * nom de famille entier ne tient de toute façon pas dans une case de 55 px.
 */

// Ordre d'affichage, pas ordre de rotation : le filet est en haut.
const DISPLAY_ORDER = [4, 3, 2, 5, 6, 1];

export default {
    template: `
      <div>
        <div v-if="teamName" class="flex items-center gap-2 mb-1">
          <span class="w-2 h-2 rounded-full" :class="accent"></span>
          <span class="text-xs font-semibold truncate">{{ teamName }}</span>
        </div>
        <div class="grid grid-cols-3 gap-1">
          <button v-for="cell in cells"
                  :key="'pos-' + cell.position"
                  type="button"
                  :disabled="!selectable"
                  class="border rounded-lg px-0.5 pt-1 pb-1.5 flex flex-col items-center gap-1 min-h-[4rem] disabled:cursor-default"
                  :class="cell.classes"
                  @click="$emit('select-position', cell.position)">
            <span class="text-[0.6rem] font-bold opacity-50 leading-none">P{{ cell.position }}</span>
            <div class="avatar">
              <div class="rounded-full" :class="photoSize">
                <img v-if="cell.player" :src="'/' + cell.player.path_photo_low" :alt="cell.player.nom_court">
                <img v-else src="/images/MaleMissingPhoto.png" alt="" class="opacity-25">
              </div>
            </div>
            <span class="text-[0.65rem] font-semibold leading-tight text-center"
                  :class="cell.player ? '' : 'opacity-40'">
              {{ cell.label }}
            </span>
          </button>
        </div>
      </div>
    `,
    props: {
        teamName: { type: String, default: '' },
        /** { 1: idJoueur, 2: …, 6: … } — une valeur vide = poste libre */
        lineup: { type: Object, default: () => ({}) },
        /** Effectif servi par `ajax/live_score.php?what=rosters` */
        players: { type: Array, default: () => [] },
        /** Poste en cours de saisie (écran de composition) */
        activePosition: { type: Number, default: null },
        /** true : les cases sont des boutons (composition) */
        selectable: { type: Boolean, default: false },
        /** Vignettes plus grandes quand la place le permet */
        large: { type: Boolean, default: false },
        accent: { type: String, default: 'bg-primary' },
    },
    emits: ['select-position'],
    computed: {
        playersById() {
            const byId = {};
            this.players.forEach((player) => { byId[String(player.id)] = player; });
            return byId;
        },
        photoSize() {
            return this.large ? 'w-9' : 'w-7';
        },
        cells() {
            return DISPLAY_ORDER.map((position) => {
                const player = this.playersById[String(this.lineup[position] || '')] || null;
                const active = this.activePosition === position;
                return {
                    position,
                    player,
                    label: player ? player.nom_court : (active ? 'à choisir' : '—'),
                    classes: active
                        ? 'border-warning bg-warning/20'
                        : (position === 1 ? 'border-warning/60 bg-warning/10' : 'border-base-300 bg-base-200'),
                };
            });
        },
    },
};
