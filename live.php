<?php
require_once __DIR__ . '/classes/MatchMgr.php';
require_once __DIR__ . '/classes/LiveScore.php';
require_once __DIR__ . '/classes/UserManager.php';

$id_match = filter_input(INPUT_GET, 'id_match');
$mode = filter_input(INPUT_GET, 'mode') ?? 'view'; // 'view' or 'scorer'

$match = null;
$liveScoreData = null;
$error = null;
$isScorer = ($mode === 'scorer');

@session_start();
$isLoggedIn = isset($_SESSION['login']);
$isAdmin = UserManager::isAdmin();
$canScore = false;

if ($id_match) {
    try {
        $manager = new MatchMgr();
        $match = $manager->get_match_by_code_match($id_match);
        
        $liveScore = new LiveScore();
        $liveScoreData = $liveScore->getLiveScore($id_match);
        
        // Check scorer permissions: admin OR team leader of one of the teams
        if ($isAdmin) {
            $canScore = true;
        } elseif ($isLoggedIn && isset($_SESSION['id_equipe'])) {
            $userTeamId = $_SESSION['id_equipe'];
            $canScore = ($userTeamId == $match['id_equipe_dom'] || $userTeamId == $match['id_equipe_ext']);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<HTML data-theme="cupcake" lang="fr">
<HEAD>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <TITLE>Live Score<?php echo $match ? ' - ' . htmlspecialchars($match['code_match']) : ''; ?></TITLE>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
          integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</HEAD>
<BODY>
<div id="app" class="min-h-screen bg-base-200">
    <!-- Header -->
    <div class="navbar bg-primary text-primary-content">
        <div class="flex-1">
            <a href="/pages/home.html" class="btn btn-ghost text-xl">
                <i class="fas fa-volleyball"></i> UFOLEP 13
            </a>
        </div>
        <div class="flex-none">
            <span class="badge badge-secondary" v-if="isLive">
                <i class="fas fa-circle text-red-500 animate-pulse mr-1"></i> LIVE
            </span>
        </div>
    </div>

    <!-- Match Info -->
    <div class="container mx-auto p-4 max-w-2xl">
        <?php if ($error): ?>
        <div class="alert alert-error mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Erreur: <?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($match): ?>
        <!-- Competition Badge -->
        <div class="text-center mb-4">
            <span class="badge badge-info badge-lg"><?php echo htmlspecialchars($match['libelle_competition']); ?></span>
            <span class="badge badge-outline ml-2">Division <?php echo htmlspecialchars($match['division']); ?></span>
        </div>

        <!-- Score Board -->
        <div class="card bg-base-100 shadow-xl mb-4">
            <div class="card-body p-4">
                <!-- Teams and Score -->
                <div class="grid grid-cols-3 gap-2 items-center text-center">
                    <!-- Home Team -->
                    <div>
                        <p class="font-bold text-lg truncate">{{ leftTeamName }}</p>
                        <p class="text-xs text-gray-500">{{ leftTeamLabel }}</p>
                    </div>
                    
                    <!-- Score -->
                    <div>
                        <div class="text-5xl font-bold">
                            <span class="text-primary">{{ leftScore }}</span>
                            <span class="text-gray-400">-</span>
                            <span class="text-secondary">{{ rightScore }}</span>
                        </div>
                        <p class="text-sm mt-1">Set {{ score.set_en_cours }}</p>
                        <p class="text-lg font-bold">({{ leftSets }} - {{ rightSets }})</p>
                        
                        <!-- Set Details -->
                        <div class="text-xs text-gray-500 mt-2 flex gap-2 justify-center flex-wrap">
                            <span v-if="score.set_1_dom || score.set_1_ext" class="badge badge-ghost">S1: {{ leftSetScore(1) }}-{{ rightSetScore(1) }}</span>
                            <span v-if="score.set_2_dom || score.set_2_ext" class="badge badge-ghost">S2: {{ leftSetScore(2) }}-{{ rightSetScore(2) }}</span>
                            <span v-if="score.set_3_dom || score.set_3_ext" class="badge badge-ghost">S3: {{ leftSetScore(3) }}-{{ rightSetScore(3) }}</span>
                            <span v-if="score.set_4_dom || score.set_4_ext" class="badge badge-ghost">S4: {{ leftSetScore(4) }}-{{ rightSetScore(4) }}</span>
                            <span v-if="score.set_5_dom || score.set_5_ext" class="badge badge-ghost">S5: {{ leftSetScore(5) }}-{{ rightSetScore(5) }}</span>
                        </div>
                    </div>
                    
                    <!-- Away Team -->
                    <div>
                        <p class="font-bold text-lg truncate">{{ rightTeamName }}</p>
                        <p class="text-xs text-gray-500">{{ rightTeamLabel }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scorer Controls (only for authorized users in scorer mode) -->
        <?php if ($canScore && $isScorer): ?>
        <div class="card bg-base-100 shadow-xl mb-4" v-if="isLive">
            <div class="card-body p-4">
                <h3 class="text-center font-bold mb-4">Contrôles Scoreur</h3>

                <div class="text-center mb-4">
                    <button @click="toggleSwapSides()" class="btn btn-outline btn-sm">
                        <i class="fas fa-exchange-alt mr-1"></i> Inverser les équipes
                    </button>
                </div>
                
                <!-- Score Buttons -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Left buttons -->
                    <div class="flex flex-col gap-2">
                        <button @click="incrementLeft()" 
                                class="btn btn-primary btn-lg h-24 text-3xl">
                            +1
                        </button>
                        <button @click="decrementLeft()" 
                                class="btn btn-outline btn-sm">
                            -1
                        </button>
                    </div>
                    
                    <!-- Right buttons -->
                    <div class="flex flex-col gap-2">
                        <button @click="incrementRight()" 
                                class="btn btn-secondary btn-lg h-24 text-3xl">
                            +1
                        </button>
                        <button @click="decrementRight()" 
                                class="btn btn-outline btn-sm">
                            -1
                        </button>
                    </div>
                </div>
                
                <!-- Timeouts -->
                <div class="divider">Temps morts</div>
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <!-- Left team timeouts -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-xs font-semibold">{{ leftTeamName }}</span>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <label class="label cursor-pointer gap-1 p-0">
                                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm"
                                           :checked="leftTimeouts.tm1.used"
                                           :disabled="leftTimeouts.tm1.used"
                                           @change="startTimeout(leftTeamKey, 1)">
                                    <span class="label-text text-xs">TM1</span>
                                </label>
                                <span v-if="leftTimeouts.tm1.countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                                    {{ leftTimeouts.tm1.countdown }}s
                                </span>
                                <span v-else-if="leftTimeouts.tm1.used" class="text-xs text-gray-400">Terminé</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <label class="label cursor-pointer gap-1 p-0">
                                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm"
                                           :checked="leftTimeouts.tm2.used"
                                           :disabled="leftTimeouts.tm2.used"
                                           @change="startTimeout(leftTeamKey, 2)">
                                    <span class="label-text text-xs">TM2</span>
                                </label>
                                <span v-if="leftTimeouts.tm2.countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                                    {{ leftTimeouts.tm2.countdown }}s
                                </span>
                                <span v-else-if="leftTimeouts.tm2.used" class="text-xs text-gray-400">Terminé</span>
                            </div>
                        </div>
                    </div>
                    <!-- Right team timeouts -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-xs font-semibold">{{ rightTeamName }}</span>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <label class="label cursor-pointer gap-1 p-0">
                                    <input type="checkbox" class="checkbox checkbox-secondary checkbox-sm"
                                           :checked="rightTimeouts.tm1.used"
                                           :disabled="rightTimeouts.tm1.used"
                                           @change="startTimeout(rightTeamKey, 1)">
                                    <span class="label-text text-xs">TM1</span>
                                </label>
                                <span v-if="rightTimeouts.tm1.countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                                    {{ rightTimeouts.tm1.countdown }}s
                                </span>
                                <span v-else-if="rightTimeouts.tm1.used" class="text-xs text-gray-400">Terminé</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <label class="label cursor-pointer gap-1 p-0">
                                    <input type="checkbox" class="checkbox checkbox-secondary checkbox-sm"
                                           :checked="rightTimeouts.tm2.used"
                                           :disabled="rightTimeouts.tm2.used"
                                           @change="startTimeout(rightTeamKey, 2)">
                                    <span class="label-text text-xs">TM2</span>
                                </label>
                                <span v-if="rightTimeouts.tm2.countdown > 0" class="countdown text-lg font-bold text-warning animate-pulse">
                                    {{ rightTimeouts.tm2.countdown }}s
                                </span>
                                <span v-else-if="rightTimeouts.tm2.used" class="text-xs text-gray-400">Terminé</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Set Controls -->
                <div class="divider">Gestion des Sets</div>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="nextSetLeft()" class="btn btn-outline btn-primary">
                        <i class="fas fa-trophy mr-1"></i> Set gagné gauche
                    </button>
                    <button @click="nextSetRight()" class="btn btn-outline btn-secondary">
                        <i class="fas fa-trophy mr-1"></i> Set gagné droite
                    </button>
                </div>
                
                <!-- End Match -->
                <div class="mt-4 flex flex-col gap-2">
                    <button @click="saveToMatch()" class="btn btn-success btn-block" v-if="score && (score.sets_dom >= 3 || score.sets_ext >= 3)">
                        <i class="fas fa-save mr-1"></i> Renseigner les scores du match
                    </button>
                    <button @click="endLiveScore()" class="btn btn-error btn-block">
                        <i class="fas fa-stop mr-1"></i> Terminer le Live
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Start Live Button -->
        <div class="text-center mb-4" v-if="!isLive">
            <button @click="startLiveScore()" class="btn btn-success btn-lg">
                <i class="fas fa-play mr-2"></i> Démarrer le Live Score
            </button>
        </div>
        <?php endif; ?>

        <!-- Status Messages -->
        <div class="alert alert-info mb-4" v-if="!isLive && !isScorer">
            <i class="fas fa-info-circle"></i>
            <span>Le live score n'est pas encore démarré pour ce match.</span>
        </div>

        <!-- Auto-refresh indicator -->
        <div class="text-center text-sm text-gray-500" v-if="isLive && !isScorer">
            <i class="fas fa-sync-alt animate-spin mr-1"></i>
            Mise à jour automatique toutes les 5 secondes
        </div>

        <!-- Match Details -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body p-4">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div><i class="fas fa-calendar mr-1"></i> <?php echo htmlspecialchars($match['date_reception'] ?? 'Non définie'); ?></div>
                    <div><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($match['heure_reception'] ?? ''); ?></div>
                    <div class="col-span-2"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($match['gymnasium'] ?? 'Non défini'); ?></div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- No match selected - show active live scores -->
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold mb-2">Matchs en direct</h2>
            <p class="text-gray-500">Sélectionnez un match pour suivre le score</p>
        </div>
        
        <div v-if="activeLiveScores.length === 0" class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <span>Aucun match en direct actuellement.</span>
        </div>
        
        <div v-for="live in activeLiveScores" :key="live.id_match" class="card bg-base-100 shadow-xl mb-4">
            <div class="card-body p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-bold">{{ live.equipe_dom }} vs {{ live.equipe_ext }}</p>
                        <p class="text-sm text-gray-500">{{ live.code_competition }} - Div {{ live.division }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ live.score_dom }} - {{ live.score_ext }}</p>
                        <p class="text-xs">Set {{ live.set_en_cours }}</p>
                    </div>
                    <a :href="'/live.php?id_match=' + live.id_match" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
new Vue({
    el: '#app',
    data: {
        idMatch: <?php echo json_encode($id_match); ?>,
        isScorer: <?php echo json_encode($isScorer); ?>,
        teamDomName: <?php echo json_encode($match['equipe_dom'] ?? null); ?>,
        teamExtName: <?php echo json_encode($match['equipe_ext'] ?? null); ?>,
        swapSides: false,
        score: {
            set_en_cours: <?php echo $liveScoreData['set_en_cours'] ?? 1; ?>,
            score_dom: <?php echo $liveScoreData['score_dom'] ?? 0; ?>,
            score_ext: <?php echo $liveScoreData['score_ext'] ?? 0; ?>,
            sets_dom: <?php echo $liveScoreData['sets_dom'] ?? 0; ?>,
            sets_ext: <?php echo $liveScoreData['sets_ext'] ?? 0; ?>
        },
        isLive: <?php echo json_encode($liveScoreData !== null); ?>,
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
        }
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
        leftScore() {
            return this.leftTeamKey === 'dom' ? this.score.score_dom : this.score.score_ext;
        },
        rightScore() {
            return this.rightTeamKey === 'dom' ? this.score.score_dom : this.score.score_ext;
        },
        leftSets() {
            return this.leftTeamKey === 'dom' ? this.score.sets_dom : this.score.sets_ext;
        },
        rightSets() {
            return this.rightTeamKey === 'dom' ? this.score.sets_dom : this.score.sets_ext;
        },
        leftTimeouts() {
            return this.timeouts[this.leftTeamKey];
        },
        rightTimeouts() {
            return this.timeouts[this.rightTeamKey];
        }
    },
    mounted() {
        if (this.idMatch && this.isScorer) {
            const swapKey = 'live_score_swap_' + this.idMatch;
            this.swapSides = localStorage.getItem(swapKey) === '1';
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
        this.clearAllTimeoutTimers();
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
        leftSetScore(setNum) {
            const key = this.leftTeamKey;
            const prop = 'set_' + setNum + '_' + key;
            return this.score[prop] ?? 0;
        },
        rightSetScore(setNum) {
            const key = this.rightTeamKey;
            const prop = 'set_' + setNum + '_' + key;
            return this.score[prop] ?? 0;
        },
        incrementLeft() {
            return this.incrementScore(this.leftTeamKey);
        },
        incrementRight() {
            return this.incrementScore(this.rightTeamKey);
        },
        decrementLeft() {
            return this.decrementScore(this.leftTeamKey);
        },
        decrementRight() {
            return this.decrementScore(this.rightTeamKey);
        },
        nextSetLeft() {
            return this.nextSet(this.leftTeamKey);
        },
        nextSetRight() {
            return this.nextSet(this.rightTeamKey);
        },
        startAutoRefresh() {
            if (!this.isScorer) {
                this.refreshInterval = setInterval(() => this.refreshScore(), 5000);
            }
        },
        async refreshScore() {
            try {
                const response = await axios.get('/ajax/live_score.php?id_match=' + this.idMatch);
                if (response.data.success && response.data.data) {
                    this.score = response.data.data;
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
                    this.showToast('Live score démarré !', 'success');
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async incrementScore(team) {
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'increment',
                    id_match: this.idMatch,
                    team: team
                });
                if (response.data.success) {
                    this.score = response.data.data;
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async decrementScore(team) {
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'decrement',
                    id_match: this.idMatch,
                    team: team
                });
                if (response.data.success) {
                    this.score = response.data.data;
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async nextSet(winner) {
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'next_set',
                    id_match: this.idMatch,
                    set_winner: winner
                });
                if (response.data.success) {
                    this.score = response.data.data;
                    this.resetTimeouts();
                    this.showToast('Set terminé !', 'success');
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async endLiveScore() {
            if (!confirm('Êtes-vous sûr de vouloir terminer le live score ?')) return;
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'end',
                    id_match: this.idMatch
                });
                if (response.data.success) {
                    this.isLive = false;
                    this.showToast('Live score terminé', 'info');
                }
            } catch (error) {
                this.showToast('Erreur: ' + error.response?.data?.error, 'error');
            }
        },
        async saveToMatch() {
            if (!confirm('Enregistrer les scores dans le match ? Cette action terminera le live score.')) return;
            try {
                const response = await axios.post('/ajax/live_score.php', {
                    action: 'save_to_match',
                    id_match: this.idMatch
                });
                if (response.data.success) {
                    this.isLive = false;
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
</script>
</BODY>
</HTML>
