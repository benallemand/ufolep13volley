<?php
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'UfolepTestCase.php';

class RegisterTest extends UfolepTestCase
{
    private Register $register;

    /**
     * @param $id
     * @return void
     * @throws Exception
     */
    public function delete_registrations_from_competition($id): void
    {
        $registrations = $this->register->get("id_competition = ?", array(array('type' => 'i', 'value' => $id)));
        $registrations_ids = array();
        foreach ($registrations as $registration) {
            $registrations_ids[] = $registration['id'];
        }
        if (count($registrations_ids) > 0) {
            $this->register->delete(implode(',', $registrations_ids));
        }
    }

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->connect_as_admin();
        $this->register = new Register();
    }


    /**
     * @throws Exception
     */
    public function test_set_up_season_kh()
    {
        //230105:PASS
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $this->register->set_up_season($competition_kh['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_isoardi()
    {
        //230105:PASS
        $competition_mgr = new Competition();
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
        // test for isoardi, where registration is automatic
        $competition_isoardi = $competition_mgr->getCompetition('c');
        $this->register->set_up_season($competition_isoardi['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_masc()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition_masc = $competition_mgr->getCompetition('m');
        $this->register->set_up_season($competition_masc['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_fem()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition_masc = $competition_mgr->getCompetition('f');
        $this->register->set_up_season($competition_masc['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_set_up_season_mo()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        // test for masc
        $competition = $competition_mgr->getCompetition('mo');
        $this->register->set_up_season($competition['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_get_pending_registrations()
    {
        //230123:PASS
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('ut');
        $this->delete_registrations_from_competition($comp['id']);
        try {
            $this->register->register(
                'test_ut',
                41, // club de test
                $comp['id'],
                null,
                'test_leader_name',
                'test_leader_first_name',
                'test_leader_email',
                'test_leader_phone',
                null,
                null,
                null,
                null,
                null,
                null,
                'test_register');
        } catch (Exception $exception) {
            $this->assertEquals($exception->getCode(), 201);
        }
        $pending_registrations = $this->register->get_pending_registrations($comp['id']);
        $this->assertNotEmpty($pending_registrations);
    }

    /**
     * @throws Exception
     */
    public function test_get_2nd_half_registrations()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        foreach (array('f', 'm', 'mo') as $code_competition) {
            $comp = $competition_mgr->getCompetition($code_competition);
            $this->delete_registrations_from_competition($comp['id']);
            try {
                $this->register->register(
                    'test_' . $code_competition . '_' . date('Ymd-m-Y-H-i-s'),
                    41, // club de test
                    $comp['id'],
                    null,
                    'test_leader_name',
                    'test_leader_first_name',
                    'test_leader_email',
                    'test_leader_phone',
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'test_register');
            } catch (Exception $exception) {
                $this->assertEquals($exception->getCode(), 201);
            }
            $this->assertCount(1, $this->register->get_2nd_half_registrations($comp['id']));
        }
    }
}