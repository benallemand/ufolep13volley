import {onSuccess, onError} from "./toaster.js";
import {genericSignMatch, genericSignSheet} from "./signer.js";
import {canAskReport, canAcceptReport, canRefuseReport, canGiveReportDate, postReportAction} from "./utils/reportUtils.js";

new Vue({
    el: '#app',
    data: {
        id_match: (new URLSearchParams(window.location.search)).get('id_match'),
        matchData: {},
        isLoading: false,
        user: null,
    },
    mounted() {
        this.fetchUserDetails();
        this.reloadData();
    },
    computed: {
        isLeader() {
            const allowedProfiles = ["RESPONSABLE_EQUIPE"];
            return this.user && allowedProfiles.includes(this.user.profile_name);
        },
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
            return axios.get(`/rest/action.php/matchmgr/get_match?id_match=${this.id_match}`)
                .then(response => {
                    this.matchData = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        signMatch() {
            genericSignMatch(this, this.id_match);
        },
        signTeamSheets() {
            genericSignSheet(this, this.id_match);
        },
        submitForm() {
            this.setScores();
            const formData = new FormData();
            for (const key in this.matchData) {
                switch (key) {
                    case 'id_match':
                    case 'code_match':
                    case 'set_1_dom':
                    case 'set_2_dom':
                    case 'set_3_dom':
                    case 'set_4_dom':
                    case 'set_5_dom':
                    case 'set_1_ext':
                    case 'set_2_ext':
                    case 'set_3_ext':
                    case 'set_4_ext':
                    case 'set_5_ext':
                    case 'referee':
                    case 'dirtyFields':
                        formData.append(key, this.matchData[key]);
                        break;
                    case 'note':
                        if (this.matchData[key] === null) {
                            this.matchData[key] = '';
                        }
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
                        onSuccess(this, response)
                        this.reloadData();
                    }
                )
                .catch(error => {
                    onError(this, error)
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
        },
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        this.user = null;
                    } else {
                        this.user = response.data;
                    }
                })
                .catch(() => {
                });
        },
        canAskReport() {
            return canAskReport(this.matchData, this.user, this.isLeader);
        },
        canAcceptReport() {
            return canAcceptReport(this.matchData, this.user, this.isLeader);
        },
        canRefuseReport() {
            return canRefuseReport(this.matchData, this.user, this.isLeader);
        },
        canGiveReportDate() {
            return canGiveReportDate(this.matchData, this.user, this.isLeader);
        },
        postReportAction(actionName) {
            postReportAction(axios, this.matchData.code_match, actionName, () => this.reloadData());
        },
    }
});
