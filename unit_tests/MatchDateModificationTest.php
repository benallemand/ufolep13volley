<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Emails.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class MatchDateModificationTest extends UfolepTestCase
{
    private SqlManager $sql_manager;
    private MatchMgr $match_manager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sql_manager = new SqlManager();
        $this->match_manager = new MatchMgr();
        $this->create_test_competition_with_not_confirmed_match();
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $this->delete_test_competition();
        parent::tearDown();
    }

    /**
     * Connect as team leader with specific user ID
     */
    protected function connect_as_team_leader_with_id(mixed $id_equipe, mixed $id_user)
    {
        @session_start();
        $_SESSION['id_equipe'] = $id_equipe;
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = $id_user;
        $_SESSION['profile_name'] = 'RESPONSABLE_EQUIPE';
    }

    /**
     * @return void
     * @throws Exception
     */
    private function create_test_competition_with_not_confirmed_match(): void
    {
        // Create competition
        $id_competition = $this->sql_manager->execute("INSERT INTO competitions SET 
                               code_competition = 'dm',
                               libelle = 'date modification tests',
                               id_compet_maitre = 'dm',
                               start_date = CURRENT_DATE - INTERVAL 30 DAY");
        
        // Create limit date for competition (VARCHAR column, must be in d/m/Y format)
        $this->sql_manager->execute("INSERT INTO dates_limite SET 
                               code_competition = 'dm',
                               date_limite = DATE_FORMAT(CURRENT_DATE + INTERVAL 60 DAY, '%d/%m/%Y')");
        
        // Create day within competition period
        $id_day = $this->sql_manager->execute("INSERT INTO journees SET 
                               code_competition = 'dm',
                               numero = 1,
                               nommage = 'J1',
                               libelle = 'J1',
                               start_date = CURRENT_DATE - INTERVAL 20 DAY");
        
        // Create clubs
        $id_club1 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'dm test club 1'");
        $id_club2 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'dm test club 2'");
        
        // Create teams
        $id_team1 = $this->sql_manager->execute("INSERT INTO equipes SET 
                                                       code_competition = 'dm',
                                                       nom_equipe = 'dm test team 1',
                                                       id_club = $id_club1");
        $id_team2 = $this->sql_manager->execute("INSERT INTO equipes SET 
                                                       code_competition = 'dm',
                                                       nom_equipe = 'dm test team 2',
                                                       id_club = $id_club2");
        
        // Create rankings
        $this->sql_manager->execute("INSERT INTO classements SET 
                                                       code_competition = 'dm',
                                                       division = '1',
                                                       id_equipe = $id_team1,
                                                       rank_start = 1");
        $this->sql_manager->execute("INSERT INTO classements SET 
                                                       code_competition = 'dm',
                                                       division = '1',
                                                       id_equipe = $id_team2,
                                                       rank_start = 2");
        
        // Create gymnases
        $id_gymnasium1 = $this->sql_manager->execute("INSERT INTO gymnase SET 
                                                       nom = 'dm test gymnasium 1',
                                                       nb_terrain = 3");
        $id_gymnasium2 = $this->sql_manager->execute("INSERT INTO gymnase SET 
                                                       nom = 'dm test gymnasium 2',
                                                       nb_terrain = 3");
        
        // Create timeslots (creneaux) for both teams
        $this->sql_manager->execute("INSERT INTO creneau SET 
                                                       id_gymnase = $id_gymnasium1,
                                                       jour = 'Lundi',
                                                       heure = '20:00',
                                                       id_equipe = $id_team1");
        $this->sql_manager->execute("INSERT INTO creneau SET 
                                                       id_gymnase = $id_gymnasium2,
                                                       jour = 'Mardi',
                                                       heure = '20:00',
                                                       id_equipe = $id_team2");
        
        // Create test users for team leaders
        $profileId = $this->sql_manager->execute("SELECT id FROM profiles WHERE name = 'RESPONSABLE_EQUIPE'");
        if (is_array($profileId)) {
            $profileId = $profileId[0]['id'];
        }
        
        $userId1 = $this->sql_manager->execute("INSERT INTO comptes_acces SET login = 'dm_team_leader1', email = 'dm1@test.com', password_hash = 'x'");
        $userId2 = $this->sql_manager->execute("INSERT INTO comptes_acces SET login = 'dm_team_leader2', email = 'dm2@test.com', password_hash = 'x'");
        
        $this->sql_manager->execute("INSERT INTO users_profiles SET user_id = $userId1, profile_id = $profileId");
        $this->sql_manager->execute("INSERT INTO users_profiles SET user_id = $userId2, profile_id = $profileId");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId1, team_id = $id_team1");
        $this->sql_manager->execute("INSERT INTO users_teams SET user_id = $userId2, team_id = $id_team2");
        
        // Create NOT_CONFIRMED match
        $this->sql_manager->execute("INSERT INTO matches SET
                        code_match = 'DM001',
                        code_competition='dm',
                        division='1',
                        id_equipe_dom = $id_team1,
                        id_equipe_ext = $id_team2,
                        date_reception = CURRENT_DATE - INTERVAL 15 DAY,
                        id_journee = $id_day,
                        id_gymnasium = $id_gymnasium1,
                        date_original = CURRENT_DATE - INTERVAL 15 DAY,
                        match_status = 'NOT_CONFIRMED'");
    }

    /**
     * @return void
     * @throws Exception
     */
    private function delete_test_competition(): void
    {
        $this->sql_manager->execute("DELETE FROM matches WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM users_teams WHERE team_id IN (SELECT id_equipe FROM equipes WHERE code_competition = 'dm')");
        $this->sql_manager->execute("DELETE FROM users_profiles WHERE user_id IN (SELECT id FROM comptes_acces WHERE login LIKE 'dm_%')");
        $this->sql_manager->execute("DELETE FROM comptes_acces WHERE login LIKE 'dm_%'");
        $this->sql_manager->execute("DELETE FROM creneau WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE code_competition = 'dm')");
        $this->sql_manager->execute("DELETE FROM classements WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM equipes WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM journees WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM dates_limite WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM competitions WHERE code_competition = 'dm'");
        $this->sql_manager->execute("DELETE FROM clubs WHERE nom LIKE 'dm test club %'");
        $this->sql_manager->execute("DELETE FROM gymnase WHERE nom LIKE 'dm test gymnasium %'");
    }

    /**
     * @return array
     * @throws Exception
     */
    private function get_test_match(): array
    {
        $matches = $this->match_manager->get_matches("m.code_competition = 'dm' AND m.match_status = 'NOT_CONFIRMED'");
        if (empty($matches)) {
            throw new Exception("No test match found");
        }
        return $matches[0];
    }

    /**
     * @return array
     * @throws Exception
     */
    private function get_test_teams(): array
    {
        $team = new Team();
        return $team->getTeams("e.code_competition = 'dm'");
    }

    /**
     * @throws Exception
     */
    public function test_get_available_dates_for_match(): void
    {
        $match = $this->get_test_match();
        $teams = $this->get_test_teams();
        
        // Test should return available dates within competition period
        $available_dates = $this->match_manager->get_available_dates_for_match(
            $match['id_match'],
            false, // don't check opposite gymnasium
            false  // don't force date
        );
        
        $this->assertIsArray($available_dates);
        $this->assertNotEmpty($available_dates);
        
        // Each date should have required fields, match créneau day, and not be a holiday
        foreach ($available_dates as $date_info) {
            $this->assertArrayHasKey('date', $date_info);
            $this->assertArrayHasKey('available', $date_info);
            $this->assertArrayHasKey('gymnasium_available', $date_info);
            $this->assertArrayHasKey('teams_available', $date_info);
            $this->assertArrayHasKey('week_conflicts', $date_info);
            $this->assertArrayHasKey('matches_reception_day', $date_info);
            $this->assertArrayHasKey('is_holiday', $date_info);
            // All returned dates must be Mondays (team 1 plays on Lundi)
            $date = DateTime::createFromFormat('d/m/Y', $date_info['date']);
            $this->assertEquals(1, (int)$date->format('N'), "Date {$date_info['date']} should be a Monday");
            // No holidays/vacations in normal mode
            $this->assertFalse($date_info['is_holiday'], "Date {$date_info['date']} should not be a holiday");
        }
        
        // Force date mode should return all days including holidays
        $forced_dates = $this->match_manager->get_available_dates_for_match(
            $match['id_match'],
            false,
            true // force_date
        );
        $this->assertGreaterThan(count($available_dates), count($forced_dates),
            "Forced dates should return more dates than normal mode");
    }

    /**
     * @throws Exception
     */
    public function test_date_within_competition_period(): void
    {
        $match = $this->get_test_match();
        
        // Date before competition start should be invalid
        $date_before_start = date('d/m/Y', strtotime('-40 days'));
        $this->assertFalse($this->match_manager->is_date_within_competition_period(
            $date_before_start,
            $match['code_competition']
        ));
        
        // Date after competition end should be invalid  
        $date_after_end = date('d/m/Y', strtotime('+70 days'));
        $this->assertFalse($this->match_manager->is_date_within_competition_period(
            $date_after_end,
            $match['code_competition']
        ));
        
        // Date within competition period should be valid
        $date_within = date('d/m/Y', strtotime('+10 days'));
        $this->assertTrue($this->match_manager->is_date_within_competition_period(
            $date_within,
            $match['code_competition']
        ));
    }

    /**
     * @throws Exception
     */
    public function test_teams_availability_for_date(): void
    {
        $match = $this->get_test_match();
        $teams = $this->get_test_teams();
        
        // Teams should be available for a future date
        $future_date = date('d/m/Y', strtotime('+10 days'));
        $availability = $this->match_manager->check_teams_availability_for_date(
            $match['id_equipe_dom'],
            $match['id_equipe_ext'],
            $future_date
        );
        
        $this->assertIsArray($availability);
        $this->assertArrayHasKey('home_team_available', $availability);
        $this->assertArrayHasKey('away_team_available', $availability);
        $this->assertTrue($availability['home_team_available']);
        $this->assertTrue($availability['away_team_available']);
    }

    /**
     * @throws Exception
     */
    public function test_gymnasium_availability_for_date(): void
    {
        $match = $this->get_test_match();
        
        // Gymnasium should be available for a future date
        $future_date = date('d/m/Y', strtotime('+10 days'));
        $available = $this->match_manager->is_gymnasium_available_for_date(
            $match['id_gymnasium'],
            $future_date
        );
        
        $this->assertTrue($available);
    }

    /**
     * @throws Exception
     */
    public function test_week_conflicts_for_teams(): void
    {
        $match = $this->get_test_match();
        
        // Check for week conflicts
        $future_date = date('d/m/Y', strtotime('+10 days'));
        $conflicts = $this->match_manager->get_week_conflicts_for_teams(
            $match['id_equipe_dom'],
            $match['id_equipe_ext'],
            $future_date
        );
        
        $this->assertIsArray($conflicts);
        $this->assertArrayHasKey('home_team_conflicts', $conflicts);
        $this->assertArrayHasKey('away_team_conflicts', $conflicts);
    }

    /**
     * @throws Exception
     */
    public function test_modify_match_date_with_available_date(): void
    {
        $match = $this->get_test_match();
        $teams = $this->get_test_teams();
        
        // Get the actual user ID for the team leader
        $user_id = $this->sql_manager->execute("SELECT id FROM comptes_acces WHERE login = 'dm_team_leader1' LIMIT 1");
        $user_id = $user_id[0]['id'];
        
        // Connect as team leader to allow modification
        $this->connect_as_team_leader_with_id($match['id_equipe_dom'], $user_id);
        
        // Get available dates
        $available_dates = $this->match_manager->get_available_dates_for_match(
            $match['id_match'],
            false,
            false
        );
        
        // Find first available date
        $available_date = null;
        foreach ($available_dates as $date_info) {
            if ($date_info['available']) {
                $available_date = $date_info['date'];
                break;
            }
        }
        
        $this->assertNotNull($available_date, "No available date found for testing");
        
        // Modify match date
        $old_date = $match['date_reception'];
        $this->match_manager->modify_match_date(
            $match['id_match'],
            $available_date,
            $match['id_gymnasium'], // keep same gymnasium
            false, // don't invert reception
            null   // no comment
        );
        
        // Verify date was changed
        $updated_match = $this->match_manager->get_match($match['id_match']);
        $this->assertNotEquals($old_date, $updated_match['date_reception']);
        $this->assertEquals($available_date, $updated_match['date_reception']);
    }

    /**
     * @throws Exception
     */
    public function test_modify_match_date_with_opposite_gymnasium(): void
    {
        $match = $this->get_test_match();
        
        // Get the actual user ID for the team leader
        $user_id = $this->sql_manager->execute("SELECT id FROM comptes_acces WHERE login = 'dm_team_leader1' LIMIT 1");
        $user_id = $user_id[0]['id'];
        
        // Connect as team leader to allow modification
        $this->connect_as_team_leader_with_id($match['id_equipe_dom'], $user_id);
        
        // Get the opposite team's gymnasium
        $opposite_gymnasium = $this->sql_manager->execute("SELECT id_gymnase FROM creneau WHERE id_equipe = ? LIMIT 1", 
            array(array('type' => 'i', 'value' => $match['id_equipe_ext'])));
        
        // Ensure we have an opposite gymnasium for testing
        $this->assertNotEmpty($opposite_gymnasium, "Opposite team should have a gymnasium assigned");
        
        // Get available dates with opposite gymnasium option
        $available_dates = $this->match_manager->get_available_dates_for_match(
            $match['id_match'],
            true, // check opposite gymnasium
            false
        );
        
        // Find first available date
        $available_date = null;
        foreach ($available_dates as $date_info) {
            if ($date_info['available']) {
                $available_date = $date_info['date'];
                break;
            }
        }
        
        // If no available date, force one for testing
        if (!$available_date) {
            $available_date = date('d/m/Y', strtotime('+30 days'));
        }
        
        // Modify match date with opposite gymnasium
        $old_gymnasium = $match['id_gymnasium'];
        $old_home_team = $match['id_equipe_dom'];
        $old_away_team = $match['id_equipe_ext'];
        
        $this->match_manager->modify_match_date(
            $match['id_match'],
            $available_date,
            $opposite_gymnasium[0]['id_gymnase'],
            true, // invert reception
            null   // no comment
        );
        
        // Verify gymnasium was changed and reception was inverted
        $updated_match = $this->match_manager->get_match($match['id_match']);
        $this->assertNotEquals($old_gymnasium, $updated_match['id_gymnasium']);
        $this->assertEquals($old_away_team, $updated_match['id_equipe_dom']);
        $this->assertEquals($old_home_team, $updated_match['id_equipe_ext']);
    }

    /**
     * @throws Exception
     */
    public function test_modify_match_date_forced(): void
    {
        $match = $this->get_test_match();
        
        // Connect as admin to allow modification
        $this->connect_as_admin();
        
        // Force a date that might not be available
        $forced_date = date('d/m/Y', strtotime('+25 days'));
        $forced_gymnasium = $match['id_gymnasium'];
        $comment = "Date forcée pour test unitaire";
        
        $this->match_manager->modify_match_date(
            $match['id_match'],
            $forced_date,
            $forced_gymnasium,
            false,
            $comment
        );
        
        // Verify date and comment were set
        $updated_match = $this->match_manager->get_match($match['id_match']);
        $this->assertEquals($forced_date, $updated_match['date_reception']);
        $this->assertEquals($forced_gymnasium, $updated_match['id_gymnasium']);
        $this->assertStringContainsString($comment, $updated_match['note']);
    }

    /**
     * @throws Exception
     */
    public function test_authorization_for_date_modification(): void
    {
        $match = $this->get_test_match();
        
        // Get the actual user ID for the team leader
        $user_id = $this->sql_manager->execute("SELECT id FROM comptes_acces WHERE login = 'dm_team_leader1' LIMIT 1");
        $user_id = $user_id[0]['id'];
        
        // Team leader should be able to modify their own match
        $this->connect_as_team_leader_with_id($match['id_equipe_dom'], $user_id);
        $this->assertTrue($this->match_manager->is_match_date_modification_allowed($match['id_match']));
        
        // Team leader should NOT be able to modify other teams' matches
        // Create a real team for the test
        $id_club3 = $this->sql_manager->execute("INSERT INTO clubs SET nom = 'dm test club 3'");
        $id_team3 = $this->sql_manager->execute("INSERT INTO equipes SET 
                                                       code_competition = 'dm',
                                                       nom_equipe = 'dm test team 3',
                                                       id_club = $id_club3");
        $this->sql_manager->execute("INSERT INTO classements SET 
                                                       code_competition = 'dm',
                                                       division = '1',
                                                       id_equipe = $id_team3,
                                                       rank_start = 3");
        
        $other_team_match = $this->sql_manager->execute("INSERT INTO matches SET
                        code_match = 'DM002',
                        code_competition='dm',
                        division='1',
                        id_equipe_dom = $id_team3,
                        id_equipe_ext = $id_team3,
                        date_reception = CURRENT_DATE - INTERVAL 10 DAY,
                        match_status = 'NOT_CONFIRMED'");
        
        $this->assertFalse($this->match_manager->is_match_date_modification_allowed($other_team_match));
        
        // Admin should be able to modify any match
        $this->connect_as_admin();
        $this->assertTrue($this->match_manager->is_match_date_modification_allowed($match['id_match']));
        $this->assertTrue($this->match_manager->is_match_date_modification_allowed($other_team_match));
        
        // Clean up
        $this->sql_manager->execute("DELETE FROM matches WHERE id_match = ?", 
            array(array('type' => 'i', 'value' => $other_team_match)));
        $this->sql_manager->execute("DELETE FROM classements WHERE id_equipe = $id_team3");
        $this->sql_manager->execute("DELETE FROM equipes WHERE id_equipe = $id_team3");
        $this->sql_manager->execute("DELETE FROM clubs WHERE id = $id_club3");
    }

    /**
     * @throws Exception
     */
    public function test_only_not_confirmed_matches_can_be_modified(): void
    {
        $match = $this->get_test_match();
        
        // Get the actual user ID for the team leader
        $user_id = $this->sql_manager->execute("SELECT id FROM comptes_acces WHERE login = 'dm_team_leader1' LIMIT 1");
        $user_id = $user_id[0]['id'];
        
        // Connect as team leader to allow modification
        $this->connect_as_team_leader_with_id($match['id_equipe_dom'], $user_id);
        
        // NOT_CONFIRMED match should be modifiable
        $this->assertTrue($this->match_manager->is_match_date_modification_allowed($match['id_match']));
        
        // Change match status to CONFIRMED
        $this->sql_manager->execute("UPDATE matches SET match_status = 'CONFIRMED' WHERE id_match = ?", 
            array(array('type' => 'i', 'value' => $match['id_match'])));
        
        // CONFIRMED match should NOT be modifiable
        $this->assertFalse($this->match_manager->is_match_date_modification_allowed($match['id_match']));
        
        // Reset to NOT_CONFIRMED
        $this->sql_manager->execute("UPDATE matches SET match_status = 'NOT_CONFIRMED' WHERE id_match = ?", 
            array(array('type' => 'i', 'value' => $match['id_match'])));
    }
}
