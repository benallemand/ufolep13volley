<?php

//session_start();
require_once "../includes/fonctions_inc.php";
if (isAdmin()) {
    echo json_encode(array(
        'success' => true,
        'message' => 'admin'
    ));
    exit;
}
echo json_encode(array(
    'success' => true,
    'message' => 'basic'
));

