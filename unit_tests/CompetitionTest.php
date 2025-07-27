<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once 'UfolepTestCase.php';

require_once __DIR__ . "/../classes/Competition.php";
require_once __DIR__ . "/../classes/Day.php";

class CompetitionTest extends UfolepTestCase
{
    private Competition $competition;

    private function init_m_classements() {
        // initial conditions:
        // - remove all 'm' teams from classements
        $this->sql->execute("DELETE FROM classements WHERE code_competition ='m'");
        // - pick 10 teams from 'm' with is_cup_registered = 1, and add them to classements
        // - 5 teams in division 1 with rank_start 1-5, and 5 teams in division 2 with rank_start 1-5
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start) 
                SELECT 'm', 
                       CASE WHEN row_num <= 5 THEN '1' ELSE '2' END as division, 
                       id_equipe, 
                       CASE WHEN row_num <= 5 THEN row_num ELSE row_num - 5 END as rank_start
                FROM (
                    SELECT id_equipe, @row_number := @row_number + 1 as row_num
                    FROM equipes, (SELECT @row_number := 0) as r
                    WHERE code_competition = 'm' AND is_cup_registered = 1
                    LIMIT 10
                ) as numbered_teams";
        $this->sql->execute($sql);

    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->competition = new Competition();
        $this->day = new Day();
        $this->limit_date = new LimitDate();
        $this->match = new MatchMgr();
    }

    /**
     * @throws Exception
     */
    public function testInit_classements_isoardi()
    {
        $this->init_m_classements();
        $this->competition->init_classements_isoardi(true);
        $this->assertTrue(1 == 1);
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
            $inputs = array(
                'id' => $comp['id'],
                'start_date' => '07/11/2024',
            );
            $this->competition->save($inputs);
            $this->assertTrue($this->competition->is_first_half($comp['id']));
        }
        foreach (array('c', 'kh') as $code) {
            $comp = $this->competition->getCompetition($code);
            $inputs = array(
                'id' => $comp['id'],
                'start_date' => '07/03/2025',
            );
            $this->competition->save($inputs);
            $this->assertFalse($this->competition->is_first_half($comp['id']));
        }
    }


    /**
     * @throws Exception
     */
    public function test_generate_matches_cup()
    {
        $this->sql->execute("DELETE FROM matches 
                    WHERE code_competition IN ('kf', 'cf') 
                      AND id_journee IN (SELECT id 
                                         FROM journees 
                                         WHERE code_competition IN ('kf', 'cf') 
                                           AND numero = 1)");
        $cups = array('kf', 'cf');
        foreach ($cups as $cup_code) {
            $cup = $this->competition->getCompetition($cup_code);
            $limit_dates = $this->limit_date->getLimitDates();
            foreach ($limit_dates as $limit_date) {
                if ($limit_date['code_competition'] == $cup['code_competition']) {
                    $this->limit_date->saveLimitDate(
                        $cup['code_competition'],
                        date('d/m/Y', strtotime('+2 month')),
                        $limit_date['id_date']);
                    break;
                }
            }
            $this->match->delete_matches("code_competition = '$cup_code'");
            $this->day->deleteDays("code_competition = '$cup_code'");
            $this->day->insertDay(
                $cup['code_competition'],
                strval(1),
                date('d/m/Y', strtotime('+1 week')),
                false,
                $this->limit_date->getLimitDate($cup['code_competition'])
            );
            $this->competition->generate_matches_final_phase_cup($cup['id'], 'Journee 01');
            $sql = "SELECT * 
                    FROM matches 
                    WHERE code_competition = '$cup_code' 
                      AND id_journee IN (SELECT id 
                                         FROM journees 
                                         WHERE code_competition = '$cup_code' 
                                           AND numero = 1)";
            $this->assertNotEmpty($this->sql->execute($sql));
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
