<?php

/**
 * Contrôle d'accès des endpoints REST (issues #268 puis #270).
 *
 * REFUS PAR DÉFAUT : `rest/action.php` dispatche n'importe quelle méthode
 * publique des classes routées — soit plus de 400 points d'entrée. Toute action
 * absente de cette liste est refusée (403), qu'elle existe ou non.
 *
 * C'est l'inverse de la première version (#268), qui listait les actions
 * d'administration : construite depuis ce que les frontends appellent, elle
 * laissait ouvertes les ~99 méthodes publiques qu'aucun frontend n'appelle,
 * dont `sqlmanager/execute` — exécution de SQL arbitraire sans authentification.
 *
 * Trois niveaux :
 *   'public' — aucune connexion requise
 *   'user'   — connexion requise ; les contrôles fins (responsable d'équipe,
 *              responsable de club, propriété de l'objet) restent dans les
 *              méthodes elles-mêmes, qui les font déjà
 *   'admin'  — réservé aux administrateurs
 *
 * Règle appliquée : les lectures sont publiques, sauf celles qui exposent des
 * données personnelles ou scopées à la session ; les écritures exigent une
 * connexion ; les actions d'administration exigent le rôle admin.
 *
 * POUR AJOUTER UN ENDPOINT : l'inscrire ici, sinon il répondra 403. Vérifier
 * qu'il n'est pas appelé via une URL construite dynamiquement — par exemple
 * `pages/components/panel/Players.js` fait `/rest/action.php/player/${action}`,
 * ce qu'un grep sur les littéraux ne voit pas.
 *
 * @return array<string, array<string, string>> classe => action => niveau
 */
