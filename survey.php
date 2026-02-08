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
    header('Location: /pages/home.html#/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&reason=' . $e->getMessage());
    exit(0);
}
try {
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        throw new Exception("id_match non défini !");
    }
    $manager = new MatchMgr();
    if (!$manager->is_match_read_allowed($id_match)) {
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
    <title>Sondage</title>
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
    <?php include __DIR__ . '/menu.php'; ?>
    <?php include __DIR__ . '/summary.php'; ?>
    <form @submit.prevent="submitForm">
        <div class="mb-4 p-4 border flex flex-col">
            <div class="rating flex">
                <label class="basis-1/3 cursor-pointer" for="star1-0">Ponctualité</label>
                <div class="basis-2/3">
                    <input type="radio" id="star1-0" v-model="surveyData.on_time" :value="0" class="rating-hidden"/>
                    <input type="radio" v-model="surveyData.on_time" :value="1" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="2" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="3" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="4" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="5" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="6" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="7" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="8" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="9" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.on_time" :value="10" class="mask mask-star"/>
                </div>
            </div>
            <div class="rating flex">
                <label class="basis-1/3 cursor-pointer" for="star2-0">Etat d'esprit</label>
                <div class="basis-2/3">
                    <input type="radio" id="star2-0" v-model="surveyData.spirit" :value="0" class="rating-hidden"/>
                    <input type="radio" v-model="surveyData.spirit" :value="1" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="2" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="3" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="4" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="5" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="6" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="7" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="8" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="9" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.spirit" :value="10" class="mask mask-star"/>
                </div>
            </div>
            <div class="rating flex">
                <label class="basis-1/3 cursor-pointer" for="star3-0">Arbitrage</label>
                <div class="basis-2/3">
                    <input type="radio" id="star3-0" v-model="surveyData.referee" :value="0" class="rating-hidden"/>
                    <input type="radio" v-model="surveyData.referee" :value="1" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="2" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="3" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="4" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="5" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="6" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="7" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="8" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="9" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.referee" :value="10" class="mask mask-star"/>
                </div>
            </div>
            <div class="rating flex">
                <label class="basis-1/3 cursor-pointer" for="star4-0">Apéro</label>
                <div class="basis-2/3">
                    <input type="radio" id="star4-0" v-model="surveyData.catering" :value="0" class="rating-hidden"/>
                    <input type="radio" v-model="surveyData.catering" :value="1" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="2" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="3" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="4" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="5" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="6" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="7" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="8" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="9" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.catering" :value="10" class="mask mask-star"/>
                </div>
            </div>
            <div class="rating flex">
                <label class="basis-1/3 cursor-pointer" for="star5-0">Global</label>
                <div class="basis-2/3">
                    <input type="radio" id="star5-0" v-model="surveyData.global" :value="0" class="rating-hidden"/>
                    <input type="radio" v-model="surveyData.global" :value="1" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="2" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="3" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="4" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="5" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="6" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="7" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="8" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="9" class="mask mask-star"/>
                    <input type="radio" v-model="surveyData.global" :value="10" class="mask mask-star"/>
                </div>
            </div>
        </div>
        <div class="form-group mb-4">
            <label class="block text-lg mb-2">Commentaire
                <textarea v-model="surveyData.comment" class="textarea textarea-bordered w-full"
                          placeholder="Ajouter un commentaire"></textarea></label>
        </div>
        <div class="flex justify-center items-center m-4">
            <button class="btn btn-primary w-2/3" type="submit"><i class="fas fa-pencil mr-1"></i>Enregistrer
            </button>
        </div>
    </form>
</div>
<script src="/survey.js" type="module"></script>
</BODY>
</HTML>