<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Emails.php";
require_once __DIR__ . "/../classes/Players.php";
require_once __DIR__ . "/../ajax/classes/Files.php";

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
            "no-reply@ufolep13volley.org",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com");
        $this->assertIsInt($id);
        $this->assertNotEquals(0, $id);
        $email_manager->delete_email($id);
        $file_manager = new Files();
        $file_id = $file_manager->insert_file(
            "test file path",
            "test hash");
        $id = $email_manager->insert_email(
            "test subject",
            "test body",
            "no-reply@ufolep13volley.org",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            array($file_id));
        $this->assertIsInt($id);
        $this->assertNotEquals(0, $id);
        $email_files = $email_manager->get_email_files($id);
        $this->assertEquals(1, count($email_files));
        $email_manager->delete_email($id);
        $email_files = $email_manager->get_email_files($id);
        $this->assertEquals(0, count($email_files));
    }

    /**
     * @throws Exception
     */
    public function testInsert_email_notify_activated_player()
    {
        $email_manager = new Emails();
        $players_manager = new Players();
        $where = "j.est_actif + 0 > 0";
        $active_players = $players_manager->get_players($where);
        $email_ids = array();
        foreach ($active_players as $active_player) {
            $email_id = $email_manager->insert_email_notify_activated_player($active_player['id']);
            if ($email_id !== 0) {
                $email_ids[] = $email_id;
            }
        }
        $emails = $email_manager->get_emails("LENGTH(to_email) = 0");
        $this->assertEquals(0, count($emails));
        foreach ($email_ids as $email_id) {
            $email_manager->delete_email($email_id);
        }
    }
}
