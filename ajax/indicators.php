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

$indicators = array();


$indicators[] = new Indicator(
    "Joueurs potentiellement en doublon",
    file_get_contents(__DIR__ . '/../sql/player_duplicates.sql'),
    'alert');
$indicators[] = new Indicator(
    "Transferts suspect de joueurs",
    file_get_contents(__DIR__ . '/../sql/suspect_transfers.sql'),
    'alert');
$indicators[] = new Indicator(
    "Joueurs sans numéro de licence",
    file_get_contents(__DIR__ . '/../sql/no_licence.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes",
    file_get_contents(__DIR__ . '/../sql/teams_in_championship.sql'));
$indicators[] = new Indicator(
    "Joueurs avec équipe mais sans club",
    file_get_contents(__DIR__ . '/../sql/no_club.sql'),
    'alert');
$indicators[] = new Indicator(
    "Joueurs en attente de validation",
    file_get_contents(__DIR__ . '/../sql/not_valid_players.sql'),
    'alert');
$indicators[] = new Indicator(
    "Evènements",
    file_get_contents(__DIR__ . '/../sql/activity.sql'));
$indicators[] = new Indicator(
    "Comptes",
    file_get_contents(__DIR__ . '/../sql/accounts.sql'));
$indicators[] = new Indicator(
    "Matches dupliqués",
    file_get_contents(__DIR__ . '/../sql/match_duplicates.sql'),
    'alert');
$indicators[] = new Indicator(
    "Club non renseigné",
    file_get_contents(__DIR__ . '/../sql/teams_without_club.sql'),
    'alert');
$indicators[] = new Indicator(
    "Licences dupliquées",
    file_get_contents(__DIR__ . '/../sql/licence_duplicates.sql'),
    'alert');
$indicators[] = new Indicator(
    "Retards",
    file_get_contents(__DIR__ . '/../sql/delay_match_report.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes actives sans compte responsable équipe",
    file_get_contents(__DIR__ . '/../sql/missing_team_leader_account.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes actives sans responsable",
    file_get_contents(__DIR__ . '/../sql/no_leader_team.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes actives sans créneau de réception",
    file_get_contents(__DIR__ . '/../sql/no_timeslot_teams.sql'));
$indicators[] = new Indicator(
    "Matches non certifiés dont la date ne correspond pas à un créneau",
    file_get_contents(__DIR__ . '/../sql/matches_without_timeslot.sql'));
$indicators[] = new Indicator(
    "Créneaux avec une contrainte horaire forte",
    file_get_contents(__DIR__ . '/../sql/timeslot_constraints.sql'));
$indicators[] = new Indicator(
    "Emails des responsables par compétition",
    file_get_contents(__DIR__ . '/../sql/emails_by_competition.sql'));
$indicators[] = new Indicator(
    'Nombre de matches par date et par gymnase',
    file_get_contents(__DIR__ . '/../sql/matches_by_gymnasium.sql'));
$indicators[] = new Indicator(
    'Nombre de matches trop élevés par date et par gymnase',
    file_get_contents(__DIR__ . '/../sql/too_many_match_in_gymnasium.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes avec trop d'écart entre réception et déplacement",
    file_get_contents(__DIR__ . '/../sql/equity_home_away.sql'),
    'alert');
$indicators[] = new Indicator(
    "Criticité de génération des matchs",
    file_get_contents(__DIR__ . '/../sql/match_generation_criticity.sql'));
$indicators[] = new Indicator(
    "Emails en erreur",
    file_get_contents(__DIR__ . '/../sql/email_errors.sql'),
    'alert');
$indicators[] = new Indicator(
    "Matchs avec joueurs non homologués",
    file_get_contents(__DIR__ . '/../sql/match_invalid_players.sql'),
    'alert');
$indicators[] = new Indicator(
    "Problèmes dans les dates des matchs",
    file_get_contents(__DIR__ . '/../sql/issues_in_match.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes qui jouent plusieurs matchs la même semaine",
    file_get_contents(__DIR__ . '/../sql/many_match_same_day.sql'),
    'alert');
$indicators[] = new Indicator(
    "Même réception que la fois précédente",
    file_get_contents(__DIR__ . '/../sql/same_reception.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equipes non réengagées",
    file_get_contents(__DIR__ . '/../sql/not_registered_teams.sql'));
$indicators[] = new Indicator(
    "Equipes qui ne s'engageront pas",
    file_get_contents(__DIR__ . '/../sql/will_not_register_teams.sql'));
$indicators[] = new Indicator(
    "Nouvelles équipes",
    file_get_contents(__DIR__ . '/../sql/newly_registered_teams.sql'));
$indicators[] = new Indicator(
    "Proposition d'organisation",
    file_get_contents(__DIR__ . '/../sql/register_setup_ranks.sql'));
$indicators[] = new Indicator(
    "Cotisations non réglées",
    file_get_contents(__DIR__ . '/../sql/register_not_paid.sql'),
    'alert');
$indicators[] = new Indicator(
    "Facture par club",
    file_get_contents(__DIR__ . '/../sql/register_invoices.sql'));
$indicators[] = new Indicator(
    "Equipes incomplètes",
    file_get_contents(__DIR__ . '/../sql/teams_incomplete.sql'),
    'alert');
$indicators[] = new Indicator(
    "Décalage des créneaux d'inscription",
    file_get_contents(__DIR__ . '/../sql/mismatch_register_timeslots.sql'),
    'alert');
$indicators[] = new Indicator(
    "Equilibre Réceptions/Déplacements sur l'année",
    file_get_contents(__DIR__ . '/../sql/overall_equity_home_away.sql'),
    'alert');
$indicators[] = new Indicator(
    "Joueurs requis le même soir",
    file_get_contents(__DIR__ . '/../sql/players_many_match_same_date.sql'),
    'alert');
$indicators[] = new Indicator(
    "Joueurs dans plusieurs équipes",
    file_get_contents(__DIR__ . '/../sql/players_in_many_teams.sql'));
$indicators[] = new Indicator(
    "Nombre de matchs par joueur",
    file_get_contents(__DIR__ . '/../sql/nb_matchs_per_player.sql'));
$indicators[] = new Indicator(
    "Classement du fair play",
    file_get_contents(__DIR__ . '/../sql/fairplay_ranks.sql'));
$indicators[] = new Indicator(
    "Délais non respectés pour transmettre une date de report",
    file_get_contents(__DIR__ . '/../sql/report_match_with_too_long_date_delay.sql'),
    'alert');
$indicators[] = new Indicator(
    "Matchs avec des renforts",
    file_get_contents(__DIR__ . '/../sql/matchs_with_reinforcement.sql'));
$indicators[] = new Indicator(
    "Inscriptions - Terrains vs Equipes",
    file_get_contents(__DIR__ . '/../sql/indicator-teams-vs-courts.sql'));

function info_first($a, $b): int
{
    return ($a->getType() == 'info') ? -1 : 1;
}

usort($indicators, 'info_first');

$results = array();
foreach ($indicators as $indicator) {
    $results[] = $indicator->getResult();
}


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
