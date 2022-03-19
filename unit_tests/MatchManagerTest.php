<?php
require_once __DIR__ . '/../classes/MatchManager.php';

use PHPUnit\Framework\TestCase;

class MatchManagerTest extends TestCase
{

    public function testGenerate_round_robin_rounds()
    {
        $manager = new MatchManager();
        $rounds = $manager->generate_round_robin_rounds(8);
        $this->assertEquals(
            array(
                0 => array(
                    0 => '0 v 7',
                    1 => '1 v 6',
                    2 => '2 v 5',
                    3 => '3 v 4',
                ),
                1 => array(
                    0 => '0 v 6',
                    1 => '7 v 5',
                    2 => '1 v 4',
                    3 => '2 v 3',
                ),
                2 => array(
                    0 => '0 v 5',
                    1 => '6 v 4',
                    2 => '7 v 3',
                    3 => '1 v 2',
                ),
                3 => array(
                    0 => '0 v 4',
                    1 => '5 v 3',
                    2 => '6 v 2',
                    3 => '7 v 1',
                ),
                4 => array(
                    0 => '0 v 3',
                    1 => '4 v 2',
                    2 => '5 v 1',
                    3 => '6 v 7',
                ),
                5 => array(
                    0 => '0 v 2',
                    1 => '3 v 1',
                    2 => '4 v 7',
                    3 => '5 v 6',
                ),
                6 => array(
                    0 => '0 v 1',
                    1 => '2 v 7',
                    2 => '3 v 6',
                    3 => '4 v 5',
                ),
            )
            , $rounds);
    }
}
