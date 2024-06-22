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
                        onError(error)
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
                    onError(error)
                });
        },
        loadAvailablePlayers() {
            return axios.get(`/rest/action.php/matchmgr/getNotMatchPlayers?id_match=${id_match}`)
                .then(response => {
                    this.availablePlayers = response.data;
                })
                .catch(error => {
                    onError(error)
                });
        },
        loadMatchPlayers() {
            return axios.get(`/rest/action.php/matchmgr/getMatchPlayers?id_match=${id_match}`)
                .then(response => {
                    this.matchPlayers = response.data;
                })
                .catch(error => {
                    onError(error)
                });
        },
        signTeamSheets() {
            const message = "Je confirme avoir pris connaissance des joueurs/joueuses présent(e)s." +
                "\nLes personnes présentes pour ce match ont été déclarées présentes sur le site, sur la page de gestion du match." +
                "\nEn signant numériquement la fiche équipe, il n'est plus nécessaire de fournir de fiche équipe au format papier." +
                "\nMerci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.";
            if (window.confirm(message)) {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('id_match', id_match);
                axios.post('/rest/action.php/matchmgr/sign_team_sheet', formData)
                    .then(
                        response => {
                            onSuccess(response)
                            this.reloadData();
                        }
                    )
                    .catch(error => {
                        onError(error)
                    });
            }
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
                        onSuccess(response)
                        this.reloadData();
                    }
                )
                .catch(error => {
                    onError(error)
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
                        onSuccess(response)
                        this.reloadData()
                    }
                )
                .catch(error => {
                    onError(error)
                });
        }
    }
});