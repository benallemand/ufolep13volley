<?php

session_start();

function estAdmin() {
    return (isset($_SESSION['id_equipe']) && $_SESSION['id_equipe'] == "admin");
}

if (estAdmin()) {
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
?>
