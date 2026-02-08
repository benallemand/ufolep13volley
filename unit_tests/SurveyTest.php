<?php
require_once __DIR__ . '/../classes/MatchMgr.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

class SurveyTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private int $test_user_id;
    private int $test_match_id;

    private function create_test_data(): void
    {
        $this->delete_test_data();
        $this->test_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'survey_test_user', email = 'survey_test@test.fr', password_hash = MD5('test')");
        $this->sql->execute(
            "INSERT INTO users_profiles SET user_id = ?, profile_id = (SELECT id FROM profiles WHERE name = 'ADMINISTRATEUR')",
            array(array('type' => 'i', 'value' => $this->test_user_id)));
        $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'survey test club 1'");
        $id_club2 = $this->sql->execute("INSERT INTO clubs SET nom = 'survey test club 2'");
        $id_team1 = $this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'ut', nom_equipe = 'survey test team 1', id_club = $id_club");
        $id_team2 = $this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'ut', nom_equipe = 'survey test team 2', id_club = $id_club2");
        $this->sql->execute(
            "INSERT INTO users_teams SET user_id = ?, team_id = ?",
            array(
                array('type' => 'i', 'value' => $this->test_user_id),
                array('type' => 'i', 'value' => $id_team1),
            ));
        $id_day = $this->sql->execute(
            "INSERT INTO journees SET code_competition = 'ut', numero = 99, nommage = 'SURVEY_TEST', libelle = 'Survey Test', start_date = CURRENT_DATE");
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'survey test court'");
        $this->test_match_id = $this->sql->execute(
            "INSERT INTO matches SET
                code_match = 'SURVEY_UT001',
                code_competition = 'ut',
                division = '1',
                id_equipe_dom = $id_team1,
                id_equipe_ext = $id_team2,
                date_reception = CURRENT_DATE,
                id_journee = $id_day,
                id_gymnasium = $id_court,
                date_original = CURRENT_DATE,
                match_status = 'CONFIRMED'");
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM survey WHERE id_match IN (SELECT id FROM matches WHERE code_match = 'SURVEY_UT001')");
        $this->sql->execute("DELETE FROM matches WHERE code_match = 'SURVEY_UT001'");
        $this->sql->execute("DELETE FROM journees WHERE nommage = 'SURVEY_TEST'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'survey_test_user')");
        $this->sql->execute("DELETE FROM users_profiles WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = 'survey_test_user')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = 'survey_test_user'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'survey test team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'survey test club %'");
        $this->sql->execute("DELETE FROM creneau WHERE id_gymnase IN (SELECT id FROM gymnase WHERE nom = 'survey test court')");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'survey test court'");
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->create_test_data();
        @session_start();
        $_SESSION['id_equipe'] = null;
        $_SESSION['login'] = 'survey_test_user';
        $_SESSION['id_user'] = $this->test_user_id;
        $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    }

    public function test_save_survey_rejects_on_time_above_10()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 11,
            spirit: 5,
            referee: 5,
            catering: 5,
            global: 5
        );
    }

    public function test_save_survey_rejects_negative_on_time()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: -1,
            spirit: 5,
            referee: 5,
            catering: 5,
            global: 5
        );
    }

    public function test_save_survey_rejects_spirit_above_10()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 5,
            spirit: 15,
            referee: 5,
            catering: 5,
            global: 5
        );
    }

    public function test_save_survey_rejects_referee_above_10()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 5,
            spirit: 5,
            referee: 12,
            catering: 5,
            global: 5
        );
    }

    public function test_save_survey_rejects_catering_above_10()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 5,
            spirit: 5,
            referee: 5,
            catering: 11,
            global: 5
        );
    }

    public function test_save_survey_rejects_global_above_10()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 5,
            spirit: 5,
            referee: 5,
            catering: 5,
            global: 11
        );
    }

    public function test_save_survey_accepts_value_10()
    {
        $result = $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 10,
            spirit: 10,
            referee: 10,
            catering: 10,
            global: 10
        );
        $this->assertNotNull($result);
    }

    public function test_save_survey_accepts_value_0()
    {
        $result = $this->match_manager->save_survey(
            id_match: $this->test_match_id,
            on_time: 0,
            spirit: 0,
            referee: 0,
            catering: 0,
            global: 0
        );
        $this->assertNotNull($result);
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }
}
