<?php
require_once __DIR__ . '/classes/Generic.php';
require_once __DIR__ . '/classes/MatchMgr.php';
try {
    $generic = new Generic();
    $user_details = $generic->getCurrentUserDetails();
    if (!in_array($user_details['profile_name'], array('RESPONSABLE_EQUIPE', 'ADMINISTRATEUR', 'SUPPORT'))) {
        throw new Exception("Vous n'avez pas le profil suffisant pour accéder à cette page !", 401);
    }
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        throw new Exception("id_match non défini !", 404);
    }
    $manager = new MatchMgr();
    if (!$manager->is_match_read_allowed($id_match)) {
        throw new Exception("Vous n'êtes pas autorisé à modifier ce match !", 401);
    }
} catch (Exception $e) {
    header('Location: /pages/home.html#/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&reason=' . $e->getMessage());
    exit(0);
}
@session_start();
$user_details = $_SESSION;
require_once __DIR__ . '/helpers/vite.php';
?>
<!DOCTYPE html>
<HTML data-theme="cupcake" lang="fr">
<HEAD>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Joueurs</title>
    <?= vite_asset('src/css/app.css') ?>
</HEAD>
<BODY>
<div id="app" class="max-w-3xl mx-auto p-6">
    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
        <div class="loading loading-spinner loading-lg">Loading...</div>
    </div>
    <?php include __DIR__ . '/menu.php'; ?>
    <?php include __DIR__ . '/summary.php'; ?>
    <div class="flex justify-center items-center m-4">
        <form @submit.prevent="submitForm">
            <div class="flex flex-col">
                <div v-if="!(matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1)">
                    <h1>Joueurs disponibles</h1>
                    <player-list
                            :players="availablePlayersDom"
                            :team-name="matchData.equipe_dom"
                            :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                            mode="add"
                            @add-player="addPlayer">
                    </player-list>

                    <player-list
                            :players="availablePlayersExt"
                            :team-name="matchData.equipe_ext"
                            :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                            mode="add"
                            @add-player="addPlayer">
                    </player-list>
                    <div class="border border-2 border-black">
                        <label>Renfort
                            <input v-model="query"
                                   @input="search"
                                   type="text"
                                   placeholder="taper pour chercher un joueur"
                                   class="input input-bordered w-full max-w-xs"
                            />
                        </label>
                        <div role="alert" class="alert">
                            1 joueur autorisé par match et par équipe. <br/>
                            Le même renfort ne peut pas être utilisé sur 2 matchs dans la même demi-saison
                        </div>
                        <player-list
                                :players="renforts.filter(player => !matchPlayers.includes(player))"
                                team-name="Renfort"
                                :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                                mode="add"
                                @add-player="addPlayer">
                        </player-list>
                    </div>
                </div>
                <div>
                    <h1>Joueurs présents</h1>
                    <player-list
                            :players="matchPlayers.filter(player => player.equipe === matchData.equipe_dom)"
                            :team-name="matchData.equipe_dom"
                            :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                            mode="remove"
                            @remove-player="removePlayer">
                    </player-list>
                    <player-list
                            :players="matchPlayers.filter(player => player.equipe === matchData.equipe_ext)"
                            :team-name="matchData.equipe_ext"
                            :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                            mode="remove"
                            @remove-player="removePlayer">
                    </player-list>
                    <player-list
                            :players="matchPlayers.filter(player => player.equipe !== matchData.equipe_ext && player.equipe !== matchData.equipe_dom)"
                            team-name="Renfort"
                            :is-signed="matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1"
                            mode="remove"
                            @remove-player="removePlayer">
                    </player-list>
                </div>
            </div>
            <div class="flex justify-center items-center m-4" v-if="!(matchData.is_sign_team_dom === 1 && matchData.is_sign_team_ext === 1)">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-pencil mr-1"></i><span>Enregistrer</span>
                </button>
            </div>
        </form>
    </div>

</div>
<?= vite_asset('team_sheets.js') ?>
</BODY>
</HTML>