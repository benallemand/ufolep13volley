<?php

require_once "../includes/fonctions_inc.php";
$idEquipe = filter_input(INPUT_GET, 'id_equipe');
echo getQuickDetails($idEquipe);

