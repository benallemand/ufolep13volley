<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";
$id = filter_input(INPUT_GET, 'id');
if (filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getTeam($id) . ")";
} else {
    echo getTeam($id);
}