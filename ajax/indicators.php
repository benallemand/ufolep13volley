<?php
// Parametres du cookie de session (HttpOnly, Secure, SameSite) : doit
// passer AVANT le premier session_start(), d'ou cette place en tete de
// point d'entree (issue #292).
require_once __DIR__ . '/../bootstrap.php';

/**
 * Indicateurs d'administration (tableau de bord).
 *
 * RESERVE AUX ADMINISTRATEURS. Ce fichier execute 44 requetes d'exploitation et
 * renvoie leurs lignes de detail : adresses des responsables, comptes, identite
 * de joueurs, cotisations. Il etait ouvert a tout le monde (issue #284) --
 * `ajax/` ne passe pas par `rest/action.php`, donc le refus par defaut de #270
 * ne le protegeait pas.
 *
 * Deux modes, appeles par `admin/components/screens/Indicators.js` :
 * `mode=list` rend les libelles et les sections, `mode=detail&id=N` execute la
 * requete d'un seul indicateur.
 */
require_once __DIR__ . '/../classes/UserManager.php';

@session_start();
if (!UserManager::isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => false,
        'message' => "Action reservee aux administrateurs !",
    ));
    exit();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Indicator.php';

/** Requête d'un indicateur, rangée dans `sql/`. */
function indicator_sql(string $file): string
{
    return file_get_contents(__DIR__ . '/../sql/' . $file);
}

