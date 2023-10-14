<?php

header('Content-Type: text/html; charset=utf-8');

/**
 * @param $data
 * @param string $delimiter
 * @param string $enclosure
 * @return string
 * @throws Exception
 */
function generateCsv($data, $delimiter = ';', $enclosure = '"')
{
    $contents = '';
    $handle = fopen('php://temp', 'r+');
    $isHeaderWritten = false;
    foreach ($data as $line) {
        $dateYesterday = new DateTime();
        $dateYesterday->sub(new DateInterval('P1D'));
        $dateActivity = DateTime::createFromFormat("d/m/Y", $line['date']);
        if ($dateActivity < $dateYesterday) {
            continue;
        }
        if (!$isHeaderWritten) {
            fputcsv($handle, array_keys($line), $delimiter, $enclosure);
            $isHeaderWritten = true;
        }
        fputcsv($handle, $line, $delimiter, $enclosure);
    }
    rewind($handle);
    while (!feof($handle)) {
        $contents .= fread($handle, 8192);
    }
    fclose($handle);
    return $contents;
}

require_once __DIR__ . '/../classes/Indicator.php';

$indicator_same_reception = new Indicator(
    "Même réception 2 fois d'affilée",
    file_get_contents(__DIR__ . '/../sql/same_reception.sql'));
$indicatorPossibleDuplicatePlayers = new Indicator(
    "Joueurs potentiellement en doublon",
    file_get_contents(__DIR__ . '/../sql/player_duplicates.sql'));
$indicatorSuspectTransfert = new Indicator(
    "Transferts suspect de joueurs",
    file_get_contents(__DIR__ . '/../sql/suspect_transfers.sql'));
$indicatorPlayersWithoutLicence = new Indicator(
    "Joueurs sans numéro de licence",
    file_get_contents(__DIR__ . '/../sql/no_licence.sql'));
$indicatorEquipesEngageesChampionnat = new Indicator(
    "Equipes",
    file_get_contents(__DIR__ . '/../sql/teams_in_championship.sql'));
$indicatorPlayersWithTeamButNoClub = new Indicator(
    "Joueurs avec équipe mais sans club",
    file_get_contents(__DIR__ . '/../sql/no_club.sql'));
$indicatorNotValidatedPlayers = new Indicator(
    "Joueurs en attente de validation",
    file_get_contents(__DIR__ . '/../sql/not_valid_players.sql'));
$indicatorActivity = new Indicator(
    "Evènements",
    file_get_contents(__DIR__ . '/../sql/activity.sql'));
$indicatorComptes = new Indicator(
    "Comptes",
    file_get_contents(__DIR__ . '/../sql/accounts.sql'));
$indicatorMatchesDupliques = new Indicator(
    "Matches dupliqués",
    file_get_contents(__DIR__ . '/../sql/match_duplicates.sql'));
$indicatorEquipesSansClub = new Indicator(
    "Club non renseigné",
    file_get_contents(__DIR__ . '/../sql/teams_without_club.sql'));
$indicatorLicencesDupliquees = new Indicator(
    "Licences dupliquées",
    file_get_contents(__DIR__ . '/../sql/licence_duplicates.sql'));
$indicatorMatchesNonRenseignes = new Indicator(
    "Retards",
    file_get_contents(__DIR__ . '/../sql/delay_match_report.sql'));
$indicatorActiveTeamWithoutTeamManagerAccount = new Indicator(
    "Equipes actives sans compte responsable équipe",
    file_get_contents(__DIR__ . '/../sql/missing_team_leader_account.sql'));
$indicatorActiveTeamWithoutTeamLeader = new Indicator(
    "Equipes actives sans responsable",
    file_get_contents(__DIR__ . '/../sql/no_leader_team.sql'));
$indicatorActiveTeamWithoutTimeslot = new Indicator(
    "Equipes actives sans créneau de réception",
    file_get_contents(__DIR__ . '/../sql/no_timeslot_teams.sql'));
$indicatorPendingMatchesWithWrongTimeSlot = new Indicator(
    "Matches non certifiés dont la date ne correspond pas à un créneau",
    file_get_contents(__DIR__ . '/../sql/matches_without_timeslot.sql'));
$indicatorTimeSlotWithConstraint = new Indicator(
    "Créneaux avec une contrainte horaire forte",
    file_get_contents(__DIR__ . '/../sql/timeslot_constraints.sql'));
$indicatorTeamLeadersByChamp = new Indicator(
    "Emails des responsables par compétition",
    file_get_contents(__DIR__ . '/../sql/emails_by_competition.sql'));
