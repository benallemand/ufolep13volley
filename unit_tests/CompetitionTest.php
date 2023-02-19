<?php


use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Competition.php";

class CompetitionTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testInit_classements_isoardi()
    {
        $competition = new Competition();
        $competition->init_classements_isoardi(true);
    }

    /**
     * @throws Exception
     */
    public function test_is_championship()
    {
        $competition = new Competition();
        foreach (array('m', 'f', 'mo') as $code) {
            $comp = $competition->getCompetition($code);
            $this->assertTrue($competition->is_championship($comp['id']));
        }
        foreach (array('c', 'cf', 'kh', 'kf') as $code) {
            $comp = $competition->getCompetition($code);
            $this->assertFalse($competition->is_championship($comp['id']));
        }
    }

    /**
     * @throws Exception
     */
    public function test_is_first_half()
    {
        $competition = new Competition();
        foreach (array('m', 'f', 'mo') as $code) {
            $comp = $competition->getCompetition($code);
            $this->assertTrue($competition->is_first_half($comp['id']));
        }
        foreach (array('c', 'kh') as $code) {
            $comp = $competition->getCompetition($code);
            $this->assertFalse($competition->is_first_half($comp['id']));
        }
    }


}
