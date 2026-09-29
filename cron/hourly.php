<?php
try {
    require_once __DIR__ . '/../classes/Emails.php';
    // Pénalités automatiques (issue #345) avant l'envoi : leurs emails partent
    // dans la même passe.
    try {
        require_once __DIR__ . '/../classes/MatchPenalty.php';
        (new MatchPenalty())->apply_unsigned_sheet_penalties();
    } catch (Exception $exception) {
        // une erreur ici ne doit pas bloquer la file d'emails
        error_log('Pénalités automatiques : ' . $exception->getMessage());
    }
    $emails = new Emails();
    $emails->send_pending_emails();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}
