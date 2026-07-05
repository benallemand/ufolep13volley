<?php
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

/**
 * Issue #249 — inscriptions réservées aux responsables de club, workflow
 * demande (club, statut PENDING) / validation (admin, statut VALIDATED) /
 * engagement (admin, set_up_season sur les seules inscriptions validées).
 *
 * Toutes les données de test vivent dans la compétition dédiée 'rt'
 * (fenêtre d'inscription ouverte) — aucune compétition réelle n'est touchée.
 */
class RegisterTest extends UfolepTestCase
{
    private ?int $id_competition = null;
    private ?int $id_club_1 = null;
    private ?int $id_club_2 = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        // compétition de test avec fenêtre d'inscription ouverte
        $this->id_competition = (int)$this->sql->execute(
            "INSERT INTO competitions SET
                code_competition = 'rt',
                libelle = 'register tests',
                id_compet_maitre = 'rt',
                start_date = CURRENT_DATE + INTERVAL 30 DAY,
                start_register_date = CURRENT_DATE - INTERVAL 10 DAY,
                limit_register_date = CURRENT_DATE + INTERVAL 10 DAY");
        $this->id_club_1 = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'rt club 1'");
        $this->id_club_2 = (int)$this->sql->execute("INSERT INTO clubs SET nom = 'rt club 2'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM emails WHERE to_email LIKE 'rt_%@ufolep.test' OR body LIKE '%RT Team%'");
        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'rt'");
        $this->sql->execute("DELETE FROM users_teams WHERE team_id IN (SELECT id_equipe FROM equipes WHERE code_competition = 'rt')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE email LIKE 'rt_%@ufolep.test'");
        $this->sql->execute("DELETE FROM creneau WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE code_competition = 'rt')");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_equipe IN (SELECT id_equipe FROM equipes WHERE code_competition = 'rt')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'RTLEADER'");
        $this->sql->execute("DELETE FROM equipes WHERE code_competition = 'rt'");
        $this->sql->execute("DELETE FROM register WHERE new_team_name LIKE 'RT Team%'");
        $this->sql->execute("DELETE FROM competitions WHERE code_competition = 'rt'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'rt club %'");
    }

