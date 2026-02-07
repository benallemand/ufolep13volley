<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/Day.php';
require_once __DIR__ . '/../classes/LimitDate.php';
require_once __DIR__ . '/../classes/Competition.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Emails.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';


class MatchManagerTest extends UfolepTestCase
{
    private SqlManager $sql_manager;
    private MatchMgr $match_manager;
    private Competition $competition;
    private Day $day;
    private Players $players_manager;
    private Emails $emails;

    /**
     * @return void
     * @throws Exception
     */
    private function create_test_blacklist_date(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_date SET 
                               closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y')");
    }

    /**
     * @param mixed $comp
     * @return void
     * @throws Exception
     */
    public function setTestDates(mixed $comp): void
    {
        $inputs = array(
            'id' => $comp['id'],
            'start_date' => date('d/m/Y', strtotime('+1 week')),
        );
        $this->competition->save($inputs);
        $limit_dates = $this->limit_date->getLimitDates();
        foreach ($limit_dates as $limit_date) {
            if ($limit_date['code_competition'] == $comp['code_competition']) {
                $this->limit_date->saveLimitDate(
                    $comp['code_competition'],
                    date('d/m/Y', strtotime('+4 month')),
                    $limit_date['id_date']);
                break;
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_test_full_competition(): void
    {
        $this->delete_test_full_competition();
        $id_competition = $this->sql_manager->execute("INSERT INTO competitions SET 
                               code_competition = 'ut',
                               libelle = 'unit tests',
                               id_compet_maitre = 'ut',
                               start_date = CURRENT_DATE - INTERVAL 30 DAY");
        $id_day = $this->sql_manager->execute("INSERT INTO journees SET 
                               code_competition = 'ut',
                               numero = 1,
                               nommage = 'J1',
                               libelle = 'J1',
                               start_date = CURRENT_DATE - INTERVAL 30 DAY");
        $id_club1 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'test club 1'");
        $id_club2 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'test club 2'");
        $id_team1 = $this->sql_manager->execute("INSERT INTO equipes SET 
                                                       code_competition = 'ut',
                                                       nom_equipe = 'test team 1',
                                                       id_club = $id_club1");
        $id_team2 = $this->sql_manager->execute("INSERT INTO equipes SET 
                                                       code_competition = 'ut',
                                                       nom_equipe = 'test team 2',
                                                       id_club = $id_club2");
        $id_rank1 = $this->sql_manager->execute("INSERT INTO classements SET 
                                                       code_competition = 'ut',
                                                       division = '1',
                                                       id_equipe = $id_team1,
                                                       rank_start = 1");
        $id_rank2 = $this->sql_manager->execute("INSERT INTO classements SET 
                                                       code_competition = 'ut',
                                                       division = '1',
                                                       id_equipe = $id_team2,
                                                       rank_start = 2");
        $id_court1 = $this->sql_manager->execute("INSERT INTO gymnase SET 
                                                       nom = 'test court 1'");
        $id_court2 = $this->sql_manager->execute("INSERT INTO gymnase SET 
                                                       nom = 'test court 2'");
        $id_timeslot1 = $this->sql_manager->execute("INSERT INTO creneau SET 
                                                       id_gymnase = $id_court1,
                                                       jour = 'Lundi',
                                                       heure = '20:00',
                                                       id_equipe = $id_team1");
        $id_timeslot2 = $this->sql_manager->execute("INSERT INTO creneau SET 
                                                       id_gymnase = $id_court2,
                                                       jour = 'Mardi',
                                                       heure = '20:00',
                                                       id_equipe = $id_team2");
        $id_match1 = $this->sql_manager->execute("INSERT INTO matches SET
                        code_match = 'UT001',
                        code_competition='ut',
                        division='1',
                        id_equipe_dom = $id_team1,
                        id_equipe_ext = $id_team2,
                        date_reception = CURRENT_DATE - INTERVAL 30 DAY,
                        id_journee = $id_day,
                        id_gymnasium = $id_court1,
                        date_original = CURRENT_DATE - INTERVAL 30 DAY,
                        match_status = 'CONFIRMED'");
        // insert players to test teams
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team1 FROM joueurs WHERE sexe = 'M' LIMIT 0,5");
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team1 FROM joueurs WHERE sexe = 'F' LIMIT 0,5");
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team2 FROM joueurs WHERE sexe = 'M' LIMIT 10,5");
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team2 FROM joueurs WHERE sexe = 'F' LIMIT 10,5");
        // get team leaders
        $team_players_1 = $this->sql_manager->execute("SELECT * FROM joueur_equipe WHERE id_equipe = $id_team1");
        $team_players_2 = $this->sql_manager->execute("SELECT * FROM joueur_equipe WHERE id_equipe = $id_team2");
        // add team leader emails
        $this->sql_manager->execute("UPDATE joueur_equipe SET is_leader = 1 WHERE id_joueur = ?", array(array('type' => 'i', 'value' => $team_players_1[0]['id_joueur'])));
        $this->sql_manager->execute("UPDATE joueur_equipe SET is_leader = 1 WHERE id_joueur = ?", array(array('type' => 'i', 'value' => $team_players_2[0]['id_joueur'])));
        $this->sql_manager->execute("UPDATE joueurs SET email = 'a@b.fr' WHERE id = ?", array(array('type' => 'i', 'value' => $team_players_1[0]['id_joueur'])));
        $this->sql_manager->execute("UPDATE joueurs SET email = 'c@d.fr' WHERE id = ?", array(array('type' => 'i', 'value' => $team_players_2[0]['id_joueur'])));
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_test_blacklist_gymnase(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_gymnase SET 
                                  closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y'), 
                                  id_gymnase = 45");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_test_blacklist_team(): void
    {
        $this->sql_manager->execute("INSERT INTO blacklist_teams SET 
                                id_team_1 = (SELECT id_equipe 
                                             FROM equipes 
                                             WHERE nom_equipe = 'test team 1'), 
                                id_team_2 = (SELECT id_equipe 
                                             FROM equipes 
                                             WHERE nom_equipe = 'test team 2')");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_blacklist_team(): void
    {
        $this->sql_manager->execute("DELETE FROM blacklist_teams WHERE id_team_1 = 470 and id_team_2 = 357");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_blacklist_gymnase(): void
    {
        $this->sql_manager->execute("DELETE FROM blacklist_gymnase 
       WHERE closed_date = STR_TO_DATE('01/08/2022', '%d/%m/%Y')
    and id_gymnase = (SELECT id_gymnase
                           FROM creneau 
                           WHERE id_equipe = 357
    and jour = 'Lundi')");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_weeks(): void
    {
        $this->sql_manager->execute("DELETE FROM journees WHERE code_competition = 'ut'");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_matches(): void
    {
        $this->sql_manager->execute("DELETE FROM matches WHERE code_competition = 'ut'");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_blacklist_date(): void
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
        $this->match_manager = new MatchMgr();
        $this->players_manager = new Players();
        $this->competition = new Competition();
        $this->day = new Day();
        $this->limit_date = new LimitDate();
        $this->emails = new Emails();
        $this->create_test_full_competition();
    }

    /**
     * @throws Exception
     */
    public function test_is_match_read_allowed_team_leader_multi_teams(): void
    {
        $teams = $this->get_test_teams();
        $team1 = $teams[0]['id_equipe'];
        $team2 = $teams[1]['id_equipe'];

        $id_club3 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'test club 3'");
        $team3 = $this->sql_manager->execute("INSERT INTO equipes SET code_competition = 'ut', nom_equipe = 'test team 3', id_club = $id_club3");
        $rank3 = $this->sql_manager->execute("INSERT INTO classements SET code_competition = 'ut', division = '1', id_equipe = $team3, rank_start = 3");
        $court3 = $this->sql_manager->execute("INSERT INTO gymnase SET nom = 'test court 3'");

        $day = $this->get_test_day();
        $matchId = $this->sql_manager->execute("INSERT INTO matches SET code_match = 'UT002', code_competition='ut', division='1', id_equipe_dom = $team2, id_equipe_ext = $team3, date_reception = CURRENT_DATE - INTERVAL 30 DAY, id_journee = {$day['id']}, id_gymnasium = $court3, date_original = CURRENT_DATE - INTERVAL 30 DAY, match_status = 'CONFIRMED'");

        $profileId = $this->sql_manager->execute("SELECT id FROM profiles WHERE name = 'RESPONSABLE_EQUIPE'");
        if (is_array($profileId)) {
            $profileId = $profileId[0]['id'];
        }

        $userId = $this->sql_manager->execute("INSERT INTO comptes_acces SET login = 'ut_multi_team_leader', email = 'ut_multi_team_leader@ufolep.test', password_hash = 'x'");
        $this->sql_manager->execute("INSERT INTO users_profiles SET user_id = $userId, profile_id = $profileId");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId, team_id = $team1");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId, team_id = $team2");

        @session_start();
        $_SESSION['id_equipe'] = $team1;
        $_SESSION['login'] = 'ut_multi_team_leader';
        $_SESSION['id_user'] = $userId;
        $_SESSION['profile_name'] = 'RESPONSABLE_EQUIPE';

        $this->assertTrue($this->match_manager->is_match_read_allowed($matchId));

        $this->sql_manager->execute("DELETE FROM users_teams WHERE user_id = $userId");
        $this->sql_manager->execute("DELETE FROM users_profiles WHERE user_id = $userId");
        $this->sql_manager->execute("DELETE FROM comptes_acces WHERE id = $userId");
        $this->sql_manager->execute("DELETE FROM matches WHERE id_match = $matchId");
        $this->sql_manager->execute("DELETE FROM classements WHERE id_equipe = $team3");
        $this->sql_manager->execute("DELETE FROM equipes WHERE id_equipe = $team3");
        $this->sql_manager->execute("DELETE FROM clubs WHERE nom = 'test club 3'");
        $this->sql_manager->execute("DELETE FROM gymnase WHERE nom = 'test court 3'");
    }

    /**
     * @throws Exception
     */
    public function test_is_match_update_allowed_team_leader_multi_teams(): void
    {
        $teams = $this->get_test_teams();
        $team1 = $teams[0]['id_equipe'];
        $team2 = $teams[1]['id_equipe'];

        $id_club3 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'test club 3'");
        $team3 = $this->sql_manager->execute("INSERT INTO equipes SET code_competition = 'ut', nom_equipe = 'test team 3', id_club = $id_club3");
        $rank3 = $this->sql_manager->execute("INSERT INTO classements SET code_competition = 'ut', division = '1', id_equipe = $team3, rank_start = 3");
        $court3 = $this->sql_manager->execute("INSERT INTO gymnase SET nom = 'test court 3'");

        $day = $this->get_test_day();
        $matchId = $this->sql_manager->execute("INSERT INTO matches SET code_match = 'UT003', code_competition='ut', division='1', id_equipe_dom = $team2, id_equipe_ext = $team3, date_reception = CURRENT_DATE - INTERVAL 30 DAY, id_journee = {$day['id']}, id_gymnasium = $court3, date_original = CURRENT_DATE - INTERVAL 30 DAY, match_status = 'CONFIRMED'");

        $profileId = $this->sql_manager->execute("SELECT id FROM profiles WHERE name = 'RESPONSABLE_EQUIPE'");
        if (is_array($profileId)) {
            $profileId = $profileId[0]['id'];
        }

        $userId = $this->sql_manager->execute("INSERT INTO comptes_acces SET login = 'ut_multi_team_leader2', email = 'ut_multi_team_leader2@ufolep.test', password_hash = 'x'");
        $this->sql_manager->execute("INSERT INTO users_profiles SET user_id = $userId, profile_id = $profileId");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId, team_id = $team1");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId, team_id = $team2");

        @session_start();
        $_SESSION['id_equipe'] = $team1;
        $_SESSION['login'] = 'ut_multi_team_leader2';
        $_SESSION['id_user'] = $userId;
        $_SESSION['profile_name'] = 'RESPONSABLE_EQUIPE';

        $this->assertTrue($this->match_manager->is_match_update_allowed($matchId));

        $this->sql_manager->execute("DELETE FROM users_teams WHERE user_id = $userId");
        $this->sql_manager->execute("DELETE FROM users_profiles WHERE user_id = $userId");
        $this->sql_manager->execute("DELETE FROM comptes_acces WHERE id = $userId");
        $this->sql_manager->execute("DELETE FROM matches WHERE id_match = $matchId");
        $this->sql_manager->execute("DELETE FROM classements WHERE id_equipe = $team3");
        $this->sql_manager->execute("DELETE FROM equipes WHERE id_equipe = $team3");
        $this->sql_manager->execute("DELETE FROM clubs WHERE nom = 'test club 3'");
        $this->sql_manager->execute("DELETE FROM gymnase WHERE nom = 'test court 3'");
    }

    /**
     * @throws Exception
     */
    public function test_get_count_matches_per_day()
    {
        //20220828:PASS
        $test_day = $this->get_test_day();
        $this->assertEquals(1,
            $this->match_manager->get_count_matches_per_day('ut', '1', $test_day['id']));
        $this->delete_test_matches();
        $this->assertEquals(0,
            $this->match_manager->get_count_matches_per_day('ut', '1', $test_day['id']));
    }

    /**
     * @throws Exception
     */
    public function test_is_team_busy_for_week()
    {
        //20220828:PASS
        $test_day = $this->get_test_day();
        $teams = $this->get_test_teams();
        foreach ($teams as $team) {
            $this->assertTrue($this->match_manager->is_team_busy_for_week($test_day['id'], $team['id_equipe']));
        }
        $this->delete_test_matches();
        foreach ($teams as $team) {
            $this->assertFalse($this->match_manager->is_team_busy_for_week($test_day['id'], $team['id_equipe']));
        }
    }

    /**
     * @throws Exception
     */
    public function test_get_blacklisted_team_ids()
    {
        //20220828:PASS
        $this->create_test_blacklist_team();
        $teams = $this->get_test_teams();
        $this->assertEquals(
            array($teams[0]['id_equipe']),
            $this->match_manager->get_blacklisted_team_ids($teams[1]['id_equipe']));
        $this->assertEquals(
            array($teams[1]['id_equipe']),
            $this->match_manager->get_blacklisted_team_ids($teams[0]['id_equipe']));
        $this->assertEquals(array(), $this->match_manager->get_blacklisted_team_ids(356));
    }

    /**
     * @throws Exception
     */
    public function test_is_date_blacklisted()
    {
        //20220828:PASS
        $this->assertFalse($this->match_manager->is_date_blacklisted('01/08/2022'));
        $this->create_test_blacklist_date();
        $this->assertTrue($this->match_manager->is_date_blacklisted('01/08/2022'));
        $this->delete_test_blacklist_date();
        $this->create_test_blacklist_gymnase();
        $this->assertTrue($this->match_manager->is_date_blacklisted('01/08/2022', 45));
        $this->delete_test_blacklist_gymnase();
        $this->assertFalse($this->match_manager->is_date_blacklisted('01/08/2022', 45));
    }

    /**
     * @throws Exception
     */
    public function test_is_last_match_same_home()
    {
        //20220828:PASS
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertFalse($this->match_manager->is_last_match_same_home($match['id_equipe_ext'], $match['id_equipe_dom']));
            $this->assertTrue($this->match_manager->is_last_match_same_home($match['id_equipe_dom'], $match['id_equipe_ext']));
        }
    }

    /**
     * @throws Exception
     */
    public function test_has_match()
    {
        //20220828:PASS
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertTrue($this->match_manager->has_match($match['id_equipe_dom'], $match['date_reception']));
        }
        $this->delete_test_matches();
        foreach ($matches as $match) {
            $this->assertFalse($this->match_manager->has_match($match['id_equipe_dom'], $match['date_reception']));
        }
    }

    /**
     * @throws Exception
     */
    public function test_delete_matches()
    {
        //20221005:PASS
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertTrue($this->match_manager->has_match($match['id_equipe_dom'], $match['date_reception']));
            $this->assertTrue($this->match_manager->has_match($match['id_equipe_ext'], $match['date_reception']));
        }
        $this->match_manager->delete_matches("code_competition = 'ut'");
        foreach ($matches as $match) {
            $this->assertFalse($this->match_manager->has_match($match['id_equipe_dom'], $match['date_reception']));
            $this->assertFalse($this->match_manager->has_match($match['id_equipe_ext'], $match['date_reception']));
        }
    }

    public function testGenerate_round_robin_rounds()
    {
        //20220828:PASS
        $rounds = $this->match_manager->generate_round_robin_rounds(8);
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
    private function delete_test_full_competition(): void
    {
        $this->sql_manager->execute("DELETE FROM competitions WHERE code_competition = 'ut'");
        $this->sql_manager->execute("DELETE FROM matches WHERE code_competition = 'ut'");
        $this->sql_manager->execute("DELETE FROM journees WHERE code_competition = 'ut'");
        $this->sql_manager->execute("DELETE FROM classements WHERE code_competition = 'ut'");
        $this->sql_manager->execute("DELETE FROM equipes WHERE code_competition = 'ut'");
        $this->sql_manager->execute("DELETE FROM clubs WHERE nom LIKE 'test club %'");
        $this->sql_manager->execute("DELETE FROM creneau 
                                            WHERE id_gymnase IN (SELECT id 
                                                                FROM gymnase 
                                                                WHERE nom LIKE 'test court %')");
        $this->sql_manager->execute("DELETE FROM gymnase WHERE nom LIKE 'test court %'");
    }

    /**
     * @throws Exception
     */
    private function get_test_day(): array
    {
        $day = new Day();
        $results = $day->getDays("j.code_competition = 'ut'");
        return $results[0];
    }

    /**
     * @throws Exception
     */
    private function get_test_teams(): array
    {
        $team = new Team();
        return $team->getTeams("e.code_competition = 'ut'");
    }

    /**
     * @throws Exception
     */
    private function get_test_matches()
    {
        return $this->match_manager->get_matches("m.code_competition = 'ut'");
    }

    /**
     * @throws Exception
     */
    public function test_generate_days()
    {
        //221022:PASS
        $competition_m = $this->competition->getCompetition('m');
        $competition_f = $this->competition->getCompetition('f');
        $competition_mo = $this->competition->getCompetition('mo');
        foreach (array(
                     $competition_m,
                     $competition_f,
                     $competition_mo,
                 ) as $comp) {
            $this->setTestDates($comp);
        }
        $this->competition->resetCompetition(implode(',', array(
            $competition_m['id'],
            $competition_f['id'],
            $competition_mo['id'],
        )));
        $this->day->generateDays(implode(',', array(
            $competition_m['id'],
            $competition_f['id'],
            $competition_mo['id'],
        )));
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_matches()
    {
        //221022:PASS
        $competitions = array(
            $this->competition->getCompetition('mo'),
            $this->competition->getCompetition('m'),
            $this->competition->getCompetition('f'),
        );
        $this->match_manager->delete_matches("match_status = 'NOT_CONFIRMED'");
        foreach ($competitions as $competition) {
            error_log($competition['libelle']);
            try {
                $this->match_manager->generate_matches($competition);
            } catch (Exception $exception) {
                error_log($exception->getMessage());
                continue;
            }
        }
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_matches_v2()
    {
        $this->match_manager->delete_matches("match_status = 'NOT_CONFIRMED'");
        //240221:PASS
        $competitions = array(
            $this->competition->getCompetition('mo'),
            $this->competition->getCompetition('m'),
            $this->competition->getCompetition('f'),
        );
        foreach ($competitions as $competition) {
            error_log($competition['libelle']);
            $message = "";
            $expected_matches[] = $this->match_manager->get_expected_matches($competition, null, $message);
            error_log($message);
        }
        $expected_matches = array_merge(...$expected_matches);
        foreach ($expected_matches as $index => $match) {
            error_log($index + 1 . " / " . count($expected_matches));
            $this->match_manager->insert_match($match);
        }
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_kh()
    {
        //230105:PASS
        $this->connect_as_admin();
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $this->setTestDates($competition_kh);
        $this->match_manager->generateAll($competition_kh['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_isoardi()
    {
        //230105:PASS
        $this->connect_as_admin();
        // test for isoardi, where registration is automatic
        $competition_mgr = new Competition();
        $competition_isoardi = $competition_mgr->getCompetition('c');
        $this->setTestDates($competition_isoardi);
        $this->match_manager->generateAll($competition_isoardi['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_m()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('m');
        $this->setTestDates($comp);
        $this->match_manager->generateAll($comp['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_with_params()
    {
        //230219:PASS
        $this->connect_as_admin();
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('m');
        $this->setTestDates($comp);
        $this->match_manager->generateAll($comp['id'], 'on', 'off', 'off');
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_championships()
    {
        //230219:PASS
        $this->connect_as_admin();
        $competition_mgr = new Competition();
        $codes = array(
            'mo',
            'm',
            'f',
        );
        foreach ($codes as $code) {
            $comp = $competition_mgr->getCompetition($code);
            try {
                $this->match_manager->generateAll($comp['id']);
            } catch (Exception $exception) {
                $this->assertEquals($exception->getCode(), 201);
                print_r($exception->getMessage());
            }
        }
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_adjust_home_away()
    {
        $competition_mgr = new Competition();
        $codes = array(
            'f',
        );
        foreach ($codes as $code) {
            $comp = $competition_mgr->getCompetition($code);
            $this->match_manager->adjust_home_away($comp);
        }
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_draw_matches()
    {
        $days = $this->day->get("j.code_competition = 'cf' AND j.numero = 1");
        $this->match_manager->delete_matches("code_competition = 'cf'");
        $this->match_manager->draw_matches('cf', '1', $days[0]['id']);
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws Exception
     */
    public function test_sign()
    {
        //20241205:PASS
        $this->create_test_full_competition();
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertEquals(0, $match['is_sign_team_dom']);
            $this->assertEquals(0, $match['is_sign_team_ext']);
            $this->assertEquals(0, $match['is_sign_match_dom']);
            $this->assertEquals(0, $match['is_sign_match_ext']);
            $this->assertEquals(0, $match['sheet_received']);
            // fill in players as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // fill in players as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // sign team sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exc) {
                $this->assertEquals("Signature prise en compte", $exc->getMessage());
            }
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals($email['to_email'], 'c@d.fr');
            $this->assertEquals($email['cc'], 'a@b.fr');
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_team_dom']);
            // sign team sheet as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exc) {
                $this->assertEquals("Signature prise en compte", $exc->getMessage());
            }
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals($email['to_email'], 'c@d.fr;a@b.fr');
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_team_ext']);
            // fill the score as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $this->match_manager->save(array(
                'id_match' => $match['id_match'],
                'set_1_dom' => 25,
                'set_1_ext' => 1,
                'set_2_dom' => 25,
                'set_2_ext' => 2,
                'set_3_dom' => 25,
                'set_3_ext' => 3,
                'code_match' => $match['code_match'],
            ));
            // sign match sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_match_sheet($match['id_match']);
            } catch (Exception $exc) {
                $this->assertEquals("Signature prise en compte", $exc->getMessage());
            }
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals('c@d.fr', $email['to_email']);
            $this->assertEquals('a@b.fr', $email['cc']);
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_match_dom']);
            // sign match sheet as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_match_sheet($match['id_match']);
            } catch (Exception $exc) {
                $this->assertEquals("Signature prise en compte", $exc->getMessage());
            }
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals($email['to_email'], 'c@d.fr;a@b.fr');
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_match_ext']);
            // check sheet_received
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['sheet_received']);
        }
    }

    /**
     * @throws Exception
     */
    public function test_sign_error_count()
    {
        //240218:PASS
        // error 1
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertEquals(0, $match['is_sign_team_dom']);
            $this->assertEquals(0, $match['is_sign_team_ext']);
            $this->assertEquals(0, $match['sheet_received']);
            // fill in players as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                if ($player['sexe'] == 'F') {
                    continue;
                }
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // fill in players as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // sign team sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: pas assez de filles à domicile !", $exception->getMessage());
            }
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: pas assez de filles à domicile !", $exception->getMessage());
            }
        }
        // error 2
        $this->create_test_full_competition();
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertEquals(0, $match['is_sign_team_dom']);
            $this->assertEquals(0, $match['is_sign_team_ext']);
            $this->assertEquals(0, $match['sheet_received']);
            // fill in players as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // fill in players as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                if ($player['sexe'] == 'F') {
                    continue;
                }
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // sign team sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: pas assez de filles à l'extérieur !", $exception->getMessage());
            }
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: pas assez de filles à l'extérieur !", $exception->getMessage());
            }
        }
        // error 3
        $this->create_test_full_competition();
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertEquals(0, $match['is_sign_team_dom']);
            $this->assertEquals(0, $match['is_sign_team_ext']);
            $this->assertEquals(0, $match['sheet_received']);
            // fill in players as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                if ($player['sexe'] == 'M') {
                    continue;
                }
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // fill in players as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // sign team sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: mixité obligatoire à domicile non respectée !", $exception->getMessage());
            }
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: mixité obligatoire à domicile non respectée !", $exception->getMessage());
            }
        }
        // error 4
        $this->create_test_full_competition();
        $matches = $this->get_test_matches();
        foreach ($matches as $match) {
            $this->assertEquals(0, $match['is_sign_team_dom']);
            $this->assertEquals(0, $match['is_sign_team_ext']);
            $this->assertEquals(0, $match['sheet_received']);
            // fill in players as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // fill in players as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $players = $this->players_manager->getMyPlayers();
            foreach ($players as $player) {
                if ($player['sexe'] == 'M') {
                    continue;
                }
                $this->match_manager->add_match_player($match['id_match'], $player['id']);
            }
            // sign team sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: mixité obligatoire à l'extérieur non respectée !", $exception->getMessage());
            }
            $this->connect_as_team_leader($match['id_equipe_ext']);
            try {
                $this->match_manager->sign_team_sheet($match['id_match']);
            } catch (Exception $exception) {
                $this->assertEquals("Il y a un souci dans la saisie: mixité obligatoire à l'extérieur non respectée !", $exception->getMessage());
            }
        }
    }

    /**
     * @throws Exception
     */
    public function test_check_team_allowed_to_ask_report()
    {
        $teams = $this->get_test_teams();
        $matches = $this->get_test_matches();
        $id_equipe = $teams[0]['id_equipe'];
        $code_match = $matches[0]['code_match'];
        $this->connect_as_team_leader($id_equipe);
        $this->match_manager->check_team_allowed_to_ask_report($id_equipe, $code_match);
        $sql = "UPDATE classements SET report_count = 1 WHERE id_equipe = ? AND code_competition = 'ut'";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $this->sql_manager->execute($sql, $bindings);
        try {
            $this->match_manager->check_team_allowed_to_ask_report($id_equipe, $code_match);
        } catch (Exception $exception) {
            $this->assertEquals("Demande refusée. Votre équipe a déjà demandé un report pour cette compétition.", $exception->getMessage());
        }
    }

    public function test_getMatchPlayers()
    {
        $results = $this->match_manager->getNotMatchPlayers(77866);
        print_r($results);
        $results = $this->players_manager->get_player(1925);
        print_r($results);
        $this->assertTrue(1 == 1);
    }


    public function test_get()
    {
        $results = $this->match_manager->getLastResults();
        print_r($results);
        $results = $this->match_manager->getWeekMatches();
        print_r($results);
        $this->assertTrue(1 == 1);
    }

    /**
     * Test flip_match : vérifie que les équipes sont inversées
     * et que la date correspond au créneau de la nouvelle équipe domicile
     * Teste toutes les combinaisons de créneaux (Lundi à Vendredi) pour les 2 équipes
     * @throws Exception
     */
    public function test_flip_match_all_days()
    {
        require_once __DIR__ . '/../classes/TimeSlot.php';
        $tsm = new TimeSlot();

        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
        $jour_to_offset = [
            'Lundi' => 0,
            'Mardi' => 1,
            'Mercredi' => 2,
            'Jeudi' => 3,
            'Vendredi' => 4,
        ];

        // Récupérer les équipes et le match de test
        $test_matchs = $this->get_test_matches();
        $match = $test_matchs[0];
        $id_match = $match['id_match'];
        $id_equipe_1 = $match['id_equipe_dom'];
        $id_equipe_2 = $match['id_equipe_ext'];

        echo "\n========================================\n";
        echo "TEST FLIP_MATCH - TOUTES COMBINAISONS\n";
        echo "========================================\n";

        $test_count = 0;
        $success_count = 0;

        foreach ($jours as $jour_equipe_1) {
            foreach ($jours as $jour_equipe_2) {
                $test_count++;

                // Mettre à jour les créneaux des équipes
                $this->sql_manager->execute(
                    "UPDATE creneau SET jour = ? WHERE id_equipe = ?",
                    [['type' => 's', 'value' => $jour_equipe_1], ['type' => 'i', 'value' => $id_equipe_1]]
                );
                $this->sql_manager->execute(
                    "UPDATE creneau SET jour = ? WHERE id_equipe = ?",
                    [['type' => 's', 'value' => $jour_equipe_2], ['type' => 'i', 'value' => $id_equipe_2]]
                );

                // Réinitialiser le match : équipe 1 à domicile, date sur le lundi de la semaine
                $date_lundi = (new DateTime())->modify('monday this week')->format('d/m/Y');
                $this->sql_manager->execute(
                    "UPDATE matches SET id_equipe_dom = ?, id_equipe_ext = ?, date_reception = STR_TO_DATE(?, '%d/%m/%Y') WHERE id_match = ?",
                    [
                        ['type' => 'i', 'value' => $id_equipe_1],
                        ['type' => 'i', 'value' => $id_equipe_2],
                        ['type' => 's', 'value' => $date_lundi],
                        ['type' => 'i', 'value' => $id_match]
                    ]
                );

                // Appeler flip_match
                $this->match_manager->flip_match($id_match);

                // Récupérer le match après flip
                $match_after = $this->match_manager->get_match($id_match);

                // Calculer la date attendue (basée sur le créneau de la nouvelle équipe domicile = équipe 2)
                $date_attendue = (new DateTime())->modify('monday this week')
                    ->modify("+{$jour_to_offset[$jour_equipe_2]} days")
                    ->format('d/m/Y');

                // Vérifications
                $equipes_inversees = ($match_after['id_equipe_dom'] == $id_equipe_2 && $match_after['id_equipe_ext'] == $id_equipe_1);
                $date_correcte = ($match_after['date_reception'] == $date_attendue);

                $status = ($equipes_inversees && $date_correcte) ? '✓' : '✗';
                if ($equipes_inversees && $date_correcte) {
                    $success_count++;
                }

                echo "\n[{$status}] Test {$test_count}: Équipe1={$jour_equipe_1}, Équipe2={$jour_equipe_2}\n";
                echo "    Date origine: {$date_lundi} | Date obtenue: {$match_after['date_reception']}\n";

                if (!$equipes_inversees) {
                    echo "    ERREUR: Équipes non inversées!\n";
                }
                if (!$date_correcte) {
                    echo "    ERREUR: Date incorrecte!\n";
                }

                $this->assertTrue($equipes_inversees, "Équipes non inversées pour {$jour_equipe_1}/{$jour_equipe_2}");
                $this->assertEquals($date_attendue, $match_after['date_reception'],
                    "Date incorrecte pour {$jour_equipe_1}/{$jour_equipe_2}");
            }
        }

        echo "\n========================================\n";
        echo "RÉSULTAT: {$success_count}/{$test_count} tests réussis\n";
        echo "========================================\n";
    }
}
