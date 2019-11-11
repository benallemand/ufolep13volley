<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";

$date_string = date('d/m/Y');
//$date_string = "06/06/2019";
if (filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getWeekMatches($date_string) . ")";
} else {
    echo getWeekMatches($date_string);
}
