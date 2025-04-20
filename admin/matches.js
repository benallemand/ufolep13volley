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
        filter: {
            selectedCompetition: "",
            selectedDivision: "",
            showCertified: false,
            showNotCertified: false,
            showForbiddenPlayer: false,
            showPlayedMatchesOnly: false,
            showCertifiable: false,
        },
        user: null,
        availableCompetitions: [],
        availableDivisions: [],
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
            return this.matches.filter((match) => {
                const matchesSearch =
                    match.equipe_dom.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.equipe_ext.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.libelle_competition.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.division.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    match.code_match.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesCompetition =
                    !this.filter.selectedCompetition ||
                    match.libelle_competition === this.filter.selectedCompetition;
                const matchesDivision =
                    !this.filter.selectedDivision || match.division === this.filter.selectedDivision;
                const matchesCertif =
                    !this.filter.showCertified || match.certif === 1;
                const matchesNotCertif =
                    !this.filter.showNotCertified || match.certif !== 1;
                const matchesForbiddenPlayers =
                    !this.filter.showForbiddenPlayer ||
                    match.has_forbidden_player === 1;
                const matchesPlayed =
                    !this.filter.showPlayedMatchesOnly ||
                    match.is_match_score_filled === 1;

                const matchesCertifiable =
                    !this.filter.showCertifiable ||
                    (match.certif === 0 &&
                        match.has_forbidden_player === 0 &&
                        match.is_match_score_filled === 1 &&
                        match.is_match_player_filled === 1 &&
                        match.is_sign_match_dom === 1 &&
                        match.is_sign_match_ext === 1 &&
                        match.is_sign_team_dom === 1 &&
                        match.is_sign_team_ext === 1 &&
                        match.is_survey_filled_dom === 1 &&
                        match.is_survey_filled_ext === 1);

                return matchesSearch
                    && matchesCompetition
                    && matchesDivision
                    && matchesCertif
                    && matchesNotCertif
                    && matchesForbiddenPlayers
                    && matchesPlayed
                    && matchesCertifiable;
            });
        },
    },
    methods: {
        updateAvailableCompetitions() {
            const competitions = new Set(this.matches.map((match) => match.libelle_competition));
            this.availableCompetitions = Array.from(competitions).sort();
        },
        updateAvailableDivisions() {
            if (this.filter.selectedCompetition) {
                const divisions = new Set(
                    this.matches
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
            axios
                .get("/rest/action.php/matchmgr/getMatches")
                .then((response) => {
                    this.matches = response.data;
                    this.updateAvailableCompetitions();
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
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
            this.searchQuery = "";
        },
    },
    created() {
        this.fetchUserDetails();
        this.fetchMatches();
    },
});