import { onSuccess, onError} from "./toaster.js";
import {genericSignMatch, genericSignSheet} from "./signer.js";

new Vue({
    el: '#app',
    data: {
        matchData: {},
        availablePlayers: [],
        matchPlayers: [],
        selectedPlayers: [],
        isLoading: false,
        query: '',
        renforts: [],
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
        addPlayers() {
            this.selectedPlayers.forEach(player => {
                if (!this.matchPlayers.includes(player)) {
                    this.matchPlayers.push(player);
                }
            });
            this.renforts.forEach(player => {
                if (!this.matchPlayers.includes(player)) {
                    this.matchPlayers.push(player);
                }
            });
            this.selectedPlayers = [];
        },
        removePlayer(id) {
            const formData = new FormData()
            formData.append('id_match', id_match)
            formData.append('id_player', id)
            this.isLoading = true;
            axios.post('/rest/action.php/matchmgr/delete_match_player', formData)
                .then(
                    response => {
                        onSuccess(this, response)
                        this.reloadData();
                    }
                )
                .catch(error => {
                    onError(this, error)
                });
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
        }
    }
});