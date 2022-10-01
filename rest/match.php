<?php
require_once __DIR__ . '/../classes/MatchManager.php';
require_once __DIR__ . '/../includes/fonctions_inc.php';
/**
 * @throws Exception
 */
function match($parameters)
{
    is_action_allowed(__FUNCTION__, $parameters);
    if (!isset($parameters['id_match'])) {
        throw new Exception("Cannot find id_match !");
    }
    if (empty($parameters['id_match'])) {
        throw new Exception("id_match is empty !");
    }
    $manager = new MatchManager();
    return $manager->get_match($parameters['id_match']);
}

/**
 * @throws Exception
 */
function is_action_allowed(string $function_name, $parameters)
{
    switch ($function_name) {
        case 'match':
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
            break;
        default:
            break;
    }
}