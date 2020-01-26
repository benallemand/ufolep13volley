<?php
try {
    require_once __DIR__ . '/../classes/CronTasks.php';
    $cron_tasks = new CronTasks();
    $cron_tasks->send_pending_emails();
} catch (Exception $exception) {
    echo json_encode(array(
        'success' => false,
        'message' => $exception->getMessage()
    ));
}
