export default {
    components: {
        'limit-date-navbar': () => import('../navbar/LimitDate.js'),
        'commission-member': () => import('../navbar/CommissionMember.js'),
        'matchs-list': () => import('../list/Matchs.js'),
        'tournament-bracket-viewer': () => import('./TournamentBracketViewer.js'),
    },
    template: `
      <div>
        <div class="flex flex-wrap gap-4 mb-4">
          <limit-date-navbar :key="'navbar-' + code_competition" :code_competition="code_competition" class="flex-1"></limit-date-navbar>
          <commission-member :key="'commission-' + code_competition + '-' + division" :code_competition="code_competition" :division="division" class="flex-none"></commission-member>
        </div>
        
        <!-- Onglets pour choisir la vue -->
        <div class="tabs tabs-boxed mb-4">
          <a class="tab" :class="{ 'tab-active': viewMode === 'bracket' }" @click="viewMode = 'bracket'">
            Arbre des phases finales
          </a>
          <a class="tab" :class="{ 'tab-active': viewMode === 'list' }" @click="viewMode = 'list'">
            Liste des matchs
          </a>
        </div>

        <!-- Vue arbre de tournoi -->
        <div v-if="viewMode === 'bracket'" class="mb-6">
          <tournament-bracket-viewer 
            :matches="finalsMatches" 
            :tournament-type="'single_elimination'"
            :key="'bracket-viewer-' + code_competition + '-' + division"
          />
        </div>

        <!-- Vue liste traditionnelle -->
        <div v-if="viewMode === 'list'">
          <matchs-list :key="'matchs-' + code_competition + '-' + division" :fetch-url="matchesFetchUrl"></matchs-list>
        </div>
        
      </div>
    `,
    data() {
        return {
            division: 1,
            code_competition: this.$route.params.code_competition,
            viewMode: 'bracket', // 'bracket' ou 'list'
            finalsMatches: [],
        };
    },
    watch: {
        '$route.params': {
            handler(newParams) {
                this.code_competition = newParams.code_competition;
            },
            immediate: true
        }
    },
    computed: {
        matchesFetchUrl() {
            return `/rest/action.php/matchmgr/getMatches?competition=${this.code_competition}&division=${this.division}`;
        }
    },
    methods: {
        fetchFinalsMatches() {
            axios.get(this.matchesFetchUrl)
                .then(response => {
                    // Filtrer les matchs de phases finales
                    this.finalsMatches = response.data.filter(match => {
                        const journee = match.journee?.toLowerCase() || '';
                        return journee.includes('finale') || 
                               journee.includes('1/8') || 
                               journee.includes('1/4') || 
                               journee.includes('1/2') ||
                               journee.includes('quart') ||
                               journee.includes('demi');
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des matchs de phases finales:', error);
                });
        }
    },
    created() {
        this.fetchFinalsMatches();
    }
};
