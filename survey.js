new Vue({
    el: '#app',
    data: {
        matchData: {},
        surveyData: {},
        isLoading: false,
    },
    mounted() {
        this.reloadData()
    },
    methods: {
        loadMatchData() {
            return axios.get(`/rest/action.php/matchmgr/get_match?id_match=${id_match}`)
                .then(response => {
                    this.matchData = response.data;
                })
                .catch(error => {
                    onError(error)
                });
        },
        loadSurveyData() {
            return axios.get(`/rest/action.php/matchmgr/get_survey?id_match=${id_match}`)
                .then(response => {
                    this.surveyData = response.data;
                })
                .catch(error => {
                    onError(error)
                });
        },
        reloadData() {
            this.isLoading = true;
            Promise.all([this.loadMatchData(), this.loadSurveyData()])
                .finally(() => {
                    this.isLoading = false;
                });
        },
        submitForm() {
            const formData = new FormData()
            formData.append('id_match', this.surveyData.id_match)
            formData.append('on_time', this.surveyData.on_time)
            formData.append('spirit', this.surveyData.spirit)
            formData.append('referee', this.surveyData.referee)
            formData.append('catering', this.surveyData.catering)
            formData.append('global', this.surveyData.global)
            if(this.surveyData.comment) {
                formData.append('comment', this.surveyData.comment)
            }
            if(this.surveyData.id) {
                formData.append('id', this.surveyData.id)
            }
            this.isLoading = true;
            axios.post('/rest/action.php/matchmgr/save_survey', formData)
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