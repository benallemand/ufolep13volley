<?php

require_once "../includes/fonctions_inc.php";
if (filter_input(INPUT_GET, 'callback') !== FALSE) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getCompetitions() . ")";
} else {
    echo getCompetitions();
}