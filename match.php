<?php
require_once __DIR__ . '/classes/Generic.php';
require_once __DIR__ . '/classes/MatchMgr.php';
try {
    $generic = new Generic();
    $user_details = $generic->getCurrentUserDetails();
    if (!in_array($user_details['profile_name'], array('RESPONSABLE_EQUIPE', 'ADMINISTRATEUR', 'SUPPORT'))) {
        throw new Exception("Vous n'avez pas le profil suffisant pour accéder à cette page !", 401);
    }
    $manager = new MatchMgr();
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        $code_match = filter_input(INPUT_GET, 'code_match');
        if (empty($code_match)) {
            throw new Exception("id_match non défini !", 404);
        }
        $match = $manager->get_match_by_code_match($code_match);
        $id_match = $match['id_match'];
        header("Location:/match.php?id_match=$id_match");
        die();
    }
    if (!$manager->is_match_read_allowed($id_match)) {
        throw new Exception("Vous n'êtes pas autorisé à modifier ce match !", 401);
    }
} catch (Exception $e) {
    header('Location: /pages/home.html#/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '&reason=' . $e->getMessage());
    exit(0);
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
</HEAD>
<BODY>
<div id="app" class="max-w-3xl mx-auto p-6">
    <div v-if="isLoading" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
        <div class="loading loading-spinner loading-lg">Loading...</div>
    </div>
    <?php include __DIR__ . '/menu.php'; ?>
    <?php include __DIR__ . '/summary.php'; ?>
    <form @submit.prevent="submitForm">
        <div>
            <div class="grid grid-cols-3 gap-4 flex items-center border mb-4">
                <h3 class="text-center text-xl font-bold mb-4">Score</h3>
                <h4 class="text-center text-l font-bold mb-4">{{ matchData.equipe_dom }}</h4>
                <h4 class="text-center text-l font-bold mb-4">{{ matchData.equipe_ext }}</h4>
                <span></span>
                <h4 class="text-center text-l font-bold mb-4">{{ matchData.score_equipe_dom }}</h4>
                <h4 class="text-center text-l font-bold mb-4">{{ matchData.score_equipe_ext }}</h4>
            </div>
            <div class="mb-4 p-4 border">
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
        </div>
        <div class="mb-4 p-4 border">
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
        <div class="mb-4 p-4 border">
            <h3 class="text-xl font-bold mb-4">Scan des feuilles (facultatif)</h3>
            <p>Le nombre de scans envoyés ayant considérablement diminué,<br/>
                Le scan des feuilles peut, si besoin, être envoyé directement par email à la commission.
            </p>
        </div>
        <div class="flex justify-center items-center m-4">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-pencil mr-1"></i><span>Enregistrer</span>
            </button>
        </div>
    </form>
    <div class="mb-4 p-4 border" v-if="isLeader">
        <h3 class="text-xl font-bold mb-4">Demande de report</h3>
        <div class="flex flex-wrap gap-2">
            <button
                v-if="canAskReport()"
                @click="postReportAction('askForReport')"
                class="btn btn-primary">
                <i class="fas fa-calendar mr-2"></i>Demander un report
            </button>
            <button
                v-if="canAcceptReport()"
                @click="postReportAction('acceptReport')"
                class="btn btn-success">
                <i class="fas fa-calendar mr-2"></i>Accepter le report
            </button>
            <button
                v-if="canRefuseReport()"
                @click="postReportAction('refuseReport')"
                class="btn btn-error">
                <i class="fas fa-calendar mr-2"></i>Refuser le report
            </button>
            <button
                v-if="canGiveReportDate()"
                @click="postReportAction('giveReportDate')"
                class="btn btn-success">
                <i class="fas fa-calendar mr-2"></i>Donner une date de report
            </button>
        </div>
    </div>
    <div class="mb-4 p-4 border" v-if="canModifyDate">
        <h3 class="text-xl font-bold mb-4">Modification de date</h3>
        <p class="text-sm mb-4">Ce match n'est pas encore confirmé. Vous pouvez proposer une nouvelle date.</p>
        <button class="btn btn-primary" @click="openDateModificationModal()">
            <i class="fas fa-calendar-alt mr-2"></i>Modifier la date
        </button>

        <dialog id="dateModificationModal" class="modal">
            <div class="modal-box w-11/12 max-w-3xl">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">Choisir une nouvelle date</h3>

                <div class="flex flex-wrap gap-2 mb-4">
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" class="checkbox checkbox-sm" v-model="dateModification.invertReception"/>
                        <span class="label-text">Inverser la réception</span>
                    </label>
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" class="checkbox checkbox-sm" v-model="dateModification.forceDate"/>
                        <span class="label-text">Forcer la date</span>
                    </label>
                </div>

                <div v-if="dateModification.forceDate" class="mb-4">
                    <label class="block text-sm font-medium mb-1">Commentaire obligatoire</label>
                    <textarea v-model="dateModification.comment" class="textarea textarea-bordered w-full"
                              placeholder="Raison du forçage de date..." rows="2"></textarea>
                </div>

                <div v-if="dateModification.loading" class="flex justify-center p-8">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>

                <div v-else>
                    <div class="flex gap-2 mb-2 text-xs flex-wrap">
                        <span class="badge badge-success badge-sm">Disponible</span>
                        <span class="badge badge-warning badge-sm">Conflit semaine</span>
                        <span class="badge badge-error badge-sm">Indisponible</span>
                        <span v-if="dateModification.forceDate" class="badge badge-info badge-sm">Vacances/Férié</span>
                    </div>
                    <div class="overflow-y-auto max-h-80">
                        <table class="table table-xs table-zebra w-full">
                            <thead class="sticky top-0 bg-base-200">
                                <tr>
                                    <th>Date</th>
                                    <th>Équipes</th>
                                    <th>Gymnase</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in dateModification.dates" :key="d.date"
                                    :class="getDateRowClass(d)">
                                    <td class="font-mono">{{ d.date }}</td>
                                    <td>
                                        <i v-if="d.teams_available" class="fas fa-check text-success"></i>
                                        <i v-else class="fas fa-times text-error"></i>
                                    </td>
                                    <td>
                                        <i v-if="d.gymnasium_available" class="fas fa-check text-success"></i>
                                        <i v-else class="fas fa-times text-error"></i>
                                    </td>
                                    <td>
                                        <button v-if="d.available || dateModification.forceDate"
                                                class="btn btn-xs btn-primary"
                                                @click="selectDate(d)">
                                            Choisir
                                        </button>
                                        <span v-if="hasWeekConflicts(d)" class="badge badge-warning badge-xs ml-1"
                                              :title="getWeekConflictText(d)">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>

        <dialog id="confirmDateModal" class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">Confirmer la modification</h3>
                <p>Nouvelle date : <strong>{{ dateModification.selectedDate }}</strong></p>
                <p v-if="dateModification.invertReception" class="text-warning font-semibold mt-2">
                    <i class="fas fa-exchange-alt mr-1"></i>La réception sera inversée
                </p>
                <p v-if="dateModification.comment" class="mt-2">
                    Commentaire : <em>{{ dateModification.comment }}</em>
                </p>
                <div class="modal-action">
                    <form method="dialog" class="flex gap-2">
                        <button class="btn">Annuler</button>
                        <button class="btn btn-primary" @click="confirmDateModification()">Confirmer</button>
                    </form>
                </div>
            </div>
        </dialog>
    </div>
</div>
<script src="/match.js" type="module"></script>
</BODY>
</HTML>