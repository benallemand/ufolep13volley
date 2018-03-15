<?php

try {
    require_once __DIR__ . "/../includes/fonctions_inc.php";

    $success = false;

    $code_match = filter_input(INPUT_POST, 'code_match');
    $report_date = filter_input(INPUT_POST, 'report_date');
    $success = giveReportDate($code_match, $report_date);
    echo json_encode(array(
        'success' => true,
        'message' => 'Modification OK'
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur durant la modification'
    ));
}


