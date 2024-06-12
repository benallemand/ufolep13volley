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
    $manager = new MatchMgr();
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        $code_match = filter_input(INPUT_GET, 'code_match');
        if (empty($code_match)) {
            throw new Exception("id_match non défini !");
        }
        $match = $manager->get_match_by_code_match($code_match);
        $id_match = $match['id_match'];
    }
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

    <TITLE>Feuille de match</TITLE>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <h2 class="text-2xl font-bold mb-4">{{ matchData.equipe_dom }} vs {{ matchData.equipe_ext }}</h2>
    <form @submit.prevent="submitForm">
        <div class="form-group mb-4">
            <h3 class="text-xl font-bold mb-4">Signatures</h3>
            <h4 class="text-l font-bold mb-4">Fiche équipes</h4>
            <div class="grid grid-cols-4 gap-4 items-center">
                <label class="col-span-1 text-lg">{{ matchData.equipe_dom }}</label>
                <input class="checkbox" type="checkbox" v-model="matchData.is_sign_team_dom" disabled/>
                <label class="col-span-1 text-lg">{{ matchData.equipe_ext }}</label>
                <input class="checkbox" type="checkbox" v-model="matchData.is_sign_team_ext" disabled/>
            </div>
            <h4 class="text-l font-bold mb-4">Feuille de match</h4>
            <div class="grid grid-cols-4 gap-4 items-center">
                <label class="col-span-1 text-lg">{{ matchData.equipe_dom }}</label>
                <input class="checkbox" type="checkbox" v-model="matchData.is_sign_match_dom" disabled/>
                <label class="col-span-1 text-lg">{{ matchData.equipe_ext }}</label>
                <input class="checkbox" type="checkbox" v-model="matchData.is_sign_match_ext" disabled/>
            </div>
        </div>
        <div>
            <h3 class="text-xl font-bold mb-4">Score</h3>
            <div class="grid grid-cols-3 gap-4 flex items-center">
                <span></span>
                <h4 class="text-l font-bold mb-4">{{ matchData.equipe_dom }}</h4>
                <h4 class="text-l font-bold mb-4">{{ matchData.equipe_ext }}</h4>
            </div>
            <div class="form-group mb-4" v-for="set in [1, 2, 3, 4, 5]" :key="set">
                <div class="grid grid-cols-3 gap-4 flex items-center">
                    <label class="input flex items-center gap-3 text-lg">Set {{ set }}</label>
                    <input class="input input-bordered" type="number" min="0" max="50"
                           v-model.number="matchData['set_' + set + '_dom']"/>
                    <input class="input input-bordered" type="number" min="0" max="50"
                           v-model.number="matchData['set_' + set + '_ext']"/>
                </div>
            </div>
        </div>
        <div>
            <h3 class="text-xl font-bold mb-4">Arbitrage</h3>
            <select v-model="matchData.referee" class="select w-full max-w-xs">
                <option value="HOME" :selected="matchData.referee === 'HOME'">équipe à domicile</option>
                <option value="AWAY" :selected="matchData.referee === 'AWAY'">équipe à l'extérieur</option>
                <option value="BOTH" :selected="matchData.referee === 'BOTH'">les deux / auto-arbitrage</option>
            </select>
        </div>
        <div class="form-group mb-4">
            <label class="block text-lg mb-2">Commentaire</label>
            <textarea v-model="matchData.note" class="textarea textarea-bordered w-full"
                      placeholder="Ajouter un commentaire"></textarea>
        </div>
        <div class="flex flex-row w-full">
            <button class="btn btn-primary w-2/3" type="submit"><i class="fas fa-pencil mr-2"></i>Modifier le score
            </button>
            <button class="btn btn-secondary w-1/3" type="button" @click="signMatch()"><i class="fas fa-signature mr-2"></i>Signer
            </button>
        </div>
    </form>
</div>

<script>
    new Vue({
        el: '#app',
        data: {
            matchData: {}
        },
        mounted() {
            this.loadMatchData();
        },
        methods: {
            loadMatchData() {
                axios.get(`/rest/action.php/matchmgr/get_match?id_match=${id_match}`) // Remplacez par l'URL de votre API
                    .then(
                        response => {
                            this.matchData = response.data
                        }
                    )
                    .catch(error => {
                        console.error('Erreur lors du chargement des données:', error);
                    });
            },
            signMatch() {
                const message = "Je confirme avoir pris connaissance du score saisi sur le site." +
                    "\nEn signant numériquement la feuille de match, il n'est plus nécessaire de fournir de feuille de match au format papier." +
                    "\nMerci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.";
                if (window.confirm(message)) {
                    const formData = new FormData();
                    formData.append('id_match', id_match);
                    axios.post('/rest/action.php/matchmgr/sign_match_sheet', formData)
                        .then(
                            response => {
                                const msg_result = "Signature prise en compte, merci de recharger la page."
                                Toastify({
                                    text: msg_result,
                                    duration: 3000,
                                    close: true,
                                    gravity: "bottom",
                                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Couleur de fond du toast
                                }).showToast();
                                console.log(response)
                            }
                        )
                        .catch(error => {
                            Toastify({
                                text: error.response.data.message,
                                duration: 3000,
                                close: true,
                                gravity: "bottom",
                                backgroundColor: "linear-gradient(to right, #ff0000, #ff6666)",
                            }).showToast();
                            console.log(error)
                        });
                }
            },
            submitForm() {
                const formData = new FormData();
                for (const key in this.matchData) {
                    switch (key) {
                        case 'id_match':
                        case 'code_match':
                        case 'score_equipe_dom':
                        case 'set_1_dom':
                        case 'set_2_dom':
                        case 'set_3_dom':
                        case 'set_4_dom':
                        case 'set_5_dom':
                        case 'score_equipe_ext':
                        case 'set_1_ext':
                        case 'set_2_ext':
                        case 'set_3_ext':
                        case 'set_4_ext':
                        case 'set_5_ext':
                        case 'referee':
                        case 'note':
                        case 'forfait_dom':
                        case 'forfait_ext':
                        case 'dirtyFields':
                            formData.append(key, this.matchData[key]);
                            break;
                        default:
                            break;
                    }
                }
                axios.post('/rest/action.php/matchmgr/save_match', formData)
                    .then(
                        response => {
                            Toastify({
                                text: response.data.message, // Supposons que la réponse contient un champ 'message'
                                duration: 3000, // Durée du toast en millisecondes
                                close: true, // Permet de fermer le toast
                                gravity: "bottom", // Position du toast
                                backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)", // Couleur de fond du toast
                            }).showToast();
                            console.log(response)
                        }
                    )
                    .catch(error => {
                        Toastify({
                            text: error.response.data.message,
                            duration: 3000,
                            close: true,
                            gravity: "bottom",
                            backgroundColor: "linear-gradient(to right, #ff0000, #ff6666)",
                        }).showToast();
                        console.log(error)
                    });
            }
        }
    });
</script>
</BODY>
</HTML>