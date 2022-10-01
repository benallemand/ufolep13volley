<?php
try {
    require_once __DIR__ . '/../classes/Emails.php';
    $email = new Emails();
    $email->insert_email_activity();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}
