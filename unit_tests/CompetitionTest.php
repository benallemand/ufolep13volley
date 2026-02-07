<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'UfolepTestCase.php';

require_once __DIR__ . "/../classes/Competition.php";

class CompetitionTest extends UfolepTestCase
{
    private Competition $competition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->competition = new Competition();
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
