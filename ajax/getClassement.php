<?php

require_once "../includes/fonctions_inc.php";

$compet = filter_input(INPUT_GET, 'competition');
$div = filter_input(INPUT_GET, 'division');
echo getRank($compet, $div);
?>
