import {onSuccess, onError} from "./toaster.js";
import {genericSignMatch, genericSignSheet} from "./signer.js";


Vue.component('player-list', {
    props: ['players', 'teamName', 'isSigned'],
    methods: {
        handleClick(player) {
            this.$emit(this.$listeners['add-player'] ? 'add-player' : 'remove-player', player);
        }
    },
    template: `
      <div class="border border-2 border-black p-4">
        <h1>{{ teamName }}</h1>
        <div v-for="player in players" :key="player.id" class="flex items-center mb-2">
          <img :src="player.path_photo_low" alt="photo" class="w-12 h-12 rounded-full mr-3"/>
          <span :class="{'bg-pink-500 text-white': player.est_actif === 0}" class="px-2 py-1 rounded">
                        {{ player.prenom }} {{ player.nom }}
                    </span>
          <span v-if="player.est_actif === 0" class="text-sm text-red-500 ml-2">(Licence non envoyée)</span>
          <button v-if="!isSigned"
                  class="btn ml-auto"
                  :class="{'btn-success': $listeners['add-player'], 'btn-error': $listeners['remove-player']}"
                  @click="handleClick(player)">
            <i class="fa-solid"
               :class="{'fa-plus': $listeners['add-player'], 'fa-trash': $listeners['remove-player']}"></i>
          </button>
        </div>
      </div>
    `
});

new Vue({
    el: '#app',
    data: {
        matchData: {},
        availablePlayers: [],
        matchPlayers: [],
        isLoading: false,
        query: '',
        renforts: [],
    },
    computed: {
        availablePlayersDom() {
            return this.availablePlayers.filter(player => player.equipe === this.matchData.equipe_dom && !this.matchPlayers.includes(player));
        },
        availablePlayersExt() {
            return this.availablePlayers.filter(player => player.equipe === this.matchData.equipe_ext && !this.matchPlayers.includes(player));
        },
    },
    mounted() {
        this.reloadData()
    },
    methods: {
        search() {
            if (this.query.length > 3) {
                return axios.get(`/rest/action.php/matchmgr/getReinforcementPlayers?id_match=${id_match}&query=${this.query}`)
                    .then(response => {
                        this.renforts = response.data;
                    })
                    .catch(error => {
                        this.renforts = [];
                        onError(this, error)
                    });
            }
            this.renforts = [];
        },
        loadMatchData() {
            return axios.get(`/rest/action.php/matchmgr/get_match?id_match=${id_match}`)
                .then(response => {
                    this.matchData = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        loadAvailablePlayers() {
            return axios.get(`/rest/action.php/matchmgr/getNotMatchPlayers?id_match=${id_match}`)
                .then(response => {
                    this.availablePlayers = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        loadMatchPlayers() {
            return axios.get(`/rest/action.php/matchmgr/getMatchPlayers?id_match=${id_match}`)
                .then(response => {
                    this.matchPlayers = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        signMatch() {
            genericSignMatch(this, id_match);
        },
        signTeamSheets() {
            genericSignSheet(this, id_match);
        },
        addPlayer(player) {
            if (!this.matchPlayers.includes(player)) {
                this.matchPlayers.push(player);
            }
        },
        removePlayer(player) {
            this.matchPlayers = this.matchPlayers.filter(matchPlayer => matchPlayer !== player);
        },
        reloadData() {
            this.isLoading = true;
            Promise.all([this.loadMatchData(), this.loadAvailablePlayers(), this.loadMatchPlayers()])
                .finally(() => {
                    this.isLoading = false;
                });
        },
        submitForm() {
            const formData = new FormData()
            formData.append('id_match', id_match)
            this.matchPlayers.forEach((player) => {
                formData.append('player_ids[]', player.id)
            })
            this.isLoading = true;
            axios.post('/rest/action.php/matchmgr/manage_match_players', formData)
                .then(
                    response => {
                        onSuccess(this, response)
                        this.reloadData()
                    }
                )
                .catch(error => {
                    onError(this, error)
                });
        },
        parseDate(dateString) {
            if (!dateString) {
                return null;
            }
            const [day, month, year] = dateString.split('/');
            return new Date(`${year}-${month}-${day}`);
        },
        compareDates(date1, date2) {
            const d1 = this.parseDate(date1);
            const d2 = this.parseDate(date2);
            if (!d1 || !d2) {
                return false;
            }
            return d1 < d2;
        },
    }
});