<?php

/**
 * Endpoints REST réservés aux administrateurs (issue #268).
 *
 * Le contrôle d'accès de l'admin vivait uniquement dans la garde `isAdmin()` de
 * `admin.php`, donc côté page : les endpoints REST correspondants étaient
 * appelables sans aucune authentification, y compris les suppressions.
 * La garde est désormais posée dans le routeur, sur cette liste explicite.
 *
 * Pourquoi ici plutôt que dans `Generic::save()` / `Generic::delete()` :
 *  - ces deux méthodes servent aussi des parcours légitimes non-admin
 *    (responsable d'équipe, responsable de club) : les garder aveuglément
 *    casserait le site ;
 *  - une garde de routeur ne s'applique qu'aux requêtes HTTP, donc les appels
 *    PHP internes (classes entre elles, `cron/`) ne sont pas affectés ;
 *  - un seul endroit à auditer.
 *
 * Liste établie par recoupement : endpoints référencés par l'admin ExtJS (`js/`)
 * moins ceux référencés par le front public et l'espace responsable (`pages/`,
 * `admin/`, `*.js` racine, `e2e/`), appels dynamiques compris.
 *
 * ATTENTION avant d'ajouter une entrée : `player/set_leader`, `set_captain`,
 * `set_vice_leader` et `remove_from_team` ressemblent à des actions d'admin mais
 * sont appelés par l'espace responsable d'équipe
 * (`pages/components/panel/Players.js`, via une URL construite dynamiquement).
 * Ils ne doivent PAS figurer ici.
 *
 * @return array<string, string[]> classe => actions réservées aux admins
 */
return array(
    'bilan' => array(
        'getBilanData',
    ),
    'blacklistcourt' => array(
        'delete',
        'getBlacklistGymnase',
    ),
    'blacklistdate' => array(
        'delete',
        'getBlacklistDate',
        'saveBlacklistDate',
    ),
    'blacklistteam' => array(
        'delete',
        'getBlacklistTeam',
    ),
    'blacklistteams' => array(
        'delete',
        'getBlacklistTeams',
        'saveBlacklistTeams',
    ),
    'calendarevents' => array(
        'deleteCalendarEvent',
        'getAllCalendarEvents',
        'saveCalendarEvent',
    ),
    'club' => array(
        'deleteClubs',
        'saveClub',
    ),
    'commission' => array(
        'attribution',
        'delete',
        'save_with_args',
    ),
    'competition' => array(
        'delete',
        'delete_blacklist_by_city',
        'delete_friendships',
        'generate_matches_final_phase_cup',
        'get_blacklist_by_city',
        'get_city',
        'get_friendships',
        'resetCompetition',
        'saveCompetition',
        'save_blacklist_by_city',
        'save_friendships',
    ),
    'court' => array(
        'delete',
        'saveGymnasium',
    ),
    'day' => array(
        'delete',
        'generateDays',
        'getDays',
        'save_day',
    ),
    'emails' => array(
        'get',
        'insert_email_team_recap',
        'retry_error_emails',
    ),
    'halloffame' => array(
        'delete',
        'generateHallOfFameFromMatches',
        'getHallOfFame',
        'saveHallOfFame',
    ),
    'limitdate' => array(
        'delete',
        'saveLimitDate',
    ),
    'matchmgr' => array(
        'archiveMatch',
        'certify_matchs',
        'confirmMatch',
        'delete',
        'delete_match_player',
        'flip_matchs',
        'generateAll',
        'generateMatches',
        'saveMatch',
        'unconfirmMatch',
    ),
    'news' => array(
        'deleteNews',
        'getAllNews',
        'saveNews',
    ),
    'player' => array(
        'addPlayersToClub',
        'addPlayersToTeam',
        'delete_players',
        'getPlayers',
        'get_players_by_team',
        'savePlayer',
    ),
    'rank' => array(
        'delete',
        'getRanks',
        'getRanksByCompetitionGroupedByDivision',
        'getUnassignedTeams',
        'removeFromDivision',
        'saveRank',
        'updateRanksBatch',
    ),
    'register' => array(
        'create_teams_and_accounts',
        'delete',
        'fill_ranks',
        'get_register',
    ),
    'registry' => array(
        'delete',
        'get',
        'save_with_args',
    ),
    'team' => array(
        'delete',
        'getRankTeams',
        'getTeams',
    ),
    'timeslot' => array(
        'delete',
        'getTimeSlots',
        'getWeekSchedule',
        'saveTimeSlot',
    ),
    'usermanager' => array(
        'deleteUsers',
        'getUserClubIds',
        'getUserTeamIds',
        'getUsers',
        'get_users_for_act_as',
        'reset_password',
        'saveUser',
        'setAdmin',
        'switch_to_user',
        'updateUserClubs',
        'updateUserTeams',
    ),
);
