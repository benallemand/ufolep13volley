<?php
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchManager.php';
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function manage_match_players($parameters)
{
    is_action_allowed(__FUNCTION__, $parameters);
    if (!isset($parameters['id_match'])) {
        throw new Exception("Cannot find id_match !");
    }
    if (!isset($parameters['player_ids'])) {
        throw new Exception("Cannot find player_ids !");
    }
    if (empty($parameters['id_match'])) {
        throw new Exception("id_match is empty !");
    }
    $sql_manager = new SqlManager();
    foreach ($parameters['player_ids'] as $index => $player_id) {
        if (empty($player_id)) {
            unset($parameters['player_ids'][$index]);
            continue;
        }
        $sql = "INSERT INTO match_player(id_match, id_player) 
                VALUE (?, ?) 
                ON DUPLICATE KEY UPDATE id_match = id_match, 
                                        id_player = id_player";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $parameters['id_match']
        );
        $bindings[] = array(
            'type' => 'i',
            'value' => $player_id
        );
        $sql_manager->execute($sql, $bindings);
    }
    if(count($parameters['player_ids']) > 0) {
        $match_manager = new MatchManager();
        $match = $match_manager->getMatch($parameters['id_match']);
        $comment = "Les présents ont été renseignés pour le match " . $match['code_match'];
        addActivity($comment);
    }
}

/**
 * @throws Exception
 */
function is_action_allowed(string $function_name, $parameters)
{
    switch ($function_name) {
        case 'manage_match_players':
            $match_manager = new MatchManager();
            $match = $match_manager->getMatch($parameters['id_match']);
            @session_start();
            // allow admin
            if ($_SESSION['profile_name'] === 'ADMINISTRATEUR') {
                return;
            }
            // allow only playing teams
            if (!in_array($_SESSION['id_equipe'], array($match['id_equipe_dom'], $match['id_equipe_ext']))) {
                throw new Exception("Seules les équipes ayant participé au match peuvent dire qui était là !");
            }
            // allow only RESPONSABLE_EQUIPE
            if ($_SESSION['profile_name'] !== 'RESPONSABLE_EQUIPE') {
                throw new Exception("Seuls les responables d'équipes peuvent dire qui était là !");
            }
            // allow only RESPONSABLE_EQUIPE
            if (intval($match['sheet_received']) > 0) {
                throw new Exception("La feuille de match a déjà été envoyée, il n'est plus possible de renseigner les présents !");
            }
            break;
        default:
            break;
    }
}