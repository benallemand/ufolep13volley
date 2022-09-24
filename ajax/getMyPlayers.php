<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

echo json_encode(getPlayersPdf($_SESSION['id_equipe']));

