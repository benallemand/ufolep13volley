<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Emails.php";
require_once __DIR__ . "/../classes/Players.php";
require_once __DIR__ . "/../classes/Files.php";

class EmailsTest extends UfolepTestCase
{
    private array $created_email_ids = [];
    private array $created_file_ids = [];

    protected function tearDown(): void
    {
        $email_manager = new Emails();
        $file_manager = new Files();
        foreach ($this->created_email_ids as $id) {
            $email_manager->delete_email($id);
        }
        foreach ($this->created_file_ids as $id) {
            $file_manager->delete($id);
        }
        $this->created_email_ids = [];
        $this->created_file_ids = [];
        parent::tearDown();
    }

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
        $this->created_email_ids[] = $id;
        $file_manager = new Files();
        $file_id = $file_manager->save(
            array('path_file' => "test file path",
                'hash' => "test hash"));
        $this->created_file_ids[] = $file_id;
        $id2 = $email_manager->insert_email(
            "test subject",
            "test body",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            "benallemand@gmail.com",
            array($file_id));
        $this->assertIsInt($id2);
        $this->assertNotEquals(0, $id2);
        $this->created_email_ids[] = $id2;
        $email_files = $email_manager->get_email_files($id2);
        $this->assertCount(1, $email_files);
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
        foreach ($active_players as $active_player) {
            $email_id = $email_manager->insert_email_notify_activated_player($active_player['id']);
            if ($email_id !== 0) {
                $this->created_email_ids[] = $email_id;
            }
        }
        $emails = $email_manager->get_emails("LENGTH(to_email) = 0");
        $this->assertCount(0, $emails);
    }
}