$indicatorMatchesByGymnasiumByDate = new Indicator(
    'Nombre de matches par date et par gymnase',
    file_get_contents(__DIR__ . '/../sql/matches_by_gymnasium.sql'));
$indicatorTooMuchMatchesByGymnasiumByDate = new Indicator(
    'Nombre de matches trop élevés par date et par gymnase',
    file_get_contents(__DIR__ . '/../sql/too_many_match_in_gymnasium.sql'));
$indicatorEquityBetweenHomeAndAway = new Indicator(
    "Equipes avec trop d'écart entre réception et déplacement",
    file_get_contents(__DIR__ . '/../sql/equity_home_away.sql'));
$indicatorMatchGenerationCriticity = new Indicator(
    "Criticité de génération des matchs",
    file_get_contents(__DIR__ . '/../sql/match_generation_criticity.sql'));
$indicatorErrorInEmails = new Indicator(
    "Emails en erreur",
    file_get_contents(__DIR__ . '/../sql/email_errors.sql'));
$indicatorMatchesWithInvalidPlayers = new Indicator(
    "Matchs avec joueurs non homologués",
    file_get_contents(__DIR__ . '/../sql/match_invalid_players.sql'));
$indicatorMatchesWithInvalidDate = new Indicator(
    "Problèmes dans les dates des matchs",
    file_get_contents(__DIR__ . '/../sql/issues_in_match.sql'));
$indicatorTeamManyMatchesSameDay = new Indicator(
    "Equipes qui jouent plusieurs matchs la même semaine",
    file_get_contents(__DIR__ . '/../sql/many_match_same_day.sql'));
$indicator_same_reception = new Indicator(
    "Même réception que la fois précédente",
    file_get_contents(__DIR__ . '/../sql/same_reception.sql'));
$indicator_not_registered_teams = new Indicator(
    "Equipes non réengagées",
    file_get_contents(__DIR__ . '/../sql/not_registered_teams.sql'));
$indicator_will_not_register_teams = new Indicator(
    "Equipes qui ne s'engageront pas",
    file_get_contents(__DIR__ . '/../sql/will_not_register_teams.sql'));
$indicator_newly_registered_teams = new Indicator(
    "Nouvelles équipes",
    file_get_contents(__DIR__ . '/../sql/newly_registered_teams.sql'));

$results = array();
$results[] = $indicatorEquipesEngageesChampionnat->getResult();
$results[] = $indicatorPlayersWithTeamButNoClub->getResult();
$results[] = $indicatorNotValidatedPlayers->getResult();
$results[] = $indicatorActivity->getResult();
$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
$results[] = $indicatorEquipesSansClub->getResult();
$results[] = $indicatorMatchesDupliques->getResult();
$results[] = $indicatorComptes->getResult();
$results[] = $indicatorPlayersWithoutLicence->getResult();
$results[] = $indicatorSuspectTransfert->getResult();
$results[] = $indicatorPossibleDuplicatePlayers->getResult();
$results[] = $indicatorActiveTeamWithoutTeamManagerAccount->getResult();
$results[] = $indicatorPendingMatchesWithWrongTimeSlot->getResult();
$results[] = $indicatorTimeSlotWithConstraint->getResult();
$results[] = $indicatorTeamLeadersByChamp->getResult();
$results[] = $indicatorActiveTeamWithoutTeamLeader->getResult();
$results[] = $indicatorMatchesByGymnasiumByDate->getResult();
$results[] = $indicatorTooMuchMatchesByGymnasiumByDate->getResult();
$results[] = $indicatorActiveTeamWithoutTimeslot->getResult();
$results[] = $indicatorEquityBetweenHomeAndAway->getResult();
$results[] = $indicatorMatchGenerationCriticity->getResult();
$results[] = $indicatorErrorInEmails->getResult();
$results[] = $indicatorMatchesWithInvalidPlayers->getResult();
$results[] = $indicatorMatchesWithInvalidDate->getResult();
$results[] = $indicatorTeamManyMatchesSameDay->getResult();
$results[] = $indicator_same_reception->getResult();
$results[] = $indicator_not_registered_teams->getResult();
$results[] = $indicator_will_not_register_teams->getResult();
$results[] = $indicator_newly_registered_teams->getResult();

$indicatorName = filter_input(INPUT_GET, 'indicator');
if (!$indicatorName) {
    echo json_encode(array('results' => array_filter($results)));
    exit();
}
foreach ($results as $result) {
    if ($result['fieldLabel'] === $indicatorName) {
        try {
            echo generateCsv($result['details']);
        } catch (Exception $e) {
        }
        exit();
    }
}