return array(
    'activity' => array(
        'getActivity' => 'user',
    ),
    'alerts' => array(
        'getAlerts' => 'user',
        'getInfos' => 'user',
    ),
    'bilan' => array(
        'getBilanData' => 'admin',
    ),
    'blacklistcourt' => array(
        'delete' => 'admin',
        'getBlacklistGymnase' => 'admin',
        'getMyClubBlacklistGymnase' => 'user',
        'getMyClubGymnasiums' => 'user',
        'removeBlacklistGymnase' => 'user',
        'saveBlacklistGymnase' => 'user',
    ),
    'blacklistdate' => array(
        'delete' => 'admin',
        'getBlacklistDate' => 'admin',
        'saveBlacklistDate' => 'admin',
    ),
    'blacklistteam' => array(
        'delete' => 'admin',
        'getBlacklistTeam' => 'admin',
        'getMyClubBlacklistTeam' => 'user',
        'removeBlacklistTeam' => 'user',
        'saveBlacklistTeam' => 'user',
    ),
    'blacklistteams' => array(
        'delete' => 'admin',
        'getBlacklistTeams' => 'admin',
        'saveBlacklistTeams' => 'admin',
    ),
    'calendarevents' => array(
        'deleteCalendarEvent' => 'admin',
        'getAllCalendarEvents' => 'admin',
        'saveCalendarEvent' => 'admin',
        'getCalendarEvents' => 'public',
    ),
    'club' => array(
        'deleteClubs' => 'admin',
        'saveClub' => 'admin',
        'get' => 'public',
        'getMyClubTeams' => 'user',
        'getMyClubs' => 'user',
    ),
    'commission' => array(
        'attribution' => 'admin',
        'delete' => 'admin',
        'save_with_args' => 'admin',
        'get' => 'public',
        'getByDivision' => 'public',
    ),
    'competition' => array(
        'delete' => 'admin',
        'delete_blacklist_by_city' => 'admin',
        'delete_friendships' => 'admin',
        'get_blacklist_by_city' => 'admin',
        'get_city' => 'admin',
        'get_friendships' => 'admin',
        'resetCompetition' => 'admin',
        'saveCompetition' => 'admin',
        'save_blacklist_by_city' => 'admin',
        'save_friendships' => 'admin',
        'getCompetitions' => 'public',
    ),
    'configuration' => array(
        'getRegisterSettings' => 'public',
    ),
    'court' => array(
        'delete' => 'admin',
        'saveGymnasium' => 'admin',
        'getGymnasiums' => 'public',
    ),
    'emails' => array(
        'get' => 'admin',
        'insert_email_team_recap' => 'admin',
        'retry_error_emails' => 'admin',
        'get_team_emails' => 'user',
        'mark_all_read' => 'user',
        'set_read_status' => 'user',
    ),
    'halloffame' => array(
        'delete' => 'admin',
        'generateHallOfFameFromMatches' => 'admin',
        'getHallOfFame' => 'admin',
        'saveHallOfFame' => 'admin',
        'download_diploma' => 'public',
        'getHallOfFameDisplay' => 'public',
    ),
    'limitdate' => array(
        'delete' => 'admin',
        'saveLimitDate' => 'admin',
        'getLimitDates' => 'public',
    ),
    'matchmgr' => array(
        'archiveMatch' => 'admin',
        'certify_match' => 'admin',
        'certify_matchs' => 'admin',
        'confirmMatch' => 'admin',
        'delete' => 'admin',
        'delete_match_player' => 'admin',
        'flip_matchs' => 'admin',
        'saveMatch' => 'admin',
        'unconfirmMatch' => 'admin',
        'getLastResults' => 'public',
        'getMatches' => 'public',
        'getMatchesOfTheDay' => 'public',
        'getToScheduleMatches' => 'public',
        'getWeekMatches' => 'public',
        'get_match' => 'public',
        'get_match_by_code_match' => 'public',
        'get_survey' => 'public',
        'getMatchPlayers' => 'user',
        'getMatchReadAccess' => 'user',
        'getMesMatches' => 'user',
        'getMyClubMatches' => 'user',
        'getMyPendingMatchActions' => 'user',
        'getNotMatchPlayers' => 'user',
        'getReinforcementPlayers' => 'user',
        'get_available_dates_for_match' => 'user',
        'manage_match_players' => 'user',
        'modify_match_date' => 'user',
        'save_match' => 'user',
        'save_survey' => 'user',
        'sign_match_sheet' => 'user',
        'sign_team_sheet' => 'user',
    ),
    'news' => array(
        'deleteNews' => 'admin',
        'getAllNews' => 'admin',
        'saveNews' => 'admin',
        'getLastNews' => 'public',
    ),
    'photo' => array(
        'get_photo' => 'public',
    ),
    'player' => array(
        'addPlayersToClub' => 'admin',
        'addPlayersToTeam' => 'admin',
        'delete_players' => 'admin',
        'getPlayers' => 'admin',
        'get_players_by_team' => 'admin',
        'savePlayer' => 'admin',
        'addPlayerToMyTeam' => 'user',
        'createLeaderAccount' => 'user',
        'get' => 'user',
        'getLivePlayersFromTeam' => 'user',
        'getMyPlayers' => 'user',
        'remove_from_team' => 'user',
        'set_captain' => 'user',
        'set_leader' => 'user',
        'set_vice_leader' => 'user',
        'update_from_licence_file' => 'user',
        'update_player' => 'user',
        'uploadPhoto' => 'user',
    ),
    'rank' => array(
        'getRank' => 'public',
        'getRankFFVB' => 'admin',
        'addPenalty' => 'admin',
        'decrementReportCount' => 'admin',
        'incrementReportCount' => 'admin',
        'removePenalty' => 'admin',
        'delete' => 'admin',
        'getRanks' => 'admin',
        'getRanksByCompetitionGroupedByDivision' => 'admin',
        'getUnassignedTeams' => 'admin',
        'removeFromDivision' => 'admin',
        'saveCupPoolAssignments' => 'admin',
        'saveFinalsHostDraw' => 'admin',
        'saveFullFinalsDraw' => 'admin',
        'saveRank' => 'admin',
        'updateRanksBatch' => 'admin',
        'getCupCompetitions' => 'public',
        'getCupDrawData' => 'public',
        'getCupFinalsDraw' => 'public',
        'getCupPoolAssignments' => 'public',
        'getDivisions' => 'public',
        'getDivisionsFromCompetition' => 'public',
        'getFinalsDrawRaw' => 'public',
        'getFinalsDrawResolved' => 'public',
        'getFinalsHostDraw' => 'public',
        'getKHCupDrawData' => 'public',
        'getTeamsForCupDraw' => 'public',
        'sort_cup_rank' => 'public',
    ),
    'register' => array(
        'create_teams_and_accounts' => 'admin',
        'delete' => 'admin',
        'fill_ranks' => 'admin',
        'get_register' => 'admin',
        'set_up_season' => 'admin',
        'unvalidateRegistration' => 'admin',
        'validateRegistration' => 'admin',
        'deleteMyClubRegistration' => 'user',
        'getMyClubRegistrations' => 'user',
        'register' => 'user',
    ),
    'registry' => array(
        'delete' => 'admin',
        'get' => 'admin',
        'save_with_args' => 'admin',
    ),
    'team' => array(
        'delete' => 'admin',
        'getRankTeams' => 'admin',
        'getTeams' => 'admin',
        'download_calendar' => 'public',
        'getActiveTeams' => 'public',
        'getTeam' => 'public',
        'getWebSites' => 'public',
        'getMyTeam' => 'user',
        'load_register_for_my_club' => 'user',
        'saveTeam' => 'user',
    ),
    'timeslot' => array(
        'delete' => 'admin',
        'getTimeSlots' => 'admin',
        'getWeekSchedule' => 'admin',
        'removeTimeSlot' => 'user',
        'saveTimeSlot' => 'user',
        'get_my_timeslots' => 'public',
    ),
    'usermanager' => array(
        'deleteUsers' => 'admin',
        'getUserClubIds' => 'admin',
        'getUserTeamIds' => 'admin',
        'getUsers' => 'admin',
        'get_users_for_act_as' => 'admin',
        'reset_password' => 'admin',
        'saveUser' => 'admin',
        'setAdmin' => 'admin',
        'switch_to_user' => 'admin',
        'updateUserClubs' => 'admin',
        'updateUserTeams' => 'admin',
        'getCurrentUserDetails' => 'public',
        'logout' => 'public',
        'modifierMonMotDePasse' => 'public',
        'request_reset_password' => 'public',
        'reset_my_password' => 'public',
        'attachClubTeamLeader' => 'user',
        'detachClubTeamLeader' => 'user',
        'getMyClubTeamLeaders' => 'user',
        'getMyManageableTeams' => 'user',
        'getMyPreferences' => 'user',
        'saveMyPreferences' => 'user',
        'switchCurrentUserClub' => 'user',
        'switchCurrentUserTeam' => 'user',
        'switch_back_to_admin' => 'user',
        'switch_to_club_team_leader' => 'user',
    ),
);
