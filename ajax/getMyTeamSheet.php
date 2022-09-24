<?php

require_once __DIR__ . '/../classes/TeamManager.php';

try {
    $team_manager = new TeamManager();
    echo $team_manager->getTeamSheet($_SESSION['id_equipe']);
} catch (Exception $e) {
    echo "Erreur ! " . $e->getMessage();
}

