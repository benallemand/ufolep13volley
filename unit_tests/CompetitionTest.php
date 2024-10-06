<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../classes/Competition.php";

class CompetitionTest extends TestCase
{
    private Competition $competition;
    private SqlManager $sql_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sql_manager = new SqlManager();
        $this->competition = new Competition();
    }

    /**
     * @throws Exception
     */
    public function testInit_classements_isoardi()
    {
        $this->competition->init_classements_isoardi(true);
    }

    /**
     * @throws Exception
     */
    public function test_is_championship()
    {
        foreach (array('m', 'f', 'mo') as $code) {
            $comp = $this->competition->getCompetition($code);
            $this->assertTrue($this->competition->is_championship($comp['id']));
        }
        foreach (array('c', 'cf', 'kh', 'kf') as $code) {
            $comp = $this->competition->getCompetition($code);
            $this->assertFalse($this->competition->is_championship($comp['id']));
        }
    }

    /**
     * @throws Exception
     */
    public function test_is_first_half()
    {
        foreach (array('m', 'f', 'mo') as $code) {
            $comp = $this->competition->getCompetition($code);
            $this->assertTrue($this->competition->is_first_half($comp['id']));
        }
        foreach (array('c', 'kh') as $code) {
            $comp = $this->competition->getCompetition($code);
            $this->assertFalse($this->competition->is_first_half($comp['id']));
        }
    }


    /**
     * @throws Exception
     */
    public function test_generate_matches_cup()
    {
        $this->sql_manager->execute("DELETE FROM matches 
                    WHERE code_competition IN ('kf', 'cf') 
                      AND id_journee IN (SELECT id 
                                         FROM journees 
                                         WHERE code_competition IN ('kf', 'cf') 
                                           AND numero = 1)");
        $cups = array('kf', 'cf');
        foreach ($cups as $cup_code) {
            $cup = $this->competition->getCompetition($cup_code);
            $this->competition->generate_matches_final_phase_cup($cup['id'], 1);
            $sql = "SELECT * 
                    FROM matches 
                    WHERE code_competition = '$cup_code' 
                      AND id_journee IN (SELECT id 
                                         FROM journees 
                                         WHERE code_competition = '$cup_code' 
                                           AND numero = 1)";
            $this->assertNotEmpty($this->sql_manager->execute($sql));
        }
    }

    public function test_make_hats()
    {
        $teams = array(
            array(
                'name' => 'team_11',
                'division' => '1',
            ),
            array(
                'name' => 'team_12',
                'division' => '1',
            ),
            array(
                'name' => 'team_13',
                'division' => '1',
            ),
            array(
                'name' => 'team_21',
                'division' => '2',
            ),
            array(
                'name' => 'team_22',
                'division' => '2',
            ),
            array(
                'name' => 'team_23',
                'division' => '2',
            ),
            array(
                'name' => 'team_31',
                'division' => '3',
            ),
            array(
                'name' => 'team_32',
                'division' => '3',
            ),
            array(
                'name' => 'team_33',
                'division' => '3',
            ),
        );
        $hats = $this->competition->make_hats($teams);
        $this->assertEquals(
            array(
                array(
                    array(
                        'name' => 'team_11',
                        'division' => '1',
                    ),
                    array(
                        'name' => 'team_12',
                        'division' => '1',
                    ),
                    array(
                        'name' => 'team_13',
                        'division' => '1',
                    ),
                ),
                array(
                    array(
                        'name' => 'team_21',
                        'division' => '2',
                    ),
                    array(
                        'name' => 'team_22',
                        'division' => '2',
                    ),
                    array(
                        'name' => 'team_23',
                        'division' => '2',
                    ),
                ),
                array(
                    array(
                        'name' => 'team_31',
                        'division' => '3',
                    ),
                    array(
                        'name' => 'team_32',
                        'division' => '3',
                    ),
                    array(
                        'name' => 'team_33',
                        'division' => '3',
                    ),
                ),
            ),
            $hats);
    }


    /**
     * @throws Exception
     */
    public function test_make_pools_of_3()
    {
        $hats = array(
            array(1, 1, 1),
            array(2, 2, 2),
            array(3, 3, 3),
        );
        $pools = Competition::make_pools_of_3($hats);
        $this->assertEquals(
            array(
                array(1, 2, 3),
                array(1, 2, 3),
                array(1, 3, 2),
            ),
            $pools);
        $hats = array(
            array(1, 1, 1),
            array(2, 2,),
            array(3, 3, 3),
        );
        $pools = Competition::make_pools_of_3($hats);
        $this->assertEquals(
            array(
                array(1, 2, 3, 3),
                array(1, 2, 1, 3),
            ),
            $pools);
        $hats = array(
            array(1, 1, 1),
            array(2, 2,),
            array(3, 3,),
        );
        $pools = Competition::make_pools_of_3($hats);
        $this->assertEquals(
            array(
                array(1, 2, 3,),
                array(1, 2, 1, 3),
            ),
            $pools);
    }


}
