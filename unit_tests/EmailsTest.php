<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

require_once __DIR__ . "/../classes/Emails.php";
require_once __DIR__ . "/../classes/Players.php";

class EmailsTest extends UfolepTestCase
{
    private array $created_email_ids = [];

    protected function tearDown(): void
    {
        $email_manager = new Emails();
        foreach ($this->created_email_ids as $id) {
            $email_manager->delete_email($id);
        }
        $this->created_email_ids = [];
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
