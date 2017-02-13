<?php

require_once "../includes/fonctions_inc.php";

$success = false;

$code_match = filter_input(INPUT_POST, 'code_match');
$report_date = filter_input(INPUT_POST, 'report_date');
$success = giveReportDate($code_match, $report_date);

echo json_encode(array(
    'success' => $success,
    'message' => $success ? 'Modification OK' : 'Erreur durant la modification'
));
