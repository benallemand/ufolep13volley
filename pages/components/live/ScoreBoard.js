export default {
    template: `
      <div class="card bg-base-100 shadow-xl mb-4">
        <div class="card-body p-4">
          <!-- Teams and Score -->
          <div class="grid grid-cols-3 gap-2 items-center text-center">
            <!-- Home Team -->
            <div>
              <p class="font-bold text-lg truncate">{{ leftTeamName }}</p>
              <p class="text-xs text-gray-500">{{ leftTeamLabel }}</p>
            </div>
            
            <!-- Score -->
            <div>
              <div class="text-5xl font-bold">
                <span class="text-primary">{{ leftScore }}</span>
                <span class="text-gray-400">-</span>
                <span class="text-secondary">{{ rightScore }}</span>
              </div>
              <p class="text-sm mt-1">Set {{ score.set_en_cours }}</p>
              <p class="text-lg font-bold">({{ leftSets }} - {{ rightSets }})</p>
              
              <!-- Set Details -->
              <div class="text-xs text-gray-500 mt-2 flex gap-2 justify-center flex-wrap">
                <span v-if="score.set_1_dom || score.set_1_ext" class="badge badge-ghost">S1: {{ leftSetScore(1) }}-{{ rightSetScore(1) }}</span>
                <span v-if="score.set_2_dom || score.set_2_ext" class="badge badge-ghost">S2: {{ leftSetScore(2) }}-{{ rightSetScore(2) }}</span>
                <span v-if="score.set_3_dom || score.set_3_ext" class="badge badge-ghost">S3: {{ leftSetScore(3) }}-{{ rightSetScore(3) }}</span>
                <span v-if="score.set_4_dom || score.set_4_ext" class="badge badge-ghost">S4: {{ leftSetScore(4) }}-{{ rightSetScore(4) }}</span>
                <span v-if="score.set_5_dom || score.set_5_ext" class="badge badge-ghost">S5: {{ leftSetScore(5) }}-{{ rightSetScore(5) }}</span>
              </div>
            </div>
            
            <!-- Away Team -->
            <div>
              <p class="font-bold text-lg truncate">{{ rightTeamName }}</p>
              <p class="text-xs text-gray-500">{{ rightTeamLabel }}</p>
            </div>
          </div>
        </div>
      </div>
    `,
    props: {
        score: { type: Object, required: true },
        leftTeamName: { type: String, default: '' },
        rightTeamName: { type: String, default: '' },
        leftTeamLabel: { type: String, default: '' },
        rightTeamLabel: { type: String, default: '' },
        leftTeamKey: { type: String, required: true },
        rightTeamKey: { type: String, required: true }
    },
    computed: {
        leftScore() {
            return this.leftTeamKey === 'dom' ? this.score.score_dom : this.score.score_ext;
        },
        rightScore() {
            return this.rightTeamKey === 'dom' ? this.score.score_dom : this.score.score_ext;
        },
        leftSets() {
            return this.leftTeamKey === 'dom' ? this.score.sets_dom : this.score.sets_ext;
        },
        rightSets() {
            return this.rightTeamKey === 'dom' ? this.score.sets_dom : this.score.sets_ext;
        }
    },
    methods: {
        leftSetScore(setNum) {
            const prop = 'set_' + setNum + '_' + this.leftTeamKey;
            return this.score[prop] ?? 0;
        },
        rightSetScore(setNum) {
            const prop = 'set_' + setNum + '_' + this.rightTeamKey;
            return this.score[prop] ?? 0;
        }
    }
};
