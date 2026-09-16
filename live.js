import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import ScoreBoard from './pages/components/live/ScoreBoard.js';
import ScorerBoard from './pages/components/live/ScorerBoard.js';
import ScorerControls from './pages/components/live/ScorerControls.js';
import ScorerLineup from './pages/components/live/ScorerLineup.js';
import ActiveMatchList from './pages/components/live/ActiveMatchList.js';
import MatchDetails from './pages/components/live/MatchDetails.js';
import {getCurrentUser} from './pages/components/auth/guard.js';

// Expose les libs en global pour les sous-composants qui les utilisent sans import
window.axios = axios;
window.Toastify = Toastify;

const ROTATION_COMPETITION_CODES = ['m', 'c', 'cf'];

const params = new URLSearchParams(window.location.search);

function createEmptyLineup() {
    return {
        1: '',
        2: '',
        3: '',
        4: '',
        5: '',
        6: ''
    };
}

/**
 * Ne garde d'une composition que les valeurs qui sont des identifiants de
 * joueur (issue #332). Les brouillons enregistrés avant ce lot portaient des
 * noms complets : les afficher tels quels donnerait des postes remplis qu'aucune
 * vignette ne peut suivre, mieux vaut les laisser libres.
 */
function keepPlayerIds(lineup) {
    const cleaned = createEmptyLineup();
    Object.keys(cleaned).forEach((position) => {
        const value = lineup ? lineup[position] : '';
        if (value !== '' && value !== null && value !== undefined && !Number.isNaN(Number(value))) {
            cleaned[position] = value;
        }
    });
    return cleaned;
}

