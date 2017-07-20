<?php
require_once '../classes/CronTasks.php';
$cron_tasks = new CronTasks();
$cron_tasks->sendMailActivity();
