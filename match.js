import basePath from './utils/basePath.js';
import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import { Notyf } from 'notyf';
import {onSuccess, onError} from "./toaster.js";
import {canAskReport, canAcceptReport, canRefuseReport, canGiveReportDate, isMyTeamInMatch, actingTeamId, postReportAction} from "./utils/reportUtils.js";
import {requireMatchAccess} from "./pages/components/auth/guard.js";
import {STEP_KEYS, currentStep, matchState, nextSignatureStep, sideOf} from "./utils/signatureSteps.js";
import MatchMenu from "./pages/components/match/MatchMenu.js";
import StepCard from "./pages/components/match/StepCard.js";
import PlayersStep from "./pages/components/match/PlayersStep.js";
import SignatureBlock from "./pages/components/match/SignatureBlock.js";
import MatchDayStep from "./pages/components/match/MatchDayStep.js";
import ScoreStep from "./pages/components/match/ScoreStep.js";
import SurveyStep from "./pages/components/match/SurveyStep.js";

// Expose les libs en global pour les sous-composants qui les utilisent sans import
window.axios = axios;
window.Toastify = Toastify;
window.Notyf = Notyf;

/**
 * Page de match unique (#490) : présents → fiche équipes → match → score →
 * feuille de match → sondage. Chaque enregistrement part avec la version du
 * match : si l'autre équipe a enregistré entre-temps, le serveur refuse (409)
 * et la page se recharge au lieu d'écraser sa saisie.
 */
