<div class="flex justify-center items-center m-4">
    <img src="/images/ufolep-logo-cmjn-BOUCHES-DU.jpg" class="rounded-lg border-4 border-gray-300 shadow-lg"
         alt=""/>
</div>
<div class="flex items-center justify-between mb-4">
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{matchData.libelle_competition}}</span>
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Division {{matchData.division}} Journée {{matchData.numero_journee}}</span>
</div>
<div class="flex items-center justify-center mb-4">
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-2xl font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{matchData.code_match}}</span>
</div>
<div class="flex items-center justify-center mb-4">
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-2xl font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 font-bold">{{ matchData.equipe_dom }} vs
        {{ matchData.equipe_ext }}</span>
</div>
<div class="flex items-center justify-between mb-4">
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{matchData.gymnasium}}</span>
    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{matchData.date_reception}} {{matchData.heure_reception}}</span>
</div>

<div class="border border-4">
    <div class="flex flex-row mb-4">
        <span class="basis-1/3"></span>
        <span class="basis-1/3">{{ matchData.equipe_dom }}</span>
        <span class="basis-1/3">{{ matchData.equipe_ext }}</span>
    </div>
    <div class="flex flex-row mb-4">
        <span class="basis-1/3">Fiche équipes</span>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_sign_team_dom && !matchData.is_match_player_filled"
               :href="'/team_sheets.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-user"></i><span>remplir</span>
                </button>
            </a>
            <button v-if="!matchData.is_sign_team_dom && matchData.is_match_player_filled"
                    class="btn btn-secondary"
                    type="button" @click="signTeamSheets()">
                <i class="fas fa-signature mr-1"></i><span>signer</span>
            </button>
            <input v-if="matchData.is_sign_team_dom" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_sign_team_dom"
                   disabled/>
        </div>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_sign_team_ext && !matchData.is_match_player_filled"
               :href="'/team_sheets.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-user"></i><span>remplir</span>
                </button>
            </a>
            <button v-if="!matchData.is_sign_team_ext && matchData.is_match_player_filled"
                    class="btn btn-secondary"
                    type="button" @click="signTeamSheets()">
                <i class="fas fa-signature mr-1"></i><span>signer</span>
            </button>
            <input v-if="matchData.is_sign_team_ext" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_sign_team_ext"
                   disabled/>
        </div>
    </div>
    <div class="flex flex-row mb-4">
        <span class="basis-1/3">Feuille de match</span>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_sign_match_dom && !matchData.is_match_score_filled"
               :href="'/match.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-volleyball"></i><span>remplir</span>
                </button>
            </a>
            <button v-if="!matchData.is_sign_match_dom && matchData.is_match_score_filled"
                    class="btn btn-secondary"
                    type="button" @click="signMatch()">
                <i class="fas fa-signature mr-1"></i><span>signer</span>
            </button>
            <input v-if="matchData.is_sign_match_dom" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_sign_match_dom"
                   disabled/>
        </div>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_sign_match_ext && !matchData.is_match_score_filled"
               :href="'/match.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-volleyball"></i><span>remplir</span>
                </button>
            </a>
            <button v-if="!matchData.is_sign_match_ext && matchData.is_match_score_filled"
                    class="btn btn-secondary"
                    type="button" @click="signMatch()">
                <i class="fas fa-signature mr-1"></i><span>signer</span>
            </button>
            <input v-if="matchData.is_sign_match_ext" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_sign_match_ext"
                   disabled/>
        </div>
    </div>
    <div class="flex flex-row mb-4">
        <span class="basis-1/3">Fair-play</span>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_survey_filled_dom"
               :href="'/survey.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-square-poll-vertical"></i><span>remplir</span>
                </button>
            </a>
            <input v-if="matchData.is_survey_filled_dom" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_survey_filled_dom"
                   disabled/>
        </div>
        <div class="basis-1/3 flex gap-4 items-center">
            <a v-if="!matchData.is_survey_filled_ext"
               :href="'/survey.php?id_match=' + matchData.id_match">
                <button
                        class="btn btn-primary"
                        type="button">
                    <i class="fa-solid fa-square-poll-vertical"></i><span>remplir</span>
                </button>
            </a>
            <input v-if="matchData.is_survey_filled_ext" class="checkbox checkbox-success checkbox-lg" type="checkbox"
                   v-model="matchData.is_survey_filled_ext"
                   disabled/>
        </div>
    </div>
</div>