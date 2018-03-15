<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

$idTeam = filter_input(INPUT_GET, 'idTeam');
echo getPlayersFromTeam($idTeam);