// Chaque indicateur déclare sa section du tableau de bord (issue #396). Ceux
// qui déclarent un écran cible et une colonne d'identifiant rendent leur tuile
// ACTIONNABLE (issue #312) : le tableau de bord y ajoute un bouton qui ouvre
// l'écran filtré sur ces seules lignes. Les autres restent de simples constats
// — la plupart croisent plusieurs entités, il n'y a pas d'écran évident où les
// corriger.
//
// Retirés par #396 : « Proposition d'organisation » (remplacé par la division X
// et l'écran Divisions, #388), « Comptes » et « Evènements » (les écrans
// Utilisateurs et Activité le font mieux), « Nouvelles équipes » (compté sur la
// page d'accueil avec la bonne définition, #379), « Nombre de matches par date
// et par gymnase » (comptait en double ; la version alerte lit le gymnase du
// match).
$indicators = array(

    // --- Inscriptions -------------------------------------------------------

    // Maille club, alors que « Equipes non réengagées » est à la maille équipe :
    // un club qui n'a rien inscrit du tout n'a pas commencé sa saisie. La tuile
    // s'éteint d'elle-même passée la date limite d'inscription (issue #338).
    new Indicator("Clubs sans aucune inscription",
        indicator_sql('clubs_without_registration.sql'), 'alert', 'clubs', 'indicator_id',
        category: Indicator::INSCRIPTIONS),
    new Indicator("Equipes non réengagées",
        indicator_sql('not_registered_teams.sql'),
        category: Indicator::INSCRIPTIONS),
    new Indicator("Equipes qui ne s'engageront pas",
        indicator_sql('will_not_register_teams.sql'),
        category: Indicator::INSCRIPTIONS),
    // Alerte : une inscription sans créneau complet bloque le calendrier (#395).
    new Indicator("Inscriptions - Infos incomplètes",
        indicator_sql('indicator-register-incomplete-teams.sql'), 'alert',
        category: Indicator::INSCRIPTIONS),
    // Alerte : seuls les clubs qui inscrivent plus d'équipes que leurs terrains
    // n'en reçoivent (demandes refusées exclues).
    new Indicator("Inscriptions - Terrains vs Equipes",
        indicator_sql('indicator-teams-vs-courts.sql'), 'alert',
        category: Indicator::INSCRIPTIONS),
    new Indicator("Cotisations non réglées",
        indicator_sql('register_not_paid.sql'), 'alert',
        category: Indicator::INSCRIPTIONS),
    new Indicator("Facture par club",
        indicator_sql('register_invoices.sql'),
        category: Indicator::INSCRIPTIONS),

    // --- Préparation du calendrier ------------------------------------------

    new Indicator("Décalage des créneaux d'inscription",
        indicator_sql('mismatch_register_timeslots.sql'), 'alert',
        category: Indicator::CALENDAR),
    new Indicator("Equipes actives sans créneau de réception",
        indicator_sql('no_timeslot_teams.sql'),
        category: Indicator::CALENDAR),
    new Indicator("Créneaux avec une contrainte horaire forte",
        indicator_sql('timeslot_constraints.sql'),
        category: Indicator::CALENDAR),
    new Indicator("Criticité de génération des matchs",
        indicator_sql('match_generation_criticity.sql'),
        category: Indicator::CALENDAR),

    // --- Équipes et clubs ---------------------------------------------------

    new Indicator("Equipes",
        indicator_sql('teams_in_championship.sql'),
        category: Indicator::TEAMS),
    new Indicator("Equipes incomplètes",
        indicator_sql('teams_incomplete.sql'), 'alert',
        category: Indicator::TEAMS),
    new Indicator("Equipes actives sans responsable",
        indicator_sql('no_leader_team.sql'), 'alert', 'teams', 'indicator_id',
        category: Indicator::TEAMS),
    new Indicator("Equipes actives sans compte responsable équipe",
        indicator_sql('missing_team_leader_account.sql'), 'alert',
        category: Indicator::TEAMS),
    new Indicator("Clubs engagés sans compte de club",
        indicator_sql('clubs_without_account.sql'), 'alert', 'clubs', 'indicator_id',
        category: Indicator::TEAMS),
    new Indicator("Club non renseigné",
        indicator_sql('teams_without_club.sql'), 'alert', 'teams', 'indicator_id',
        category: Indicator::TEAMS),
    new Indicator("Emails des responsables par compétition",
        indicator_sql('emails_by_competition.sql'),
        category: Indicator::TEAMS),

    // --- Joueurs ------------------------------------------------------------

    new Indicator("Joueurs sans numéro de licence",
        indicator_sql('no_licence.sql'), 'alert', 'players', 'indicator_id',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs en attente de validation",
        indicator_sql('not_valid_players.sql'), 'alert', 'players', 'indicator_id',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs avec équipe mais sans club",
        indicator_sql('no_club.sql'), 'alert', 'players', 'indicator_id',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs sans photo",
        indicator_sql('no_photo.sql'), 'alert',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs potentiellement en doublon",
        indicator_sql('player_duplicates.sql'), 'alert',
        category: Indicator::PLAYERS),
    new Indicator("Licences dupliquées",
        indicator_sql('licence_duplicates.sql'), 'alert',
        category: Indicator::PLAYERS),
    // L'indicateur « Joueurs inscrits hors délai en Coupe Khoury Hanna » (#233) a
    // été retiré avec l'issue #32 : il détectait a posteriori, par recoupement de
    // chaînes du journal d'activité, ce que le verrouillage de l'effectif empêche
    // désormais à la source. Le seul ajout tardif encore possible est la
    // dérogation d'un administrateur, journalisée explicitement (« Ajout
    // DEROGATOIRE de … ») et donc consultable depuis l'écran Activité.
    new Indicator("Transferts suspect de joueurs",
        indicator_sql('suspect_transfers.sql'), 'alert',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs requis le même soir",
        indicator_sql('players_many_match_same_date.sql'), 'alert',
        category: Indicator::PLAYERS),
    new Indicator("Joueurs dans plusieurs équipes",
        indicator_sql('players_in_many_teams.sql'),
        category: Indicator::PLAYERS),

    // --- Saison en cours ----------------------------------------------------

    new Indicator("Retards",
        indicator_sql('delay_match_report.sql'), 'alert',
        category: Indicator::SEASON),
    // Pénalités automatiques de la feuille de match non signée à 48 h (#345)
    new Indicator("Pénalités automatiques",
        indicator_sql('auto_penalties.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Délais non respectés pour transmettre une date de report",
        indicator_sql('report_match_with_too_long_date_delay.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Matchs avec joueurs non homologués",
        indicator_sql('match_invalid_players.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Matches dupliqués",
        indicator_sql('match_duplicates.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Problèmes dans les dates des matchs",
        indicator_sql('issues_in_match.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Equipes qui jouent plusieurs matchs la même semaine",
        indicator_sql('many_match_same_day.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator('Nombre de matches trop élevés par date et par gymnase',
        indicator_sql('too_many_match_in_gymnasium.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Matches non certifiés dont la date ne correspond pas à un créneau",
        indicator_sql('matches_without_timeslot.sql'),
        category: Indicator::SEASON),
    new Indicator("Même réception que la fois précédente",
        indicator_sql('same_reception.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Equipes avec trop d'écart entre réception et déplacement",
        indicator_sql('equity_home_away.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Equilibre Réceptions/Déplacements sur l'année",
        indicator_sql('overall_equity_home_away.sql'), 'alert',
        category: Indicator::SEASON),
    new Indicator("Emails en erreur",
        indicator_sql('email_errors.sql'), 'alert',
        category: Indicator::SEASON),

    // --- Statistiques -------------------------------------------------------

    new Indicator("Nombre de matchs par joueur",
        indicator_sql('nb_matchs_per_player.sql'),
        category: Indicator::STATISTICS),
    new Indicator("Matchs avec des renforts",
        indicator_sql('matchs_with_reinforcement.sql'),
        category: Indicator::STATISTICS),
    new Indicator("Classement du fair play",
        indicator_sql('fairplay_ranks.sql'),
        category: Indicator::STATISTICS),
    new Indicator("Distance parcourue",
        indicator_sql('equity_distance.sql'),
        category: Indicator::STATISTICS),
);

$mode = filter_input(INPUT_GET, 'mode');
$indicatorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($mode === 'list') {
    $list = array();
    foreach ($indicators as $index => $indicator) {
        $list[] = array(
            'id' => $index,
            'fieldLabel' => $indicator->getFieldLabel(),
            'type' => $indicator->getType(),
            'category' => $indicator->getCategory(),
        );
    }
    $categories = array();
    foreach (Indicator::CATEGORIES as $key => $label) {
        $categories[] = array('key' => $key, 'label' => $label);
    }
    echo json_encode(array('results' => $list, 'categories' => $categories));
    exit();
}

if ($mode === 'detail' && $indicatorId !== null && $indicatorId !== false) {
    if (isset($indicators[$indicatorId])) {
        echo json_encode($indicators[$indicatorId]->getResult());
    } else {
        echo json_encode(array('error' => 'Indicator not found'));
    }
    exit();
}

// L'ancien mode « tout calculer d'un coup », et l'export CSV de l'indicateur
// « Evènements » qui s'y greffait, n'avaient plus aucun appelant (#396).
http_response_code(400);
echo json_encode(array('success' => false, 'message' => "Mode attendu : list ou detail"));
