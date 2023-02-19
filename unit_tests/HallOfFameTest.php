<?php


use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/HallOfFame.php";

class HallOfFameTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function test_generate_hall_of_fame()
    {
        $hof = new HallOfFame();
        $competition = new Competition();
        foreach (array('m', 'f', 'mo') as $code) {
            $comp = $competition->getCompetition($code);
            $hof->generateHallOfFame($comp['id']);
        }
    }
}
