<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Players.php";

class PlayersTest extends TestCase
{
    private Players $players;

    protected function setUp(): void
    {
        parent::setUp();
        $this->players = new Players();
    }

    public function test_generateLowPhoto()
    {
        $this->players->generateLowPhoto('players_pics/AHOUANSOUVirginie1.jpg');
    }

    public function test_getPlayersPdf()
    {
        $this->connect_as_admin();
        $this->players->getPlayersPdf(344);
    }

    private function connect_as_admin()
    {
        @session_start();
        $_SESSION['id_equipe'] = null;
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = 1;
        $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    }

}
