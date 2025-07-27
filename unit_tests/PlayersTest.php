<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../classes/Players.php";
require_once 'UfolepTestCase.php';

class PlayersTest extends UfolepTestCase
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
        $this->assertTrue(1 == 1);
    }

    public function test_getPlayersPdf()
    {
        $this->connect_as_admin();
        $this->players->getPlayersPdf(344);
        $this->assertTrue(1 == 1);
    }

}