createApp({
    components: {
        'score-board': ScoreBoard,
        'scorer-board': ScorerBoard,
        'scorer-controls': ScorerControls,
        'scorer-lineup': ScorerLineup,
        'active-match-list': ActiveMatchList,
        'match-details': MatchDetails
    },
    data() { return {
        idMatch: params.get('id_match'),
        isScorer: (params.get('mode') || 'view') === 'scorer',
        canScore: false,
        currentUser: null,
        teamDomName: null,
        teamExtName: null,
        match: null,
        error: null,
        swapSides: false,
        teamPlayersBySide: {
            dom: [],
            ext: []
        },
        score: {
            set_en_cours: 1,
            score_dom: 0,
            score_ext: 0,
            sets_dom: 0,
            sets_ext: 0
        },
        isLive: false,
        activeLiveScores: [],
        refreshInterval: null,
        timeouts: {
            dom: {
                tm1: { used: false, countdown: 0, timer: null },
                tm2: { used: false, countdown: 0, timer: null }
            },
            ext: {
                tm1: { used: false, countdown: 0, timer: null },
                tm2: { used: false, countdown: 0, timer: null }
            }
        },
        // Positions : { dom: {1: idJoueur, …}, ext: … }. Les valeurs sont des
        // IDENTIFIANTS de joueur depuis l'issue #332 — c'est ce qui permet
        // d'afficher la vignette. Les brouillons d'avant portaient des noms :
        // `restoreFromLocalStorage` les écarte au lieu de les afficher de
        // travers (un brouillon ne survit de toute façon qu'à un match).
        lineups: {
            dom: createEmptyLineup(),
            ext: createEmptyLineup()
        },
        // Composition du set précédent, pour le raccourci « reprendre ».
        previousLineups: {
            dom: null,
            ext: null
        },
        servingTeam: null,
        // Pile d'annulation (issue #332). Chaque entrée est l'état AVANT le
        // point : score, service et positions. Annuler restitue les trois — un
        // `-1` qui ne rendait que le point laissait la rotation décalée pour
        // tout le reste du set, sans que rien ne le signale.
        pointHistory: [],
        maxHistory: 60,
        showLineup: false,
        // Autosave state (issue #196)
        saveStatus: 'saved',
        version: 1,
        autosaveInterval: null,
        retryCount: 0,
        maxRetries: 5,
        baseRetryDelay: 2000,
        retryTimer: null,
        isOnline: true,
        isSaving: false
    }; },
    computed: {
        leftTeamKey() {
            return this.swapSides ? 'ext' : 'dom';
        },
        rightTeamKey() {
            return this.swapSides ? 'dom' : 'ext';
        },
        leftTeamName() {
            return this.leftTeamKey === 'dom' ? this.teamDomName : this.teamExtName;
        },
        rightTeamName() {
            return this.rightTeamKey === 'dom' ? this.teamDomName : this.teamExtName;
        },
        leftTeamLabel() {
            return this.leftTeamKey === 'dom' ? 'Domicile' : 'Extérieur';
        },
        rightTeamLabel() {
            return this.rightTeamKey === 'dom' ? 'Domicile' : 'Extérieur';
        },
        leftTimeouts() {
            return this.timeouts[this.leftTeamKey];
        },
        rightTimeouts() {
            return this.timeouts[this.rightTeamKey];
        },
        leftTeamPlayers() {
            return this.teamPlayersBySide[this.leftTeamKey] || [];
        },
        rightTeamPlayers() {
            return this.teamPlayersBySide[this.rightTeamKey] || [];
        },
        isRotationModeEnabled() {
            const competitionCode = (this.match?.code_competition || '').toString().trim().toLowerCase();
            return ROTATION_COMPETITION_CODES.includes(competitionCode);
        },
        canUndo() {
            return this.pointHistory.length > 0;
        },
        // Le plein écran arbitre ne prend la main qu'une fois le live démarré :
        // avant, il n'y a rien à marquer (issue #332).
        scorerFullScreen() {
            return this.canScore && this.isScorer && this.isLive;
        },
        // Les deux camps tels que l'écran de composition les attend, dans
        // l'ordre affiché (gauche d'abord) — pas dans l'ordre dom/ext.
        lineupSides() {
            return [this.leftTeamKey, this.rightTeamKey].map((key) => ({
                key,
                name: key === 'dom' ? this.teamDomName : this.teamExtName,
                lineup: this.lineups[key],
                players: this.teamPlayersBySide[key] || [],
                hasPrevious: Boolean(this.previousLineups[key]),
            }));
        },
        localStorageKey() {
            return 'live_score_draft_' + this.idMatch;
        },
        // Score FINAL renseigné (match terminé) : is_match_score_filled vaut 1
        // dès qu'une équipe a gagné 3 sets. Peut arriver en chaîne ("0"/"1") via
        // mysqli → comparaison souple ("0" est truthy en JS).
        isMatchFinished() {
            return this.match != null && this.match.is_match_score_filled == 1;
        }
    },
    async mounted() {
        // Charge les données autrefois injectées par live.php (window.__LIVE_DATA__) :
        // infos du match, droits de marquage, joueurs des équipes.
        if (this.idMatch) {
            await this.loadInitialData();
        }
        if (this.idMatch && this.isScorer && this.canScore) {
            const swapKey = 'live_score_swap_' + this.idMatch;
            this.swapSides = localStorage.getItem(swapKey) === '1';
            this.restoreFromLocalStorage();
            this.startAutosave();
            window.addEventListener('online', this.handleOnline);
            window.addEventListener('offline', this.handleOffline);
            this.isOnline = navigator.onLine;
        }
        if (this.idMatch) {
            this.refreshScore();
            this.startAutoRefresh();
        } else {
            this.loadActiveLiveScores();
            this.refreshInterval = setInterval(() => this.loadActiveLiveScores(), 10000);
        }
    },
    beforeDestroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        if (this.autosaveInterval) {
            clearInterval(this.autosaveInterval);
        }
        if (this.retryTimer) {
            clearTimeout(this.retryTimer);
        }
        this.clearAllTimeoutTimers();
        if (this.isScorer) {
            window.removeEventListener('online', this.handleOnline);
            window.removeEventListener('offline', this.handleOffline);
        }
    },
    methods: {
        // Reconstitue côté client les données autrefois calculées par live.php.
        // idMatch est en réalité un code_match (ex. C_9_20260122_040).
        async loadInitialData() {
            try {
                const {data} = await axios.get(
                    `/rest/action.php/matchmgr/get_match_by_code_match?code_match=${encodeURIComponent(this.idMatch)}`
                );
                this.match = data;
                this.teamDomName = data.equipe_dom;
                this.teamExtName = data.equipe_ext;
                document.title = 'Live Score - ' + (data.code_match || '');
            } catch (e) {
                this.error = "Impossible de charger les informations du match.";
                return;
            }
            // Droits de marquage : admin OU responsable d'une des deux équipes
            const user = await getCurrentUser();
            this.currentUser = user;
            if (user && user.is_admin) {
                this.canScore = true;
            } else if (user && user.id_equipe) {
                this.canScore = (user.id_equipe == this.match.id_equipe_dom
                    || user.id_equipe == this.match.id_equipe_ext);
            }
            if (this.canScore && this.isScorer) {
                await this.loadTeamPlayers();
            }
        },
        // Effectifs avec vignettes, RESERVES AU SCOREUR du match (issue #332).
        // `player/getLivePlayersFromTeam` reste volontairement sans PII et en
        // niveau `user` (issue #228) : on ne l'elargit pas aux photos, sinon
        // tout compte connecte pourrait lister n'importe quelle equipe. Cet
        // appel-ci est garde par `canModifyLiveScore()`, et la page publique
        // ne le fait jamais — elle n'affiche pas les compositions.
        async loadTeamPlayers() {
            try {
                const { data } = await axios.get(
                    `/ajax/live_score.php?id_match=${encodeURIComponent(this.idMatch)}&what=rosters`
                );
                const rosters = (data && data.data) || {};
                this.teamPlayersBySide.dom = Array.isArray(rosters.dom) ? rosters.dom : [];
                this.teamPlayersBySide.ext = Array.isArray(rosters.ext) ? rosters.ext : [];
            } catch (e) {
                console.error('Erreur lors du chargement des joueurs:', e);
            }
        },
        // Aiguillage du bouton "Passer en mode scoreur", affiché publiquement.
        // - autorisé (admin / responsable d'une des 2 équipes) : bascule en mode scoreur
        // - non connecté : redirection login, avec retour au mode scoreur après connexion
        // - connecté mais non autorisé : message explicatif, on reste en consultation
        goToScorerMode() {
            const scorerUrl = '/live.html?id_match=' + encodeURIComponent(this.idMatch) + '&mode=scorer';
            if (this.canScore) {
                window.location.href = scorerUrl;
                return;
            }
            if (!this.currentUser) {
                const redirect = encodeURIComponent(window.location.origin + scorerUrl);
                const reason = encodeURIComponent(
                    "Connectez-vous avec un compte responsable d'équipe ou administrateur pour saisir le score en direct."
                );
                window.location.href = `/pages/home.html#/login?redirect=${redirect}&reason=${reason}`;
                return;
            }
            this.showToast(
                "Le mode scoreur est réservé aux responsables des deux équipes et aux administrateurs.",
                'info'
            );
        },
        toggleSwapSides() {
            if (!this.idMatch) {
                return;
            }
            const swapKey = 'live_score_swap_' + this.idMatch;
            this.swapSides = !this.swapSides;
            localStorage.setItem(swapKey, this.swapSides ? '1' : '0');
        },

        incrementLeft() {
            this.incrementScore(this.leftTeamKey);
        },
        incrementRight() {
            this.incrementScore(this.rightTeamKey);
        },
        nextSetLeft() {
            this.nextSet(this.leftTeamKey);
        },
        nextSetRight() {
            this.nextSet(this.rightTeamKey);
        },

        // --- Local state modification (no AJAX) ---
        incrementScore(team) {
            this.pushHistory();
            this.handleServiceAndRotation(team);
            const key = 'score_' + team;
            this.score[key] = (parseInt(this.score[key]) || 0) + 1;
            this.markAsUnsaved();
        },
        /**
         * Empile l'état AVANT le point (issue #332).
         *
         * Les positions sont copiées, pas référencées : `rotateTeamPositions`
         * remplace l'objet, mais une copie de surface protège des reprises
         * futures sans coûter quoi que ce soit ici.
         */
        pushHistory() {
            this.pointHistory.push({
                score: Object.assign({}, this.score),
                servingTeam: this.servingTeam,
                lineups: {
                    dom: Object.assign({}, this.lineups.dom),
                    ext: Object.assign({}, this.lineups.ext)
                }
            });
            if (this.pointHistory.length > this.maxHistory) {
                this.pointHistory.shift();
            }
        },
        /**
         * Annule le dernier point — score, service ET rotation.
         *
         * C'est le point qui a motivé le remplacement des deux `-1` par camp :
         * `decrementScore()` ne rendait que le point. Si ce point avait provoqué
         * une reprise de service, l'équipe avait tourné d'un cran, et la
         * rotation restait fausse jusqu'à la fin du set sans que rien ne le
         * signale.
         */
        undoLastPoint() {
            const previous = this.pointHistory.pop();
            if (!previous) {
                return;
            }
            this.score = Object.assign({}, previous.score);
            this.servingTeam = previous.servingTeam;
            this.lineups = {
                dom: Object.assign({}, previous.lineups.dom),
                ext: Object.assign({}, previous.lineups.ext)
            };
            this.markAsUnsaved();
            this.persistToLocalStorage();
        },
        nextSet(winner) {
            const setNum = parseInt(this.score.set_en_cours) || 1;
            if (setNum > 5) {
                this.showToast('Impossible de dépasser 5 sets', 'error');
                return;
            }
            // Save current set scores
            this.score['set_' + setNum + '_dom'] = this.score.score_dom;
            this.score['set_' + setNum + '_ext'] = this.score.score_ext;
            // Increment sets won
            if (winner === 'dom') {
                this.score.sets_dom = (parseInt(this.score.sets_dom) || 0) + 1;
            } else if (winner === 'ext') {
                this.score.sets_ext = (parseInt(this.score.sets_ext) || 0) + 1;
            }
            // Reset point scores and advance set
            this.score.score_dom = 0;
            this.score.score_ext = 0;
            this.score.set_en_cours = setNum + 1;
            this.resetTimeouts();
            // La composition du set qui s'achève alimente le raccourci
            // « reprendre » du set suivant : une équipe change rarement son six
            // de départ en cours de match (issue #332).
            this.previousLineups = {
                dom: Object.assign({}, this.lineups.dom),
                ext: Object.assign({}, this.lineups.ext)
            };
            this.resetPositions();
            this.servingTeam = null;
            // On n'annule pas au travers d'une fin de set : le score du set
            // précédent est figé, une annulation le laisserait incohérent.
            this.pointHistory = [];
            this.markAsUnsaved();
            this.showToast('Set terminé !', 'success');
        },
        /**
         * Qui sert, et — en compétition à 6 — qui tourne.
         *
         * Les deux étaient liés : le service n'était suivi que si la rotation
         * l'était. Savoir qui sert est pourtant utile dans TOUTES les
         * compétitions, c'est la rotation des six postes qui est spécifique
         * (issue #332).
         */
        handleServiceAndRotation(team) {
            if (!['dom', 'ext'].includes(team)) {
                return;
            }
            if (!this.servingTeam) {
                this.servingTeam = team;
                return;
            }
            if (this.servingTeam !== team) {
                // Side-out : l'équipe qui reprend le service tourne d'un cran.
                if (this.isRotationModeEnabled) {
                    this.rotateTeamPositions(team);
                }
                this.servingTeam = team;
            }
        },
        rotateTeamPositions(team) {
            const current = this.lineups[team] || createEmptyLineup();
            const rotated = {
                1: current[2] || '',
                2: current[3] || '',
                3: current[4] || '',
                4: current[5] || '',
                5: current[6] || '',
                6: current[1] || ''
            };
            this.lineups[team] = rotated;
            this.persistToLocalStorage();
        },
        /**
         * Place un joueur à un poste. La valeur est son IDENTIFIANT — c'est lui
         * qui permet de retrouver la vignette (issue #332).
         *
         * Un joueur déjà placé ailleurs libère son ancien poste : sans ça, on
         * peut se retrouver avec le même joueur à deux endroits du terrain.
         */
        placeInLineup(team, position, idPlayer) {
            if (!this.isRotationModeEnabled || !['dom', 'ext'].includes(team)) {
                return;
            }
            const normalizedPosition = parseInt(position, 10);
            if (!Number.isInteger(normalizedPosition) || normalizedPosition < 1 || normalizedPosition > 6) {
                return;
            }
            const updated = Object.assign({}, this.lineups[team]);
            Object.keys(updated).forEach((key) => {
                if (String(updated[key]) === String(idPlayer)) {
                    updated[key] = '';
                }
            });
            updated[normalizedPosition] = idPlayer;
            this.lineups[team] = updated;
            this.persistToLocalStorage();
        },
        clearLineup(team) {
            if (!this.isRotationModeEnabled || !['dom', 'ext'].includes(team)) {
                return;
            }
            this.lineups[team] = createEmptyLineup();
            this.persistToLocalStorage();
        },
        repeatPreviousLineup(team) {
            const previous = this.previousLineups[team];
            if (!previous) {
                return;
            }
            this.lineups[team] = Object.assign({}, previous);
            this.persistToLocalStorage();
        },
        resetPositions() {
            if (!this.isRotationModeEnabled) {
                return;
            }
            this.lineups.dom = createEmptyLineup();
            this.lineups.ext = createEmptyLineup();
            this.persistToLocalStorage();
        },

        // --- Save status management ---
        markAsUnsaved() {
            this.saveStatus = 'unsaved';
            this.persistToLocalStorage();
        },

        // --- Autosave ---
        startAutosave() {
            this.autosaveInterval = setInterval(() => {
                if (this.saveStatus === 'unsaved' && !this.isSaving && this.isOnline) {
                    this.saveScore();
                }
            }, 5000);
        },
        async saveScore() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.saveStatus = 'saving';
            try {
                const scoreData = {
                    score_dom: parseInt(this.score.score_dom) || 0,
                    score_ext: parseInt(this.score.score_ext) || 0,
                    sets_dom: parseInt(this.score.sets_dom) || 0,
                    sets_ext: parseInt(this.score.sets_ext) || 0,
                    set_en_cours: parseInt(this.score.set_en_cours) || 1,
                    set_1_dom: parseInt(this.score.set_1_dom) || 0,
                    set_1_ext: parseInt(this.score.set_1_ext) || 0,
                    set_2_dom: parseInt(this.score.set_2_dom) || 0,
                    set_2_ext: parseInt(this.score.set_2_ext) || 0,
                    set_3_dom: parseInt(this.score.set_3_dom) || 0,
                    set_3_ext: parseInt(this.score.set_3_ext) || 0,
                    set_4_dom: parseInt(this.score.set_4_dom) || 0,
                    set_4_ext: parseInt(this.score.set_4_ext) || 0,
                    set_5_dom: parseInt(this.score.set_5_dom) || 0,
                    set_5_ext: parseInt(this.score.set_5_ext) || 0
                };
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'upsert',
                    id_match: this.idMatch,
                    score_data: scoreData,
                    version: this.version
                });
                if (response.data.success) {
                    this.version = response.data.version;
                    this.saveStatus = 'saved';
                    this.retryCount = 0;
                    this.clearLocalStorage();
                } else if (response.data.error === 'version_conflict') {
                    // Server has a newer version — adopt server state
                    this.score = response.data.data;
                    this.version = response.data.version;
                    this.saveStatus = 'saved';
                    this.retryCount = 0;
                    this.clearLocalStorage();
                    this.showToast('Conflit de version résolu (état serveur adopté)', 'info');
                }
            } catch (error) {
                console.error('Save error:', error);
                this.saveStatus = 'error';
                this.retryCount++;
                if (this.retryCount <= this.maxRetries) {
                    this.scheduleRetry();
                } else {
                    this.showToast('Échec d\'enregistrement après ' + this.maxRetries + ' tentatives', 'error');
                }
            } finally {
                this.isSaving = false;
            }
        },
        scheduleRetry() {
            const delay = this.baseRetryDelay * Math.pow(2, this.retryCount - 1);
            console.log('Retry scheduled in ' + delay + 'ms (attempt ' + this.retryCount + ')');
            if (this.retryTimer) clearTimeout(this.retryTimer);
            this.retryTimer = setTimeout(() => {
                if (this.saveStatus === 'error' && this.isOnline) {
                    this.saveScore();
                }
            }, delay);
        },

        // --- localStorage persistence ---
        persistToLocalStorage() {
            try {
                const draft = {
                    score: this.score,
                    lineups: this.lineups,
                    previousLineups: this.previousLineups,
                    servingTeam: this.servingTeam,
                    version: this.version,
                    timestamp: Date.now()
                };
                localStorage.setItem(this.localStorageKey, JSON.stringify(draft));
            } catch (e) {
                console.warn('Failed to persist to localStorage:', e);
            }
        },
        restoreFromLocalStorage() {
            try {
                const raw = localStorage.getItem(this.localStorageKey);
                if (!raw) return;
                const draft = JSON.parse(raw);
                // Only restore if less than 24h old
                if (draft.timestamp && (Date.now() - draft.timestamp) < 86400000) {
                    this.score = draft.score;
                    if (draft.lineups?.dom && draft.lineups?.ext) {
                        // Les brouillons antérieurs à l'issue #332 portaient des
                        // NOMS ; on ne garde que ce qui ressemble à un
                        // identifiant, le reste laisse le poste libre.
                        this.lineups = {
                            dom: keepPlayerIds(draft.lineups.dom),
                            ext: keepPlayerIds(draft.lineups.ext)
                        };
                    }
                    if (draft.previousLineups?.dom || draft.previousLineups?.ext) {
                        this.previousLineups = {
                            dom: draft.previousLineups.dom ? keepPlayerIds(draft.previousLineups.dom) : null,
                            ext: draft.previousLineups.ext ? keepPlayerIds(draft.previousLineups.ext) : null
                        };
                    }
                    this.servingTeam = draft.servingTeam || null;
                    this.version = draft.version;
                    this.saveStatus = 'unsaved';
                    this.showToast('Brouillon restauré depuis le stockage local', 'info');
                } else {
                    this.clearLocalStorage();
                }
            } catch (e) {
                console.warn('Failed to restore from localStorage:', e);
                this.clearLocalStorage();
            }
        },
        clearLocalStorage() {
            try {
                localStorage.removeItem(this.localStorageKey);
            } catch (e) {
                console.warn('Failed to clear localStorage:', e);
            }
        },

        // --- Online/Offline handling ---
        handleOnline() {
            this.isOnline = true;
            this.showToast('Connexion rétablie', 'success');
            if (this.saveStatus === 'unsaved' || this.saveStatus === 'error') {
                this.retryCount = 0;
                this.saveScore();
            }
        },
        handleOffline() {
            this.isOnline = false;
            this.showToast('Connexion perdue — les modifications sont sauvegardées localement', 'info');
        },

        // --- Auto-refresh (viewers only) ---
        startAutoRefresh() {
            if (!this.isScorer) {
                this.refreshInterval = setInterval(() => this.refreshScore(), 5000);
            }
        },
        async refreshScore() {
            try {
                const response = await axios.get('/ajax/live_score.php?id_match=' + this.idMatch);
                if (response.data.success && response.data.data) {
                    if (!this.isScorer) {
                        this.score = response.data.data;
                    }
                    if (response.data.data.version) {
                        // For scorer: update version on initial load only if no pending changes
                        if (this.isScorer && this.saveStatus === 'saved') {
                            this.score = response.data.data;
                            this.version = parseInt(response.data.data.version) || this.version;
                        }
                    }
                    this.isLive = true;
                } else {
                    this.isLive = false;
                }
            } catch (error) {
                console.error('Error refreshing score:', error);
            }
        },
        async loadActiveLiveScores() {
            try {
                const response = await axios.get('/ajax/live_score.php');
                if (response.data.success) {
                    this.activeLiveScores = response.data.data;
                }
            } catch (error) {
                console.error('Error loading active live scores:', error);
            }
        },
        async startLiveScore() {
            console.log('Starting live score with idMatch:', this.idMatch);
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'start',
                    id_match: this.idMatch
                });
                if (response.data.success) {
                    this.isLive = true;
                    this.version = 1;
                    this.saveStatus = 'saved';
                    this.servingTeam = null;
                    this.resetPositions();
                    this.showToast('Live score démarré !', 'success');
                    if (!this.autosaveInterval) {
                        this.startAutosave();
                    }
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async endLiveScore() {
            if (!confirm('Êtes-vous sûr de vouloir terminer le live score ?')) return;
            // Flush pending changes before ending
            if (this.saveStatus === 'unsaved') {
                await this.saveScore();
            }
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'end',
                    id_match: this.idMatch
                });
                if (response.data.success) {
                    this.isLive = false;
                    this.clearLocalStorage();
                    this.showToast('Live score terminé', 'info');
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async saveToMatch() {
            if (!confirm('Enregistrer les scores dans le match ? Cette action terminera le live score.')) return;
            // Flush pending changes before saving to match
            if (this.saveStatus === 'unsaved') {
                await this.saveScore();
            }
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'save_to_match',
                    id_match: this.idMatch
                });
                if (response.data.success) {
                    this.isLive = false;
                    this.clearLocalStorage();
                    this.showToast('Scores enregistrés dans le match !', 'success');
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        startTimeout(teamKey, num) {
            const tm = this.timeouts[teamKey]['tm' + num];
            if (tm.used) return;
            tm.used = true;
            tm.countdown = 30;
            tm.timer = setInterval(() => {
                tm.countdown--;
                if (tm.countdown <= 0) {
                    tm.countdown = 0;
                    clearInterval(tm.timer);
                    tm.timer = null;
                }
            }, 1000);
        },
        resetTimeouts() {
            this.clearAllTimeoutTimers();
            ['dom', 'ext'].forEach(team => {
                ['tm1', 'tm2'].forEach(tm => {
                    this.timeouts[team][tm].used = false;
                    this.timeouts[team][tm].countdown = 0;
                    this.timeouts[team][tm].timer = null;
                });
            });
        },
        clearAllTimeoutTimers() {
            ['dom', 'ext'].forEach(team => {
                ['tm1', 'tm2'].forEach(tm => {
                    if (this.timeouts[team][tm].timer) {
                        clearInterval(this.timeouts[team][tm].timer);
                        this.timeouts[team][tm].timer = null;
                    }
                });
            });
        },
        showToast(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "center",
                backgroundColor: colors[type] || colors.info
            }).showToast();
        }
    }
}).mount('#app');
