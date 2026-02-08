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
        <div class="alert alert-error mb-4" v-if="error">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Erreur: {{ error }}</span>
        </div>
        
        <template v-if="match">
            <!-- Competition Badge -->
            <div class="text-center mb-4">
                <span class="badge badge-info badge-lg">{{ match.libelle_competition }}</span>
                <span class="badge badge-outline ml-2">Division {{ match.division }}</span>
            </div>

            <!-- Score Board -->
            <score-board
                :score="score"
                :left-team-name="leftTeamName"
                :right-team-name="rightTeamName"
                :left-team-label="leftTeamLabel"
                :right-team-label="rightTeamLabel"
                :left-team-key="leftTeamKey"
                :right-team-key="rightTeamKey">
            </score-board>

            <!-- Scorer Controls (only for authorized users in scorer mode) -->
            <template v-if="canScore && isScorer">
                <scorer-controls
                    :score="score"
                    :is-live="isLive"
                    :save-status="saveStatus"
                    :is-online="isOnline"
                    :left-team-name="leftTeamName"
                    :right-team-name="rightTeamName"
                    :left-team-key="leftTeamKey"
                    :right-team-key="rightTeamKey"
                    :left-timeouts="leftTimeouts"
                    :right-timeouts="rightTimeouts"
                    @increment-left="incrementLeft"
                    @increment-right="incrementRight"
                    @decrement-left="decrementLeft"
                    @decrement-right="decrementRight"
                    @next-set-left="nextSetLeft"
                    @next-set-right="nextSetRight"
                    @save-score="saveScore"
                    @save-to-match="saveToMatch"
                    @end-live="endLiveScore"
                    @start-timeout="startTimeout"
                    @swap-sides="toggleSwapSides">
                </scorer-controls>
                
                <!-- Start Live Button -->
                <div class="text-center mb-4" v-if="!isLive">
                    <button @click="startLiveScore()" class="btn btn-success btn-lg">
                        <i class="fas fa-play mr-2"></i> Démarrer le Live Score
                    </button>
                </div>
            </template>

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
            <match-details :match="match"></match-details>
        </template>

        <!-- No match selected - show active live scores -->
        <active-match-list v-else :active-live-scores="activeLiveScores"></active-match-list>
    </div>
</div>

<script>
    window.__LIVE_DATA__ = {
        idMatch: <?php echo json_encode($id_match); ?>,
        isScorer: <?php echo json_encode($isScorer); ?>,
        canScore: <?php echo json_encode($canScore); ?>,
        match: <?php echo json_encode($match); ?>,
        liveScoreData: <?php echo json_encode($liveScoreData); ?>,
        error: <?php echo json_encode($error); ?>
    };
</script>
<script src="/live.js" type="module"></script>
</BODY>
</HTML>
