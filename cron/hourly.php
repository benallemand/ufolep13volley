<?php
try {
    require_once __DIR__ . '/../classes/Emails.php';
    $emails = new Emails();
    $emails->send_pending_emails();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}
