<?php
require_once __DIR__ . '/../classes/MatchManager.php';
require_once __DIR__ . '/../classes/SqlManager.php';

use PHPUnit\Framework\TestCase;

class MatchManagerTest extends TestCase
{
    private $sql_manager;

    /**
     * @return void
     * @throws Exception
     */
    private function create_blacklist_date(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_date SET 
                               closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y')");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_blacklist_gymnase(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_gymnase SET 
                                  closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y'), 
                                  id_gymnase = (SELECT id_gymnase 
                                                FROM creneau
                                                WHERE id_equipe = 357 
                                                  AND jour = 'Lundi')");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_blacklist_team(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_teams SET id_team_1 = 470, id_team_2 = 357");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_blacklist_team(): void
    {
        $this->sql_manager->execute("DELETE FROM blacklist_teams WHERE id_team_1 = 470 AND id_team_2 = 357");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_blacklist_gymnase(): void
    {
        $this->sql_manager->execute("DELETE FROM blacklist_gymnase 
       WHERE closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y') 
         AND id_gymnase = (SELECT id_gymnase 
                           FROM creneau 
                           WHERE id_equipe = 357 
                             AND jour = 'Lundi')");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_weeks(): void
    {
        $this->sql_manager->execute("DELETE FROM journees WHERE code_competition = 'ut'");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_matches(): void
    {
        $this->sql_manager->execute("DELETE FROM matches WHERE code_competition = 'ut'");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_blacklist_date(): void
    {
        $this->sql_manager->execute("DELETE FROM blacklist_date 
       WHERE closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y')");
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sql_manager = new SqlManager();
        $this->cleanup();
    }

    /**
     * @throws Exception
     */
    public function test_get_count_matches_per_day()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $week_id = $this->create_day();
        $this->assertEquals(0,
            $manager->get_count_matches_per_day('ut', '1', $week_id));
        $this->create_match($week_id);
        $this->assertEquals(1,
            $manager->get_count_matches_per_day('ut', '1', $week_id));
    }

    /**
     * @throws Exception
     */
    public function test_is_team_busy_for_week()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $week_id = $this->create_day();
        $this->assertFalse($manager->is_team_busy_for_week($week_id, 357));
        $this->create_match($week_id);
        $this->assertTrue($manager->is_team_busy_for_week($week_id, 357));
    }

    /**
     * @throws Exception
     */
    public function test_get_blacklisted_team_ids()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $this->create_blacklist_team();
        $this->assertEquals(array(357), $manager->get_blacklisted_team_ids(470));
        $this->assertEquals(array(470), $manager->get_blacklisted_team_ids(357));
        $this->assertEquals(array(), $manager->get_blacklisted_team_ids(356));
    }

    /**
     * @throws Exception
     */
    public function test_is_date_blacklisted()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $this->assertFalse($manager->is_date_blacklisted('01/08/2022'));
        $this->create_blacklist_date();
        $this->assertTrue($manager->is_date_blacklisted('01/08/2022'));
        $this->delete_blacklist_date();
        $this->create_blacklist_gymnase();
        $this->assertTrue($manager->is_date_blacklisted('01/08/2022', 357));
        $this->delete_blacklist_gymnase();
        $this->assertFalse($manager->is_date_blacklisted('01/08/2022', 357));
    }

    /**
     * @throws Exception
     */
    public function test_is_last_match_same_home()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $week_id = $this->create_day();
        $this->create_match($week_id);
        $this->assertFalse($manager->is_last_match_same_home(470, 357));
        $this->assertTrue($manager->is_last_match_same_home(357, 470));
    }

    /**
     * @throws Exception
     */
    public function test_has_match()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $week_id = $this->create_day();
        $match_id = $this->create_match($week_id);
        $this->assertTrue($manager->has_match(357, '01/01/2022'));
        $this->delete_match($match_id);
        $this->assertFalse($manager->has_match(357, '01/01/2022'));
    }

    /**
     * @throws Exception
     */
    public function test_delete_matches()
    {
        //20220828:PASS
        $manager = new MatchManager();
        $week_id = $this->create_day();
        $this->create_match($week_id);
        $this->assertTrue($manager->has_match(357, '01/01/2022'));
        $manager->delete_matches("code_competition = 'ut'");
        $this->assertFalse($manager->has_match(357, '01/01/2022'));
    }

    public function testGenerate_round_robin_rounds()
    {
        //20220828:PASS
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

    /**
     * @throws Exception
     */
    private function cleanup()
    {
        $this->delete_matches();
        $this->delete_weeks();
        $this->delete_blacklist_date();
        $this->delete_blacklist_gymnase();
        $this->delete_blacklist_team();
    }

    /**
     * @throws Exception
     */
    private function create_day()
    {
        return $this->sql_manager->execute("INSERT INTO journees SET 
                         code_competition = 'ut', 
                         numero = 1, 
                         nommage = 'test 1', 
                         libelle = null, 
                         start_date = STR_TO_DATE('01/01/2022', '%d/%m/%Y')");
    }

    /**
     * @param $week_id
     * @return array|int|string|null
     * @throws Exception
     */
    private function create_match($week_id)
    {
        return $this->sql_manager->execute("INSERT INTO matches SET 
                        code_competition = 'ut', 
                        date_reception = STR_TO_DATE('01/01/2022', '%d/%m/%Y'),
                        code_match = 'UT001',
                        division = '1',
                        id_journee = $week_id,
                        id_equipe_dom = 357,
                        id_equipe_ext = 470");
    }

    /**
     * @throws Exception
     */
    private function delete_match($match_id)
    {
        $this->sql_manager->execute("DELETE FROM matches WHERE id_match = $match_id");
    }

    /**
     * @throws Exception
     */
    private function delete_day($week_id)
    {
        $this->sql_manager->execute("DELETE FROM journees WHERE id = $week_id");
    }
}
