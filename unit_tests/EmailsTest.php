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

    /**
     * @throws Exception
     */
    public function testGet_team_emails_as_admin()
    {
        $this->connect_as_admin();
        $email_manager = new Emails();
        $sql_manager = new SqlManager();
        $teams = $sql_manager->execute("SELECT id_equipe FROM equipes LIMIT 1");
        $this->assertNotEmpty($teams, "Aucune équipe en base pour le test");
        $id_equipe = (int)$teams[0]['id_equipe'];
        $emails = $email_manager->get_team_emails($id_equipe);
        $this->assertIsArray($emails);
    }

    /**
     * @throws Exception
     */
    public function testGet_team_emails_unauthorized_when_not_connected()
    {
        $this->expectException(Exception::class);
        $email_manager = new Emails();
        $email_manager->get_team_emails(1);
    }

    /**
     * @throws Exception
     */
    public function testGet_team_emails_forbidden_for_other_team()
    {
        $this->expectException(Exception::class);
        $sql_manager = new SqlManager();
        $teams = $sql_manager->execute("SELECT id_equipe FROM equipes LIMIT 2");
        $this->assertGreaterThanOrEqual(2, count($teams), "Pas assez d'équipes en base");
        $id_equipe_1 = (int)$teams[0]['id_equipe'];
        $id_equipe_2 = (int)$teams[1]['id_equipe'];
        $this->connect_as_team_leader($id_equipe_1);
        $email_manager = new Emails();
        $email_manager->get_team_emails($id_equipe_2);
    }

    /**
     * @throws Exception
     */
    public function testSet_read_status()
    {
        $this->connect_as_admin();
        $email_manager = new Emails();
        $id = $email_manager->insert_email(
            "test read status",
            "test body",
            "benallemand@gmail.com");
        $this->assertIsInt($id);
        $this->created_email_ids[] = $id;
        $email_manager->set_read_status($id, 1);
        $emails = $email_manager->get_emails("id = $id");
        $this->assertCount(1, $emails);
        $this->assertEquals(1, (int)$emails[0]['is_read']);
        $email_manager->set_read_status($id, 0);
        $emails = $email_manager->get_emails("id = $id");
        $this->assertEquals(0, (int)$emails[0]['is_read']);
    }

    /**
     * @throws Exception
     */
    public function testSet_read_status_unauthorized()
    {
        $this->expectException(Exception::class);
        $email_manager = new Emails();
        $email_manager->set_read_status(1, 1);
    }
}
