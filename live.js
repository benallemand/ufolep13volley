import ScoreBoard from './pages/components/live/ScoreBoard.js';
import ScorerControls from './pages/components/live/ScorerControls.js';
import ActiveMatchList from './pages/components/live/ActiveMatchList.js';
import MatchDetails from './pages/components/live/MatchDetails.js';

const liveData = window.__LIVE_DATA__ || {};

new Vue({
    el: '#app',
    components: {
        'score-board': ScoreBoard,
        'scorer-controls': ScorerControls,
        'active-match-list': ActiveMatchList,
        'match-details': MatchDetails
    },
    data: {
        idMatch: liveData.idMatch,
        isScorer: liveData.isScorer || false,
        canScore: liveData.canScore || false,
        teamDomName: liveData.match ? liveData.match.equipe_dom : null,
        teamExtName: liveData.match ? liveData.match.equipe_ext : null,
        match: liveData.match || null,
        error: liveData.error || null,
        swapSides: false,
        score: {
            set_en_cours: liveData.liveScoreData ? liveData.liveScoreData.set_en_cours : 1,
            score_dom: liveData.liveScoreData ? liveData.liveScoreData.score_dom : 0,
            score_ext: liveData.liveScoreData ? liveData.liveScoreData.score_ext : 0,
            sets_dom: liveData.liveScoreData ? liveData.liveScoreData.sets_dom : 0,
            sets_ext: liveData.liveScoreData ? liveData.liveScoreData.sets_ext : 0
        },
        isLive: liveData.liveScoreData !== null && liveData.liveScoreData !== undefined,
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
        // Autosave state (issue #196)
        saveStatus: 'saved',
        version: liveData.liveScoreData ? parseInt(liveData.liveScoreData.version) || 1 : 1,
        autosaveInterval: null,
        retryCount: 0,
        maxRetries: 5,
        baseRetryDelay: 2000,
        retryTimer: null,
        isOnline: true,
        isSaving: false
    },
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
        localStorageKey() {
            return 'live_score_draft_' + this.idMatch;
        }
    },
    mounted() {
        if (this.idMatch && this.isScorer) {
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
        decrementLeft() {
            this.decrementScore(this.leftTeamKey);
        },
        decrementRight() {
            this.decrementScore(this.rightTeamKey);
        },
        nextSetLeft() {
            this.nextSet(this.leftTeamKey);
        },
        nextSetRight() {
            this.nextSet(this.rightTeamKey);
        },

        // --- Local state modification (no AJAX) ---
        incrementScore(team) {
            const key = 'score_' + team;
            this.$set(this.score, key, (parseInt(this.score[key]) || 0) + 1);
            this.markAsUnsaved();
        },
        decrementScore(team) {
            const key = 'score_' + team;
            const current = parseInt(this.score[key]) || 0;
            this.$set(this.score, key, Math.max(0, current - 1));
            this.markAsUnsaved();
        },
        nextSet(winner) {
            const setNum = parseInt(this.score.set_en_cours) || 1;
            if (setNum > 5) {
                this.showToast('Impossible de dépasser 5 sets', 'error');
                return;
            }
            // Save current set scores
            this.$set(this.score, 'set_' + setNum + '_dom', this.score.score_dom);
            this.$set(this.score, 'set_' + setNum + '_ext', this.score.score_ext);
            // Increment sets won
            if (winner === 'dom') {
                this.$set(this.score, 'sets_dom', (parseInt(this.score.sets_dom) || 0) + 1);
            } else if (winner === 'ext') {
                this.$set(this.score, 'sets_ext', (parseInt(this.score.sets_ext) || 0) + 1);
            }
            // Reset point scores and advance set
            this.$set(this.score, 'score_dom', 0);
            this.$set(this.score, 'score_ext', 0);
            this.$set(this.score, 'set_en_cours', setNum + 1);
            this.resetTimeouts();
            this.markAsUnsaved();
            this.showToast('Set terminé !', 'success');
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
});
