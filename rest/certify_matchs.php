<?php
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchManager.php';
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function certify_matchs($parameters)
{
    is_action_allowed(__FUNCTION__, $parameters);
    if (!isset($parameters['ids'])) {
        throw new Exception("Cannot find ids !");
    }
    if (empty($parameters['ids'])) {
        throw new Exception("ids is empty !");
    }
    $ids = explode(',', $parameters['ids']);
    if (empty($ids)) {
        throw new Exception("ids is empty !");
    }
    $sql_manager = new SqlManager();
    $match_manager = new MatchManager();
    foreach ($ids as $id) {
        if (empty($id)) {
            continue;
        }
        $sql = "UPDATE matches 
                SET certif = 1
                WHERE id_match = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $sql_manager->execute($sql, $bindings);
        $match = $match_manager->get_match($id);
        addActivity("Le match " . $match['code_match'] . " a ete certifie");
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
            $match = $match_manager->get_match($parameters['id_match']);
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