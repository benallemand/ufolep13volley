import MatchCard from '../../pages/components/match-card.js';

export default {
    components: {
        'match-card': MatchCard
    },
    template: `
      <match-card :match="match">
        <template v-slot:actions>
          <div class="card-actions">
            <button
                v-if="canValidate"
                @click="validateMatch(match.id_match)"
                :disabled="loadingMatch === match.id_match || match.certif === 1"
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
            type: Boolean,
            default: false
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
