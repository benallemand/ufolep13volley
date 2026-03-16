// Import du composant admin-match-card
import AdminMatchCard from './components/admin-match-card.js';

new Vue({
    el: '#match-app',
    components: {
        'admin-match-card': AdminMatchCard
    },
    data: {
        searchQuery: "",
        matches: [],
        loadingMatch: null,
        loading: false,
        filter: {
            selectedCompetition: "",
            selectedDivision: "",
            showCertified: false,
            showNotCertified: false,
            showForbiddenPlayer: false,
            showPlayedMatchesOnly: false,
            showCertifiable: false,
            showInProgress: true,
        },
        user: null,
        availableCompetitions: [],
        availableDivisions: [],
        allMatches: [],
    },
    computed: {
        canValidate() {
            const allowedProfiles = ["ADMINISTRATEUR", "SUPPORT"];
            return (match) => {
                const hasPermission = this.user && allowedProfiles.includes(this.user.profile_name);
                const isSignedByBothTeams = match.is_sign_match_dom === 1 && match.is_sign_match_ext === 1;
                const isAlreadyCertified = match.certif === 1;
                return hasPermission && isSignedByBothTeams && !isAlreadyCertified;
            };
        },
        filteredMatches() {
            return this.matches;
        },
    },
    watch: {
        'filter': {
            handler: function() {
                this.debouncedFetchMatches();
            },
            deep: true
        },
        'searchQuery': function() {
            this.debouncedFetchMatches();
        }
    },
    methods: {
        updateAvailableCompetitions() {
            const competitions = new Set(this.allMatches.map((match) => match.libelle_competition));
            this.availableCompetitions = Array.from(competitions).sort();
        },
        fetchAllMatches() {
            axios
                .get("/rest/action.php/matchmgr/getMatches")
                .then((response) => {
                    this.allMatches = response.data;
                    this.updateAvailableCompetitions();
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement de tous les matchs :", error);
                });
        },
        updateAvailableDivisions() {
            if (this.filter.selectedCompetition) {
                const divisions = new Set(
                    this.allMatches
                        .filter((match) => match.libelle_competition === this.filter.selectedCompetition)
                        .map((match) => match.division)
                );
                this.availableDivisions = Array.from(divisions).sort();
            } else {
                this.availableDivisions = [];
            }
            this.filter.selectedDivision = "";
        },
        fetchMatches() {
            const params = new URLSearchParams();
            if (this.filter.selectedCompetition) {
                params.append('competition', this.filter.selectedCompetition);
            }
            if (this.filter.selectedDivision) {
                params.append('division', this.filter.selectedDivision);
            }
            if (this.filter.showCertified) {
                params.append('showCertified', 'true');
            }
            if (this.filter.showNotCertified) {
                params.append('showNotCertified', 'true');
            }
            if (this.filter.showForbiddenPlayer) {
                params.append('showForbiddenPlayer', 'true');
            }
            if (this.filter.showPlayedMatchesOnly) {
                params.append('showPlayedMatchesOnly', 'true');
            }
            if (this.filter.showCertifiable) {
                params.append('showCertifiable', 'true');
            }
            if (this.filter.showInProgress) {
                params.append('showInProgress', 'true');
            }
            if (this.searchQuery) {
                params.append('search', this.searchQuery);
            }
            this.loading = true;
            axios
                .get(`/rest/action.php/matchmgr/getMatches?${params.toString()}`)
                .then((response) => {
                    this.matches = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        debouncedFetchMatches: (function() {
            let timeout = null;
            return function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.fetchMatches();
                }, 300);
            };
        })(),
        fetchUserDetails() {
            axios
                .get("/session_user.php")
                .then((response) => {
                    if (response.data.error) {
                        console.error("Erreur de session :", response.data.error);
                    } else {
                        this.user = response.data;
                    }
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des détails utilisateur :", error);
                });
        },
        validateMatch(idMatch) {
            this.loadingMatch = idMatch;
            const formData = new FormData();
            formData.append("id", idMatch);
            axios
                .post("/rest/action.php/matchmgr/certify_match", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then(() => {
                    alert(`Match ${idMatch} validé avec succès !`);
                    this.fetchMatches();
                })
                .catch((error) => {
                    console.error("Erreur lors de la validation du match :", error);
                    alert(`Erreur lors de la validation du match ${idMatch}.`);
                })
                .finally(() => {
                    this.loadingMatch = null;
                });
        },
        resetFilters() {
            this.filter.showNotCertified = false;
            this.filter.showCertified = false;
            this.filter.showForbiddenPlayer = false;
            this.filter.showCertifiable = false;
            this.filter.showPlayedMatchesOnly = false;
            this.filter.showInProgress = true;
            this.searchQuery = "";
        },
    },
    created() {
        this.fetchUserDetails();
        this.fetchAllMatches();
        this.fetchMatches();
    },
});