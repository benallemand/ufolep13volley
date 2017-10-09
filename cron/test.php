<?php
require_once '../classes/CronTasks.php';
$cron_tasks = new CronTasks();
$test_email = "benallemand@gmail.com";
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailPlayersWithoutLicenceNumber.fr.html',
    array(
        'joueurs' => "test",
        'club' => "test",
        'equipe' => "test",
        'responsable' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailNextMatches.fr.html',
    array(
        'next_matches' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailMatchNotReported.fr.html',
    array(
        'equipe_reception' => "test",
        'equipe_visiteur' => "test",
        'date_reception' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailActivity.fr.html',
    array(
        'activity' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailAccountRecap.fr.html',
    array(
        'email' => "test",
        'login' => "test",
        'password' => "test",
        'team' => "test",
        'competition' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailTeamLeadersWithoutEmail.fr.html',
    array(
        'team_leaders_without_email' => "test"
    ),
    $test_email
);
$cron_tasks->sendGenericEmail(
    '../templates/emails/sendMailAlertReport.fr.html',
    array(
        'match_reference' => "test",
        'team_home' => "test",
        'team_guest' => "test",
        'original_match_date' => "test"
    ),
    $test_email
);
