<?php

require_once "../includes/fonctions_inc.php";
$id = filter_input(INPUT_GET, 'id');
if (filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getTeam() . ")";
} else {
    echo getTeam($id);
}