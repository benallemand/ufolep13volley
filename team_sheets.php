<?php
require_once __DIR__ . '/classes/Generic.php';
require_once __DIR__ . '/classes/MatchMgr.php';
try {
    $generic = new Generic();
    $user_details = $generic->getCurrentUserDetails();
    if (!in_array($user_details['profile_name'], array('RESPONSABLE_EQUIPE', 'ADMINISTRATEUR'))) {
        throw new Exception("Profil responsable d'équipe ou administrateur nécessaire !", 401);
    }
} catch (Exception $e) {
    header('Location: /new_site/#/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&reason=' . $e->getMessage());
    exit(0);
}
try {
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        throw new Exception("id_match non défini !");
    }
    $manager = new MatchMgr();
    if (!$manager->is_match_update_allowed($id_match)) {
        throw new Exception("Vous n'êtes pas autorisé à modifier ce match !");
    }
} catch (Exception $e) {
    echo "Erreur ! " . $e->getMessage();
}
@session_start();
$user_details = $_SESSION;
?>
<!DOCTYPE html>
<HTML data-theme="cupcake" lang="fr">
<HEAD>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Joueurs</title>
    <!--TOASTER-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!--VUE-->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <!--AXIOS-->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- TAILWIND-->
    <script src="https://cdn.tailwindcss.com"></script>
    <!--DAISYUI-->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet" type="text/css"/>
    <!--    FONT AWESOME-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
          integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <script type="text/javascript">
        var id_match = <?php echo $id_match; ?>;
        var user_details = <?php echo json_encode($user_details); ?>;
    </script>
</HEAD>
<BODY>
<div id="app" class="max-w-3xl mx-auto p-6">
    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
        <div class="loading loading-spinner loading-lg">Loading...</div>
    </div>
    <ul class="menu menu-horizontal bg-base-200 rounded-box">
        <li>
            <a href="javascript:history.back()">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </li>
        <li>
            <a href="/">
                <i class="fa-solid fa-house"></i>
            </a>
        </li>
        <li>
            <a href="/match.php?id_match=<?php echo $id_match; ?>">
                <i class="fa-solid fa-volleyball"></i>
            </a>
        </li>
        <li>
            <a href="/survey.php?id_match=<?php echo $id_match; ?>">
                <i class="fa-solid fa-square-poll-vertical"></i>
            </a>
        </li>
    </ul>
    <div class="flex justify-center items-center m-4">
        <img src="/images/ufolep-logo-cmjn-BOUCHES-DU.jpg" class="rounded-lg border-4 border-gray-300 shadow-lg"
             alt=""/>
    </div>
    <div class="flex justify-center items-center m-4">
        <form @submit.prevent="submitForm">
            <div class="flex flex-col">
                <div class="border border-2 border-black">
                    <h1>Joueurs disponibles</h1>
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{matchData.equipe_dom}}
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <select v-model="selectedPlayers" multiple class="select flex-1">
                                        <option disabled selected>sélectionnez puis ajoutez</option>
                                        <option v-for="player in availablePlayers"
                                                v-if="player.equipe === matchData.equipe_dom"
                                                :key="player.id"
                                                :value="player">
                                            {{ player.prenom }} {{ player.nom }}
                                        </option>
                                    </select>
                                    <button @click="addPlayers" type="button" class="btn btn-success ml-auto">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </label>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{matchData.equipe_ext}}
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <select v-model="selectedPlayers" multiple class="select flex-1">
                                        <option disabled selected>sélectionnez puis ajoutez</option>
                                        <option v-for="player in availablePlayers"
                                                v-if="player.equipe === matchData.equipe_ext"
                                                :key="player.id" :value="player">
                                            {{ player.prenom }} {{ player.nom }}
                                        </option>
                                    </select>
                                    <button @click="addPlayers" type="button" class="btn btn-success ml-auto">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
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
                    <ul>
                        <li v-for="player in renforts" :key="player.id" class="flex items-center mb-3">
                            {{ player.prenom }} {{ player.nom }} ({{player.club}})
                            <button @click="addPlayers" type="button" class="btn btn-success ml-auto">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </li>
                    </ul>
                </div>
                <div>
                    <h1>Joueurs présents</h1>
                    <div class="flex flex-col">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{matchData.equipe_dom}}
                                <ul class="list-disc pl-5">
                                    <li v-for="(player, index) in matchPlayers"
                                        v-if="player.equipe == matchData.equipe_dom"
                                        :key="index"
                                        class="flex justify-between items-center mb-2">
                                        <div class="flex items-center">
                                            <img :src="player.path_photo_low" alt="Photo"
                                                 class="w-12 h-12 rounded-full mr-4">
                                            <p>{{ player.prenom }} {{ player.nom }}</p>
                                        </div>
                                        <button @click="removePlayer(player.id)" type="button"
                                                class="btn btn-error ml-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </li>
                                </ul>
                            </label>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{matchData.equipe_ext}}
                                <ul class="list-disc pl-5">
                                    <li v-for="(player, index) in matchPlayers"
                                        v-if="player.equipe == matchData.equipe_ext"
                                        :key="index"
                                        class="flex justify-between items-center mb-2">
                                        <div class="flex items-center">
                                            <img :src="player.path_photo_low" alt="Photo"
                                                 class="w-12 h-12 rounded-full mr-4">
                                            <p>{{ player.prenom }} {{ player.nom }}</p>
                                        </div>
                                        <button @click="removePlayer(player.id)" type="button"
                                                class="btn btn-error ml-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </li>
                                </ul>
                            </label>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Renfort
                                <ul class="list-disc pl-5">
                                    <li v-for="(player, index) in matchPlayers"
                                        v-if="player.equipe !== matchData.equipe_ext && player.equipe !== matchData.equipe_dom"
                                        :key="index"
                                        class="flex justify-between items-center mb-2">
                                        <div class="flex items-center">
                                            <img :src="player.path_photo_low" alt="Photo"
                                                 class="w-12 h-12 rounded-full mr-4">
                                            <p> {{ player.prenom }} {{ player.nom }} ({{player.club}}) </p>
                                        </div>
                                        <button @click="removePlayer(player.id)" type="button"
                                                class="btn btn-error ml-2">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </li>
                                </ul>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-row w-full">
                <button class="btn btn-primary w-2/3" type="submit"><i class="fas fa-pencil mr-1"></i>Enregistrer
                </button>
                <button class="btn btn-secondary w-1/3" type="button" @click="signTeamSheets()"><i
                            class="fas fa-signature mr-1"></i>Signer
                </button>
            </div>
        </form>
    </div>

</div>
<script src="/common_vue.js"></script>
<script src="/team_sheets.js"></script>
</BODY>
</HTML>