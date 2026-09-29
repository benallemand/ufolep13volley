<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #351 — sondages lisibles sans connexion, injections SQL dans la
 * recherche de renforts et dans le sondage.
 *
 * Deux matchs de test : A (équipe 1 contre équipe 2), dont l'équipe 1 est
 * celle du responsable connecté, et B (équipe 3 contre équipe 4), qui ne le
 * concerne pas. Un joueur de l'équipe 3 sert de renfort à trouver.
 */
class SurveyAndReinforcementSecurityTest extends UfolepTestCase
{
    private MatchMgr $match_manager;
    private int $leader_user_id;
    private int $other_user_id;
    private int $id_team1;
    private int $match_a;
    private int $match_b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->match_manager = new MatchMgr();
        $this->delete_test_data();
        $this->sql->execute("INSERT INTO competitions SET code_competition = 'uq', libelle = 'issue351 tests', id_compet_maitre = 'uq'");
        $this->leader_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue351_leader', email = 'issue351_leader@test.fr', password_hash = MD5('test')");
        $this->other_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue351_other', email = 'issue351_other@test.fr', password_hash = MD5('test')");
        $teams = array();
        for ($i = 1; $i <= 4; $i++) {
            $id_club = $this->sql->execute("INSERT INTO clubs SET nom = 'issue351 club $i'");
            $teams[$i] = $this->sql->execute(
                "INSERT INTO equipes SET code_competition = 'uq', nom_equipe = 'issue351 team $i', id_club = $id_club");
        }
        $this->id_team1 = $teams[1];
        $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?", array(
            array('type' => 'i', 'value' => $this->leader_user_id),
            array('type' => 'i', 'value' => $this->id_team1),
        ));
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue351 court'");
        $this->match_a = $this->create_match('ISSUE351_A', $teams[1], $teams[2], $id_court);
        $this->match_b = $this->create_match('ISSUE351_B', $teams[3], $teams[4], $id_court);
        $id_player = $this->sql->execute(
            "INSERT INTO joueurs SET nom = 'Zyxissue', prenom = 'Renfort', sexe = 'M', id_club = (SELECT id_club FROM equipes WHERE id_equipe = ?)",
            array(array('type' => 'i', 'value' => $teams[3])));
        $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?", array(
            array('type' => 'i', 'value' => $id_player),
            array('type' => 'i', 'value' => $teams[3]),
        ));
    }

    private function create_match(string $code, int $dom, int $ext, int $id_court): int
    {
        return $this->sql->execute(
            "INSERT INTO matches SET code_match = ?, code_competition = 'uq', division = '1',
                id_equipe_dom = ?, id_equipe_ext = ?, date_reception = CURRENT_DATE,
                id_gymnasium = ?, date_original = CURRENT_DATE, match_status = 'CONFIRMED'",
            array(
                array('type' => 's', 'value' => $code),
                array('type' => 'i', 'value' => $dom),
                array('type' => 'i', 'value' => $ext),
                array('type' => 'i', 'value' => $id_court),
            ));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM survey WHERE id_match IN (SELECT id_match FROM matches WHERE code_match LIKE 'ISSUE351_%')");
        $this->sql->execute("DELETE FROM matches WHERE code_match LIKE 'ISSUE351_%'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Zyxissue')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Zyxissue'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login LIKE 'issue351_%')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login LIKE 'issue351_%'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue351 team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue351 club %'");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue351 court'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'uq'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function connect_as_leader(): void
    {
        $this->connect_as_team_leader($this->id_team1);
        $_SESSION['login'] = 'issue351_leader';
        $_SESSION['id_user'] = $this->leader_user_id;
    }

    private function ratings(): array
    {
        // échelle -2..+2 depuis #350
        return array('on_time' => 1, 'spirit' => 1, 'referee' => 1, 'catering' => 1, 'global' => 1);
    }

    // --- Sondage : lecture

    public function test_get_survey_refuse_un_visiteur_non_connecte(): void
    {
        $_SESSION = [];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('non connecté');
        $this->match_manager->get_survey();
    }

    public function test_get_survey_sans_match_est_reserve_a_l_admin(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Seul un admin");
        $this->match_manager->get_survey();
    }

    public function test_get_survey_refuse_le_match_d_une_autre_equipe(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("match de votre équipe");
        $this->match_manager->get_survey($this->match_b);
    }

    public function test_get_survey_refuse_un_identifiant_non_numerique(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Identifiant de match invalide");
        $this->match_manager->get_survey($this->match_a . ' OR 1=1');
    }

    public function test_get_survey_renvoie_le_sondage_de_l_utilisateur_pour_son_match(): void
    {
        $this->connect_as_leader();
        $survey = $this->match_manager->get_survey($this->match_a);
        self::assertNull($survey['id']);
        self::assertSame($this->leader_user_id, (int)$survey['user_id']);
        self::assertSame($this->match_a, (int)$survey['id_match']);
    }

    // --- Sondage : écriture

    public function test_save_survey_refuse_le_match_d_une_autre_equipe(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("match de votre équipe");
        $this->match_manager->save_survey($this->match_b, ...array_values($this->ratings()));
    }

    public function test_save_survey_ne_reecrit_pas_le_sondage_d_un_autre_compte(): void
    {
        $id_other_survey = $this->sql->execute(
            "INSERT INTO survey SET user_id = ?, id_match = ?, on_time = 1, spirit = 1, referee = 1, catering = 1, global = -1",
            array(
                array('type' => 'i', 'value' => $this->other_user_id),
                array('type' => 'i', 'value' => $this->match_a),
            ));
        $this->connect_as_leader();
        try {
            $this->match_manager->save_survey($this->match_a, ...array_values($this->ratings()), id: $id_other_survey);
            self::fail("Le sondage d'un autre compte ne doit pas pouvoir être réécrit");
        } catch (Exception $e) {
            self::assertStringContainsString('ne vous appartient pas', $e->getMessage());
        }
        $row = $this->sql->execute("SELECT user_id, global FROM survey WHERE id = ?",
            array(array('type' => 'i', 'value' => $id_other_survey)));
        self::assertSame($this->other_user_id, (int)$row[0]['user_id']);
        self::assertSame(-1, (int)$row[0]['global']);
    }

    public function test_save_survey_fonctionne_pour_son_propre_match(): void
    {
        $this->connect_as_leader();
        $this->match_manager->save_survey($this->match_a, ...array_values($this->ratings()));
        $survey = $this->match_manager->get_survey($this->match_a);
        self::assertNotNull($survey['id']);
        self::assertSame(1, (int)$survey['global']);
        // et la mise à jour de son propre sondage reste possible
        $this->match_manager->save_survey($this->match_a, 1, 1, 1, 1, 2, id: $survey['id']);
        self::assertSame(2, (int)$this->match_manager->get_survey($this->match_a)['global']);
    }

    // --- Renforts

    public function test_get_reinforcement_players_trouve_un_joueur_d_une_autre_equipe(): void
    {
        $this->connect_as_leader();
        $found = $this->match_manager->getReinforcementPlayers($this->match_a, 'zyxiss');
        self::assertCount(1, $found);
        self::assertStringContainsString('ZYXISSUE', $found[0]['full_name']);
    }

    public function test_get_reinforcement_players_neutralise_une_charge_d_injection(): void
    {
        $this->connect_as_leader();
        // Concaténée, la charge rendait le filtre toujours vrai : tous les joueurs remontaient.
        self::assertSame(array(), $this->match_manager->getReinforcementPlayers($this->match_a, "zzz' OR '1'='1"));
        // `%` et `_` sont des caractères ordinaires, pas des jokers.
        self::assertSame(array(), $this->match_manager->getReinforcementPlayers($this->match_a, '%'));
        self::assertSame(array(), $this->match_manager->getReinforcementPlayers($this->match_a, 'Zyx_ssue'));
    }

    public function test_get_reinforcement_players_refuse_un_identifiant_non_numerique(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Identifiant de match invalide");
        $this->match_manager->getReinforcementPlayers($this->match_a . ') OR (1=1', 'zyxiss');
    }

    public function test_get_reinforcement_players_refuse_le_match_d_une_autre_equipe(): void
    {
        $this->connect_as_leader();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("match de votre équipe");
        $this->match_manager->getReinforcementPlayers($this->match_b, 'zyxiss');
    }

    public function test_get_reinforcement_players_reste_ouvert_a_l_admin(): void
    {
        // l'admin n'est dans aucune équipe du match, il cherche quand même
        $this->connect_as_admin();
        self::assertCount(1, $this->match_manager->getReinforcementPlayers($this->match_a, 'zyxiss'));
    }
}
