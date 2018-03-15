<?php

require_once __DIR__ . "/../includes/fonctions_inc.php";
if(filter_input(INPUT_GET, 'callback') !== null) {
    echo filter_input(INPUT_GET, 'callback') . "(" . getWebSites() . ")";
}
else {
    echo getWebSites();
}
