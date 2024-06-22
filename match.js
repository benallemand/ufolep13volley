new Vue({
    el: '#app',
    data: {
        matchData: {},
        isLoading: false,
    },
    mounted() {
        this.reloadData();
    },
    methods: {
        reloadData() {
            this.isLoading = true;
            Promise.all([this.loadMatchData()])
                .finally(() => {
                    this.isLoading = false;
                });
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
        signMatch() {
            const message = "Je confirme avoir pris connaissance du score saisi sur le site." +
                "\nEn signant numériquement la feuille de match, il n'est plus nécessaire de fournir de feuille de match au format papier." +
                "\nMerci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.";
            if (window.confirm(message)) {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('id_match', id_match);
                axios.post('/rest/action.php/matchmgr/sign_match_sheet', formData)
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
        submitForm() {
            this.setScores();
            const formData = new FormData();
            for (const key in this.matchData) {
                switch (key) {
                    case 'id_match':
                    case 'code_match':
                    case 'score_equipe_dom':
                    case 'set_1_dom':
                    case 'set_2_dom':
                    case 'set_3_dom':
                    case 'set_4_dom':
                    case 'set_5_dom':
                    case 'score_equipe_ext':
                    case 'set_1_ext':
                    case 'set_2_ext':
                    case 'set_3_ext':
                    case 'set_4_ext':
                    case 'set_5_ext':
                    case 'referee':
                    case 'note':
                    case 'forfait_dom':
                    case 'forfait_ext':
                    case 'dirtyFields':
                        formData.append(key, this.matchData[key]);
                        break;
                    default:
                        break;
                }
            }
            this.isLoading = true;
            axios.post('/rest/action.php/matchmgr/save_match', formData)
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
        setScores() {
            const sets = [
                {dom: this.matchData.set_1_dom, ext: this.matchData.set_1_ext},
                {dom: this.matchData.set_2_dom, ext: this.matchData.set_2_ext},
                {dom: this.matchData.set_3_dom, ext: this.matchData.set_3_ext},
                {dom: this.matchData.set_4_dom, ext: this.matchData.set_4_ext},
                {dom: this.matchData.set_5_dom, ext: this.matchData.set_5_ext}
            ];
            const result = sets.reduce((acc, set) => {
                if (set.dom !== set.ext) {
                    if (set.dom > set.ext) {
                        acc.score_equipe_dom++;
                    } else {
                        acc.score_equipe_ext++;
                    }
                }
                return acc;
            }, {score_equipe_dom: 0, score_equipe_ext: 0});
            this.matchData.score_equipe_dom = result.score_equipe_dom
            this.matchData.score_equipe_ext = result.score_equipe_ext
        }
    }
});