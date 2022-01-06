<?php
try {
    require_once __DIR__ . '/../classes/CronTasks.php';
    $cron_tasks = new CronTasks();
    $cron_tasks->sendMailMatchesNotReported();
    $cron_tasks->sendMailNextMatches();
    $cron_tasks->sendMailPlayersWithoutLicenceNumber();
    $cron_tasks->sendMailTeamLeadersWithoutEmail();
    $cron_tasks->sendMailAlertReport();
    $cron_tasks->sendMailMissingLicences();
    $cron_tasks->cleanupFiles();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}