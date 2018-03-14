<?php
try {
    require_once '../classes/CronTasks.php';
    $cron_tasks = new CronTasks();
    $cron_tasks->sendMailMatchesNotReported();
    $cron_tasks->sendMailNextMatches();
    $cron_tasks->sendMailPlayersWithoutLicenceNumber();
    $cron_tasks->sendMailTeamLeadersWithoutEmail();
    $cron_tasks->sendMailAlertReport();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}