<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";
$idEquipe = filter_input(INPUT_GET, 'id_equipe');
echo getQuickDetails($idEquipe);

