import MatchCard from '../../pages/components/card/Match.js';

export default {
    components: {
        'match-card': MatchCard
    },
    template: `
      <match-card :match="match">
        <template v-slot:actions>
          <div class="card-actions">
            <button
                v-if="canValidate(match)"
                @click="validateMatch(match.id_match)"
                :disabled="loadingMatch === match.id_match"
                class="btn btn-primary">
              {{ loadingMatch === match.id_match ? 'Validation en cours...' : 'Valider le match' }}
            </button>
          </div>
        </template>
      </match-card>
    `,
    props: {
        match: {
            type: Object,
            required: true,
        },
        canValidate: {
            type: Function,
            default: () => false
        },
        loadingMatch: {
            type: [Number, String, null],
            default: null
        }
    },
    methods: {
        validateMatch(idMatch) {
            this.$emit('validate-match', idMatch);
        }
    }
};
