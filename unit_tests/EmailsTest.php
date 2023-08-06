<?php

require_once __DIR__ . '/../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Emails.php";
require_once __DIR__ . "/../classes/Players.php";
require_once __DIR__ . "/../classes/Files.php";

class EmailsTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testInsert_email()
    {
        $email_manager = new Emails();
        $id = $email_manager->insert_email(
            "test subject",
            "test body",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com");
        $this->assertIsInt($id);
        $this->assertNotEquals(0, $id);
        $email_manager->delete_email($id);
        $file_manager = new Files();
        $file_id = $file_manager->save(
            array('path_file' => "test file path",
                'hash' => "test hash"));
        $id = $email_manager->insert_email(
            "test subject",
            "test body",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            array($file_id));
        $this->assertIsInt($id);
        $this->assertNotEquals(0, $id);
        $email_files = $email_manager->get_email_files($id);
        $this->assertCount(1, $email_files);
        $email_manager->delete_email($id);
        $email_files = $email_manager->get_email_files($id);
        $this->assertCount(0, $email_files);
    }

    /**
     * @throws Exception
     */
    public function testInsert_email_notify_activated_player()
    {
        $email_manager = new Emails();
        $players_manager = new Players();
        $where = "j.est_actif = 1";
        $active_players = $players_manager->get_players($where);
        $email_ids = array();
        foreach ($active_players as $active_player) {
            $email_id = $email_manager->insert_email_notify_activated_player($active_player['id']);
            if ($email_id !== 0) {
                $email_ids[] = $email_id;
            }
        }
        $emails = $email_manager->get_emails("LENGTH(to_email) = 0");
        $this->assertCount(0, $emails);
        foreach ($email_ids as $email_id) {
            $email_manager->delete_email($email_id);
        }
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function testSend_pending_emails()
    {
        // create email
        $email_manager = new Emails();
        $file_manager = new Files();
        $email_manager->delete_emails();
        $file_id = $file_manager->save(
            array('path_file' => "images/logo_ufolep.jpg",
                'hash' => md5_file(__DIR__ . '/../images/logo_ufolep.jpg')));
        $id = $email_manager->insert_email(
            "test subject",
            "test body",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            array($file_id));
        $emails = $email_manager->get_emails("id = $id");
        $this->assertCount(1, $emails);
        $this->assertEquals('TO_DO', $emails[0]['sending_status']);
        $email_manager->send_pending_emails();
        $emails = $email_manager->get_emails("id = $id");
        $this->assertCount(1, $emails);
        $this->assertEquals('DONE', $emails[0]['sending_status']);
        // delete email
        $email_manager->delete_email($id);
        $file_manager->delete($file_id);
    }

    /**
     * @doesNotPerformAssertions
     * @throws Exception
     */
    public function test_sendMailMissingLicences()
    {
        $email_manager = new Emails();
        $email_manager->insert_email_missing_licences();
    }
}