createApp({
    components: {
        'match-menu': MatchMenu,
        'step-card': StepCard,
        'players-step': PlayersStep,
        'signature-block': SignatureBlock,
        'match-day-step': MatchDayStep,
        'score-step': ScoreStep,
        'survey-step': SurveyStep,
    },
    data() { return {
        id_match: (new URLSearchParams(window.location.search)).get('id_match'),
        matchData: {},
        surveyData: {},
        version: null,
        reloadKey: 0,
        // étape ouverte : null = l'étape en cours ; '' = toutes repliées
        openStep: null,
        isLoading: false,
        user: null,
        dateModification: {
            dates: [],
            loading: false,
            invertReception: false,
            forceDate: false,
            comment: '',
            selectedDate: null,
        },
    }; },
    async created() {
        // Résolution code_match -> id_match, pour les liens qui ouvrent /match.html?code_match=...
        if (!this.id_match) {
            const code_match = (new URLSearchParams(window.location.search)).get('code_match');
            if (code_match) {
                try {
                    const {data} = await axios.get(
                        `/rest/action.php/matchmgr/get_match_by_code_match?code_match=${encodeURIComponent(code_match)}`
                    );
                    this.id_match = data && data.id_match ? data.id_match : null;
                } catch (e) {
                    // ignoré : la garde ci-dessous redirigera faute d'id_match
                }
            }
        }
        const user = await requireMatchAccess(this.id_match, ['admin', 'team_leader']);
        if (!user) {
            return; // redirection déjà déclenchée par la garde
        }
        // lien vers une étape : match.html?id_match=…#score (emails, alertes)
        const hash = window.location.hash.replace('#', '');
        if (STEP_KEYS.includes(hash)) {
            this.openStep = hash;
        }
        await this.fetchUserDetails();
        await this.reloadData();
        if (this.openStep) {
            this.$nextTick(() => document.getElementById(this.openStep)?.scrollIntoView({block: 'start'}));
        }
    },
    watch: {
        'dateModification.invertReception'() {
            if (document.getElementById('dateModificationModal').open) {
                this.loadAvailableDates();
            }
        },
        'dateModification.forceDate'() {
            if (document.getElementById('dateModificationModal').open) {
                this.loadAvailableDates();
            }
        },
    },
    computed: {
        isLeader() {
            return !!(this.user && this.user.is_team_leader);
        },
        isAdmin() {
            return !!(this.user && this.user.is_admin);
        },
        // équipe au nom de laquelle le compte agit sur ce match
        myTeamId() {
            return actingTeamId(this.matchData, this.user);
        },
        mySide() {
            return sideOf(this.matchData, this.myTeamId);
        },
        otherSide() {
            return this.mySide === 'dom' ? 'ext' : 'dom';
        },
        // admin qui ne joue pas ce match : il signe pour les deux équipes (#419)
        isCommission() {
            return this.isAdmin && this.mySide === null;
        },
        state() {
            return matchState(this.matchData);
        },
        current() {
            return currentStep(this.matchData, this.mySide);
        },
        nextStep() {
            return nextSignatureStep(this.matchData, this.myTeamId);
        },
        nextStepClass() {
            return {
                warning: 'alert-warning', info: 'alert-info', error: 'alert-error', success: 'alert-success',
            }[this.nextStep?.level] || 'alert-info';
        },
        teamSigners() {
            return ['dom', 'ext'].filter(s => this.state.team[s]).map(s => this.matchData[`equipe_${s}`]).join(' et ');
        },
        // score : modifiable dès que les présents sont figés, jusqu'à la
        // première signature de la feuille de match ; l'admin, toujours
        scoreEditable() {
            if (this.isAdmin) return true;
            if (!this.state.confirmed || this.state.certified) return false;
            return this.state.frozen && !this.state.scoreFrozen;
        },
        scoreLockedReason() {
            if (this.isAdmin) return '';
            if (this.state.scoreFrozen) {
                const signers = ['dom', 'ext'].filter(s => this.state.sheet[s]).map(s => this.matchData[`equipe_${s}`]);
                return `Score figé : la feuille de match est signée par ${signers.join(' et ')}.`
                    + (this.state.closed ? '' : " Pour le corriger, cette équipe annule d'abord sa signature à l'étape 5.");
            }
            if (this.state.forfeit && !this.state.frozen) return 'Forfait enregistré.';
            return '';
        },
        canDeclareForfeit() {
            if (!this.state.confirmed || this.state.certified) return false;
            return this.isAdmin || (!!this.mySide && !this.state.scoreFrozen);
        },
        canScoreLive() {
            if (!this.user) return false;
            if (this.isAdmin) return true;
            return this.isLeader && isMyTeamInMatch(this.matchData, this.user);
        },
        canModifyDate() {
            if (this.matchData.match_status !== 'NOT_CONFIRMED') return false;
            if (this.isAdmin) return true;
            if (!this.isLeader) return false;
            // n'importe laquelle des équipes du compte, comme côté serveur (#421)
            return isMyTeamInMatch(this.matchData, this.user);
        },
    },
    methods: {
        /** Props d'une étape : état, résumé, ouverte ou non. */
        step(key) {
            const s = this.state;
            const side = this.mySide;
            const signedCount = flags => (flags.dom ? 1 : 0) + (flags.ext ? 1 : 0);
            const def = {
                presents: {
                    done: s.frozen || s.forfeit,
                    locked: !s.confirmed,
                    status: s.frozen ? 'figés' : (s.playersFilled ? 'saisis' : 'à saisir'),
                },
                fiche: {
                    done: side ? s.team[side] : s.team.dom && s.team.ext,
                    locked: !s.confirmed || (!s.playersFilled && !s.frozen),
                    status: s.forfeit && !s.frozen ? 'forfait' : `${signedCount(s.team)}/2 signée`,
                },
                match: {
                    done: s.scoreFilled,
                    locked: !s.confirmed,
                    status: s.scoreFilled ? 'joué' : '',
                },
                score: {
                    done: s.scoreFilled,
                    locked: !s.confirmed || (!s.frozen && !s.forfeit && !this.isAdmin),
                    status: s.scoreFilled
                        ? `${this.matchData.score_equipe_dom} - ${this.matchData.score_equipe_ext}`
                        : (s.frozen ? 'à saisir' : 'après la fiche équipes'),
                },
                feuille: {
                    done: side ? s.sheet[side] : s.closed,
                    locked: !s.confirmed || !s.scoreFilled,
                    status: s.scoreFilled ? `${signedCount(s.sheet)}/2 signée` : 'après le score',
                },
                sondage: {
                    done: side ? s.survey[side] : s.survey.dom && s.survey.ext,
                    locked: !s.confirmed || (side ? !s.scoreFilled : false),
                    status: side ? (s.survey[side] ? 'envoyé' : '') : `${signedCount(s.survey)}/2 rempli`,
                },
            }[key];
            let state = def.done ? 'done' : 'todo';
            if (def.locked) state = 'locked';
            else if (this.current === key) state = 'current';
            const open = this.openStep === null ? this.current === key : this.openStep === key;
            return {state, status: def.status, open};
        },
        toggle(key) {
            const isOpen = this.step(key).open;
            this.openStep = isOpen ? '' : key;
            if (!isOpen) {
                history.replaceState(null, '', '#' + key);
                this.$nextTick(() => document.getElementById(key)?.scrollIntoView({block: 'nearest'}));
            }
        },
        toastError(message) {
            onError(this, {response: {data: {message}}});
        },
        async reloadData() {
            this.isLoading = true;
            try {
                const requests = [
                    axios.get(`/rest/action.php/matchmgr/get_match?id_match=${this.id_match}`),
                    axios.get(`/rest/action.php/matchmgr/get_match_version?id_match=${this.id_match}`),
                ];
                const [match, version] = await Promise.all(requests);
                this.matchData = match.data;
                this.version = version.data.version;
                this.reloadKey++;
                if (this.mySide && this.state.confirmed) {
                    await this.loadSurvey();
                }
            } catch (error) {
                onError(this, error);
            } finally {
                this.isLoading = false;
            }
        },
        loadSurvey() {
            return axios.get(`/rest/action.php/matchmgr/get_survey?id_match=${this.id_match}`)
                .then(response => {
                    this.surveyData = response.data;
                })
                .catch(() => {
                    this.surveyData = {};
                });
        },
        /**
         * Enregistre une action de la page avec la version du match. Après
         * l'action, l'étape en cours s'ouvre ; en cas de conflit (409), la page
         * se recharge pour montrer la saisie de l'autre équipe.
         */
        post(action, fields, {withVersion = true} = {}) {
            const formData = new FormData();
            formData.append('id_match', this.id_match);
            Object.entries(fields || {}).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach(v => formData.append(`${key}[]`, v));
                } else if (value && typeof value === 'object') {
                    Object.entries(value).forEach(([k, v]) => formData.append(`${key}[${k}]`, v));
                } else if (value !== undefined && value !== null) {
                    formData.append(key, value);
                }
            });
            if (withVersion && this.version) {
                formData.append('version', this.version);
            }
            this.isLoading = true;
            return axios.post(`/rest/action.php/matchmgr/${action}`, formData)
                .then(response => {
                    onSuccess(this, response);
                    this.openStep = null;
                    return this.reloadData();
                })
                .catch(error => {
                    onError(this, error);
                    if (error.response && error.response.status === 409) {
                        return this.reloadData();
                    }
                });
        },
        savePlayers({player_ids, reinforcements}) {
            this.post('manage_match_players', {player_ids, reinforcements});
        },
        sign(kind) {
            this.post(kind === 'team' ? 'sign_team_sheet' : 'sign_match_sheet');
        },
        unsign(kind) {
            this.post(kind === 'team' ? 'unsign_team_sheet' : 'unsign_match_sheet');
        },
        saveScore(score) {
            this.post('save_match', {code_match: this.matchData.code_match, ...score});
        },
        declareForfeit(side) {
            const fields = {code_match: this.matchData.code_match, referee: this.matchData.referee ?? '',
                note: this.matchData.note ?? '', forfeit: side};
            // save_match attend tous les sets : le serveur les remplace par le score du forfait
            [1, 2, 3, 4, 5].forEach(s => {
                fields[`set_${s}_dom`] = 0;
                fields[`set_${s}_ext`] = 0;
            });
            this.post('save_match', fields);
        },
        saveSurvey(survey) {
            // un sondage par équipe : pas de conflit possible avec l'autre équipe
            this.post('save_survey', survey, {withVersion: false});
        },
        fetchUserDetails() {
            return axios.get("/session_user.php")
                .then((response) => {
                    this.user = response.data.error ? null : response.data;
                })
                .catch(() => {
                });
        },
        canAskReport() {
            return canAskReport(this.matchData, this.user);
        },
        canAcceptReport() {
            return canAcceptReport(this.matchData, this.user);
        },
        canRefuseReport() {
            return canRefuseReport(this.matchData, this.user);
        },
        canGiveReportDate() {
            return canGiveReportDate(this.matchData, this.user);
        },
        postReportAction(actionName) {
            postReportAction(axios, this.matchData.code_match, actionName, () => this.reloadData());
        },
        openDateModificationModal() {
            this.dateModification.dates = [];
            this.dateModification.loading = true;
            this.dateModification.selectedDate = null;
            document.getElementById('dateModificationModal').showModal();
            this.loadAvailableDates();
        },
        loadAvailableDates() {
            this.dateModification.loading = true;
            const params = new URLSearchParams({
                id_match: this.id_match,
                check_opposite_gymnasium: this.dateModification.invertReception ? '1' : '0',
                force_date: this.dateModification.forceDate ? '1' : '0',
            });
            axios.get(`/rest/action.php/matchmgr/get_available_dates_for_match?${params}`)
                .then(response => {
                    this.dateModification.dates = response.data;
                })
                .catch(error => onError(this, error))
                .finally(() => { this.dateModification.loading = false; });
        },
        getDateRowClass(d) {
            if (d.is_holiday) return 'bg-blue-50 opacity-60';
            if (!d.matches_reception_day) return 'bg-gray-50 opacity-40';
            if (d.available) {
                if (this.hasWeekConflicts(d)) return 'bg-yellow-50';
                return '';
            }
            return 'opacity-40';
        },
        hasWeekConflicts(d) {
            if (!d.week_conflicts) return false;
            return (d.week_conflicts.home_team_conflicts?.length > 0) ||
                   (d.week_conflicts.away_team_conflicts?.length > 0);
        },
        getWeekConflictText(d) {
            if (!d.week_conflicts) return '';
            const parts = [];
            (d.week_conflicts.home_team_conflicts || []).forEach(c => {
                parts.push(`${c.team_name} vs ${c.opponent} le ${c.match_date}`);
            });
            (d.week_conflicts.away_team_conflicts || []).forEach(c => {
                parts.push(`${c.team_name} vs ${c.opponent} le ${c.match_date}`);
            });
            return parts.join('\n');
        },
        selectDate(d) {
            if (this.dateModification.forceDate && !this.dateModification.comment.trim()) {
                this.toastError('Un commentaire est obligatoire pour forcer une date.');
                return;
            }
            this.dateModification.selectedDate = d.date;
            document.getElementById('dateModificationModal').close();
            document.getElementById('confirmDateModal').showModal();
        },
        confirmDateModification() {
            this.isLoading = true;
            const formData = new FormData();
            formData.append('id_match', this.id_match);
            formData.append('new_date', this.dateModification.selectedDate);
            formData.append('invert_reception', this.dateModification.invertReception ? '1' : '0');
            if (this.dateModification.comment) {
                formData.append('comment', this.dateModification.comment);
            }
            axios.post('/rest/action.php/matchmgr/modify_match_date', formData)
                .then(response => {
                    onSuccess(this, response);
                    this.reloadData();
                })
                .catch(error => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
    }
}).use(basePath).mount('#app');
