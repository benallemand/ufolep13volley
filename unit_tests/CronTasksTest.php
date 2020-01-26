<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/CronTasks.php";
require_once __DIR__ . "/../ajax/classes/Files.php";

class CronTasksTest extends TestCase
{

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function testSend_pending_emails()
    {
        // create email
        $email_manager = new Emails();
        $file_manager = new Files();
        $file_id = $file_manager->insert_file(
            "images/logo_ufolep.jpg",
            md5_file(__DIR__ . '/../images/logo_ufolep.jpg'));
        $id = $email_manager->insert_email(
            "test subject",
            "test body",
            "no-reply@ufolep13volley.org",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            array($file_id));
        // run cron
        $cron_task = new CronTasks();
        $cron_task->send_pending_emails();
        // delete email
        $email_manager->delete_email($id);
        $file_manager->delete_file($file_id);
    }
}
