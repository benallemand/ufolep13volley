<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/Day.php';
require_once __DIR__ . '/../classes/Competition.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Emails.php';

use PHPUnit\Framework\TestCase;

class MatchManagerTest extends TestCase
{
    private SqlManager $sql_manager;
    private MatchMgr $match_manager;
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
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team1 FROM joueurs LIMIT 0,10");
        $this->sql_manager->execute("INSERT INTO joueur_equipe(id_joueur, id_equipe)  SELECT id, $id_team2 FROM joueurs LIMIT 10,10");
        // add team leader emails
        $this->sql_manager->execute("UPDATE joueur_equipe SET is_leader = 1 WHERE id_joueur = 1");
        $this->sql_manager->execute("UPDATE joueur_equipe SET is_leader = 1 WHERE id_joueur = 13");
        $this->sql_manager->execute("UPDATE joueurs SET email = 'a@b.fr' WHERE id = 1");
        $this->sql_manager->execute("UPDATE joueurs SET email = 'c@d.fr' WHERE id = 13");
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
        $this->emails = new Emails();
        $this->create_test_full_competition();
    }

    /**
     * @throws Exception
     */
    public function test_get_count_matches_per_day()
    {
        //20220828:PASS
        $week = $this->get_test_day();
        $this->assertEquals(1,
            $this->match_manager->get_count_matches_per_day('ut', '1', $week['id']));
        $this->delete_test_matches();
        $this->assertEquals(0,
            $this->match_manager->get_count_matches_per_day('ut', '1', $week['id']));
    }

    /**
     * @throws Exception
     */
    public function test_is_team_busy_for_week()
    {
        //20220828:PASS
        $week = $this->get_test_day();
        $teams = $this->get_test_teams();
        foreach ($teams as $team) {
            $this->assertTrue($this->match_manager->is_team_busy_for_week($week['id'], $team['id_equipe']));
        }
        $this->delete_test_matches();
        foreach ($teams as $team) {
            $this->assertFalse($this->match_manager->is_team_busy_for_week($week['id'], $team['id_equipe']));
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
    private function delete_test_full_competition()
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

    public function test_generate_days()
    {
        //221022:PASS
        $competition_mgr = new Competition();
        $day_mgr = new Day();
        $competition_m = $competition_mgr->getCompetition('m');
        $competition_f = $competition_mgr->getCompetition('f');
        $competition_mo = $competition_mgr->getCompetition('mo');
        $competition_mgr->resetCompetition(implode(',', array(
            $competition_m['id'],
            $competition_f['id'],
            $competition_mo['id'],
        )));
        $day_mgr->generateDays(implode(',', array(
            $competition_m['id'],
            $competition_f['id'],
            $competition_mo['id'],
        )));
    }

    /**
     * @throws Exception
     */
    public function test_generate_matches()
    {
        //221022:PASS
        $competition_mgr = new Competition();
        $competitions = array(
            $competition_mgr->getCompetition('mo'),
            $competition_mgr->getCompetition('m'),
            $competition_mgr->getCompetition('f'),
        );
        $this->match_manager->delete_matches("match_status = 'NOT_CONFIRMED'");
        foreach ($competitions as $competition) {
            error_log($competition['libelle']);
            $this->match_manager->generate_matches($competition, false, false);
        }
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_kh()
    {
        //230105:PASS
        $competition_mgr = new Competition();
        $competition_kh = $competition_mgr->getCompetition('kh');
        $this->match_manager->generateAll($competition_kh['id']);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_isoardi()
    {
        //230105:PASS
        // test for isoardi, where registration is automatic
        $competition_mgr = new Competition();
        $competition_isoardi = $competition_mgr->getCompetition('c');
        $this->match_manager->generateAll($competition_isoardi['id']);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_m()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        $comp = $competition_mgr->getCompetition('m');
        $this->match_manager->generateAll($comp['id']);
    }

    /**
     * @throws Exception
     */
    public function test_generate_all_championships()
    {
        //230219:PASS
        $competition_mgr = new Competition();
        $codes = array(
            'mo',
            'm',
            'f',
        );
        foreach ($codes as $code) {
            $comp = $competition_mgr->getCompetition($code);
            $this->match_manager->generateAll($comp['id']);
        }
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
    }

    /**
     * @throws Exception
     */
    public function test_sign()
    {
        //20230223:PASS
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
            $this->match_manager->sign_team_sheet($match['id_match']);
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals($email['to_email'], 'c@d.fr');
            $this->assertEquals($email['cc'], 'a@b.fr');
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_team_dom']);
            // sign team sheet as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $this->match_manager->sign_team_sheet($match['id_match']);
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
                'score_equipe_dom' => 3,
                'score_equipe_ext' => 0,
                'code_match' => $match['code_match'],
            ));
            // sign match sheet as dom
            $this->connect_as_team_leader($match['id_equipe_dom']);
            $this->match_manager->sign_match_sheet($match['id_match']);
            // check last email
            $email = $this->emails->get_last();
            $this->assertEquals($email['to_email'], 'c@d.fr');
            $this->assertEquals($email['cc'], 'a@b.fr');
            // check signed
            $match = $this->match_manager->get_match($match['id_match']);
            $this->assertEquals(1, $match['is_sign_match_dom']);
            // sign match sheet as ext
            $this->connect_as_team_leader($match['id_equipe_ext']);
            $this->match_manager->sign_match_sheet($match['id_match']);
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

    private function connect_as_team_leader(mixed $id_equipe)
    {
        @session_start();
        $_SESSION['id_equipe'] = $id_equipe;
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = 1;
        $_SESSION['profile_name'] = 'RESPONSABLE_EQUIPE';
    }

}
