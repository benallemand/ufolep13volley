<?php
require_once '../classes/CronTasks.php';
$cron_tasks = new CronTasks();
$cron_tasks->sendMailMatchesNotReported();
$cron_tasks->sendMailNextMatches();
$cron_tasks->sendMailPlayersWithoutLicenceNumber();
$cron_tasks->sendMailTeamLeadersWithoutEmail();