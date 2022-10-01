<?php
try {
    require_once __DIR__ . '/../classes/Emails.php';
    require_once __DIR__ . '/../classes/Files.php';
    $files = new Files();
    $email = new Emails();
    $email->insert_email_matches_not_reported();
    $email->insert_email_next_matches();
    $email->insert_email_players_without_licence_number();
    $email->insert_email_team_leaders_without_email();
    $email->insert_email_alert_report();
    $email->insert_email_missing_licences();
    $files->cleanup_files();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}