    private function insert_registration(int $id_club, string $status, string $team_name): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO register SET
                new_team_name = '$team_name',
                id_club = $id_club,
                id_competition = $this->id_competition,
                leader_name = 'RTLEADER',
                leader_first_name = 'Test',
                leader_email = 'rt_leader@ufolep.test',
                leader_phone = '0600000000',
                status = '$status'");
    }

    private function call_register(array $overrides = []): void
    {
        $defaults = array(
            'new_team_name' => 'RT Team A',
            'id_club' => $this->id_club_1,
            'id_competition' => $this->id_competition,
            'old_team_id' => null,
            'leader_name' => 'RTLEADER',
            'leader_first_name' => 'Test',
            'leader_email' => 'rt_leader@ufolep.test',
            'leader_phone' => '0600000000',
            'id_court_1' => null,
            'day_court_1' => null,
            'hour_court_1' => null,
            'id_court_2' => null,
            'day_court_2' => null,
            'hour_court_2' => null,
            'remarks' => 'test',
        );
        $params = array_merge($defaults, $overrides);
        (new Register())->register(...$params);
    }

    // ---- Étape 1 : la demande est réservée aux responsables de club ---------

    public function test_register_refused_when_not_connected()
    {
        $this->expectExceptionMessage("Seuls les responsables de club peuvent gérer les inscriptions !");
        $this->call_register();
    }

    public function test_register_refused_for_simple_team_leader()
    {
        $this->connect_as_team_leader(1);
        $this->expectExceptionMessage("Seuls les responsables de club peuvent gérer les inscriptions !");
        $this->call_register();
    }

    public function test_club_leader_creates_pending_registration_with_forced_club()
    {
        $this->connect_as_club_leader($this->id_club_1);
        try {
            // id_club falsifié vers le club 2 : le backend doit forcer le club de session
            $this->call_register(['id_club' => $this->id_club_2]);
            $this->fail("Une création réussie doit lever l'exception 201 (message de confirmation)");
        } catch (Exception $e) {
            $this->assertEquals(201, $e->getCode());
        }
        $rows = $this->sql->execute("SELECT * FROM register WHERE new_team_name = 'RT Team A'");
        $this->assertCount(1, $rows);
        $this->assertEquals($this->id_club_1, (int)$rows[0]['id_club']);
        $this->assertEquals('PENDING', $rows[0]['status']);
    }

    public function test_club_leader_updates_own_pending_registration()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team B');
        $this->connect_as_club_leader($this->id_club_1);
        $this->call_register(['id' => $id, 'new_team_name' => 'RT Team B', 'remarks' => 'modifié par le club']);
        $rows = $this->sql->execute("SELECT * FROM register WHERE id = $id");
        $this->assertEquals('modifié par le club', $rows[0]['remarks']);
        $this->assertEquals('PENDING', $rows[0]['status']);
    }

    public function test_club_leader_cannot_update_validated_registration()
    {
        $id = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team C');
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectExceptionMessage("Cette inscription a été validée, elle n'est plus modifiable !");
        $this->call_register(['id' => $id, 'new_team_name' => 'RT Team C']);
    }

    public function test_club_leader_cannot_update_other_club_registration()
    {
        $id = $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team D');
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectException(Exception::class);
        $this->call_register(['id' => $id, 'new_team_name' => 'RT Team D']);
    }

    public function test_admin_can_register_for_any_club()
    {
        $this->connect_as_admin();
        try {
            $this->call_register(['id_club' => $this->id_club_2, 'new_team_name' => 'RT Team E']);
            $this->fail("Une création réussie doit lever l'exception 201");
        } catch (Exception $e) {
            $this->assertEquals(201, $e->getCode());
        }
        $rows = $this->sql->execute("SELECT * FROM register WHERE new_team_name = 'RT Team E'");
        $this->assertEquals($this->id_club_2, (int)$rows[0]['id_club']);
    }

    // ---- Étape 2 : validation par l'admin -----------------------------------

    public function test_admin_validates_then_unvalidates()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team F');
        $this->connect_as_admin();
        $register = new Register();
        $register->validateRegistration($id);
        $row = $this->sql->execute("SELECT status, validation_date FROM register WHERE id = $id")[0];
        $this->assertEquals('VALIDATED', $row['status']);
        $this->assertNotNull($row['validation_date']);
        $register->unvalidateRegistration($id);
        $row = $this->sql->execute("SELECT status, validation_date FROM register WHERE id = $id")[0];
        $this->assertEquals('PENDING', $row['status']);
        $this->assertNull($row['validation_date']);
    }

    public function test_validate_refused_for_club_leader()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team G');
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectException(Exception::class);
        (new Register())->validateRegistration($id);
    }

    // ---- Consultation et suppression par le club ----------------------------

    public function test_getMyClubRegistrations_returns_only_own_club()
    {
        $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team H');
        $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team I');
        $this->connect_as_club_leader($this->id_club_1);
        $rows = (new Register())->getMyClubRegistrations();
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertEquals($this->id_club_1, (int)$row['id_club']);
            $this->assertArrayHasKey('status', $row);
        }
    }

    public function test_delete_own_pending_registration()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team J');
        $this->connect_as_club_leader($this->id_club_1);
        (new Register())->deleteMyClubRegistration($id);
        $this->assertCount(0, $this->sql->execute("SELECT id FROM register WHERE id = $id"));
    }

    public function test_delete_refused_on_validated_registration()
    {
        $id = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team K');
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectExceptionMessage("Cette inscription a été validée, elle n'est plus modifiable !");
        (new Register())->deleteMyClubRegistration($id);
    }

    public function test_delete_refused_on_other_club_registration()
    {
        $id = $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team L');
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectException(Exception::class);
        (new Register())->deleteMyClubRegistration($id);
    }

    // ---- Réengagement : pré-remplissage depuis l'équipe existante -----------

    public function test_load_register_for_my_club_returns_prefill()
    {
        $id_team = (int)$this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'rt', nom_equipe = 'RT Team Old', id_club = $this->id_club_1");
        $this->connect_as_club_leader($this->id_club_1);
        $prefill = (new Team())->load_register_for_my_club($id_team);
        $this->assertIsArray($prefill);
        $this->assertArrayHasKey('leader_name', $prefill);
        $this->assertArrayHasKey('id_court_1', $prefill);
    }

    public function test_load_register_for_my_club_refused_for_foreign_team()
    {
        $id_team = (int)$this->sql->execute(
            "INSERT INTO equipes SET code_competition = 'rt', nom_equipe = 'RT Team Old', id_club = $this->id_club_2");
        $this->connect_as_club_leader($this->id_club_1);
        $this->expectExceptionMessage("Cette équipe n'appartient pas à votre club !");
        (new Team())->load_register_for_my_club($id_team);
    }

    // ---- Étape 3 : l'engagement n'embarque que les validées -----------------

    public function test_set_up_season_only_creates_teams_for_validated()
    {
        $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team M');
        $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team N');
        $this->connect_as_admin();
        (new Register())->set_up_season((string)$this->id_competition);
        $teams = array_column(
            $this->sql->execute("SELECT nom_equipe FROM equipes WHERE code_competition = 'rt'"),
            'nom_equipe');
        $this->assertContains('RT Team M', $teams, "L'inscription validée doit être engagée");
        $this->assertNotContains('RT Team N', $teams, "L'inscription en attente ne doit PAS être engagée");
    }

    public function test_get_pending_registrations_only_returns_validated()
    {
        // "pending" au sens engagement : validée mais sans division/rang
        $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team O');
        $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team P');
        $this->connect_as_admin();
        $rows = (new Register())->get_pending_registrations($this->id_competition);
        $names = array_column($rows, 'new_team_name');
        $this->assertContains('RT Team O', $names);
        $this->assertNotContains('RT Team P', $names);
    }

    public function test_get_2nd_half_registrations_only_returns_validated()
    {
        $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team Q');
        $this->insert_registration($this->id_club_2, 'PENDING', 'RT Team R');
        $this->connect_as_admin();
        $rows = (new Register())->get_2nd_half_registrations($this->id_competition);
        $names = array_column($rows, 'new_team_name');
        $this->assertContains('RT Team Q', $names);
        $this->assertNotContains('RT Team R', $names);
    }
}
