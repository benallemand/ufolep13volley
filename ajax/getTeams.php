<?php

require_once "../includes/fonctions_inc.php";

if (filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getTeams() . ")";
} else {
    echo getTeams();
}