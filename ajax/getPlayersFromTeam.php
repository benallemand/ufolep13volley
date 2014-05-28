<?php

require_once "../includes/fonctions_inc.php";

$idTeam = filter_input(INPUT_GET, 'idTeam');
echo getPlayersFromTeam($idTeam);

