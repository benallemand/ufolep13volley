<?php
require_once __DIR__ . '/../classes/SqlManager.php';
function manage_match_players($parameters)
{
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
    $sql = "DELETE FROM match_player WHERE id_match = ?";
    $bindings = array();
    $bindings[] = array(
        'type' => 'i',
        'value' => $parameters['id_match']
    );
    $sql_manager->execute($sql, $bindings);
    foreach ($parameters['player_ids'] as $player_id) {
        if(empty($player_id)) {
            continue;
        }
        $sql = "INSERT INTO match_player(id_match, id_player) VALUE (?, ?)";
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
}