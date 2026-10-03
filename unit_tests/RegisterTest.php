<?php
require_once __DIR__ . '/../classes/Register.php';
require_once __DIR__ . '/../classes/Rank.php';
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
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE email LIKE 'rt_%@ufolep.test')");
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

    public function test_admin_who_is_also_club_leader_registers_for_his_session_club()
    {
        // cumul des rôles : admin ET responsable de club, id_club non posté
        // (cas du dépôt depuis l'espace club — bug remonté en recette)
        $this->connect_as_club_leader($this->id_club_1);
        $_SESSION['is_admin'] = true;
        try {
            $this->call_register(['id_club' => null, 'new_team_name' => 'RT Team S']);
            $this->fail("Une création réussie doit lever l'exception 201");
        } catch (Exception $e) {
            $this->assertEquals(201, $e->getCode());
        }
        $rows = $this->sql->execute("SELECT * FROM register WHERE new_team_name = 'RT Team S'");
        $this->assertEquals($this->id_club_1, (int)$rows[0]['id_club']);
    }

    public function test_admin_without_club_cannot_create_without_id_club()
    {
        $this->connect_as_admin();
        $this->expectExceptionMessage("Le club de l'équipe à inscrire n'est pas déterminé !");
        $this->call_register(['id_club' => null, 'new_team_name' => 'RT Team T']);
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

    // ---- Issue #376 : décisions contrôlées selon le statut, refus motivé -----

    /** Compte de club (users_clubs) destinataire des notifications. */
    private function add_club_account(int $id_club): void
    {
        $id_user = (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'rt_club_account', email = 'rt_club@ufolep.test', password_hash = 'x'");
        $this->sql->execute("INSERT INTO users_clubs SET user_id = $id_user, club_id = $id_club");
    }

    private function status_of(int $id): array
    {
        return $this->sql->execute("SELECT status, validation_date, refusal_reason, refusal_date FROM register WHERE id = $id")[0];
    }

    private function emails_to_club(): array
    {
        return $this->sql->execute("SELECT subject, body FROM emails WHERE to_email = 'rt_club@ufolep.test' ORDER BY id");
    }

    private function assert_decision_refused(callable $decision, int $expected_code): void
    {
        try {
            $decision();
            $this->fail("La décision aurait dû être refusée ($expected_code)");
        } catch (Exception $e) {
            $this->assertSame($expected_code, $e->getCode(), $e->getMessage());
        }
    }

    public function test_unvalidate_pending_is_refused()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team U1');
        $this->connect_as_admin();
        $this->assert_decision_refused(fn() => (new Register())->unvalidateRegistration($id), 409);
        $this->assertSame('PENDING', $this->status_of($id)['status']);
        $this->assertCount(0, $this->sql->execute(
            "SELECT id FROM activity WHERE comment LIKE 'Inscription dévalidée : RT Team U1%'"),
            'pas de ligne d\'activité trompeuse');
    }

    public function test_validate_twice_is_refused_and_sends_a_single_email()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team U2');
        $this->add_club_account($this->id_club_1);
        $this->connect_as_admin();
        (new Register())->validateRegistration($id);
        $this->assert_decision_refused(fn() => (new Register())->validateRegistration($id), 409);
        $this->assertCount(1, $this->emails_to_club());
    }

    public function test_refuse_requires_a_reason()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team U3');
        $this->connect_as_admin();
        foreach (array(null, '', '   ') as $reason) {
            $this->assert_decision_refused(fn() => (new Register())->refuseRegistration($id, $reason), 400);
        }
        $this->assert_decision_refused(
            fn() => (new Register())->refuseRegistration($id, str_repeat('x', 1001)), 400);
        $this->assertSame('PENDING', $this->status_of($id)['status']);
    }

    public function test_refuse_is_reserved_to_admins()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team U4');
        $this->connect_as_club_leader($this->id_club_1);
        $this->assert_decision_refused(fn() => (new Register())->refuseRegistration($id, 'motif'), 403);
    }

    public function test_admin_refuses_a_pending_registration_and_the_club_is_notified()
    {
        $id = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team U5');
        $this->add_club_account($this->id_club_1);
        $this->connect_as_admin();
        (new Register())->refuseRegistration($id, "  Équipe volante <b>interdite</b> : aucun gymnase  ");

        $row = $this->status_of($id);
        $this->assertSame('REFUSED', $row['status']);
        $this->assertSame('Équipe volante <b>interdite</b> : aucun gymnase', $row['refusal_reason'],
            'motif conservé tel quel en base, espaces retirés');
        $this->assertNotNull($row['refusal_date']);

        $emails = $this->emails_to_club();
        $this->assertCount(1, $emails);
        $this->assertStringContainsString('Inscription refusée : RT Team U5', $emails[0]['subject']);
        $this->assertStringContainsString('Équipe volante &lt;b&gt;interdite&lt;/b&gt;', $emails[0]['body'],
            'le motif est échappé dans l\'email');
        $this->assertStringNotContainsString('<b>interdite</b>', $emails[0]['body']);

        $rows = (new Register())->get_register();
        $mine = array_values(array_filter($rows, fn($r) => (int)$r['id'] === $id))[0];
        $this->assertSame('REFUSED', $mine['status']);
        $this->assertArrayHasKey('refusal_reason', $mine);
    }

    public function test_refuse_twice_or_a_validated_registration_is_refused()
    {
        $refused = $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team U6');
        $validated = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team U7');
        $this->connect_as_admin();
        $this->assert_decision_refused(fn() => (new Register())->refuseRegistration($refused, 'motif'), 409);
        $this->assert_decision_refused(fn() => (new Register())->refuseRegistration($validated, 'motif'), 409);
        $this->assertSame('VALIDATED', $this->status_of($validated)['status']);
    }

    public function test_club_correction_puts_a_refused_registration_back_to_pending()
    {
        $id = $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team U8');
        $this->sql->execute("UPDATE register SET refusal_reason = 'volante', refusal_date = NOW() WHERE id = $id");
        $this->connect_as_club_leader($this->id_club_1);
        $this->call_register(['id' => $id, 'new_team_name' => 'RT Team U8', 'remarks' => 'gymnase ajouté']);

        $row = $this->status_of($id);
        $this->assertSame('PENDING', $row['status']);
        $this->assertNull($row['refusal_reason']);
        $this->assertNull($row['refusal_date']);
    }

    public function test_club_can_still_delete_a_refused_registration()
    {
        $id = $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team U9');
        $this->connect_as_club_leader($this->id_club_1);
        (new Register())->deleteMyClubRegistration($id);
        $this->assertCount(0, $this->sql->execute("SELECT id FROM register WHERE id = $id"));
    }

    public function test_admin_edit_keeps_the_refusal()
    {
        $id = $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team V1');
        $this->sql->execute("UPDATE register SET refusal_reason = 'volante' WHERE id = $id");
        $this->connect_as_admin();
        $this->call_register(['id' => $id, 'new_team_name' => 'RT Team V1', 'remarks' => 'corrigé par la commission']);
        $row = $this->status_of($id);
        $this->assertSame('REFUSED', $row['status']);
        $this->assertSame('volante', $row['refusal_reason']);
    }

    public function test_a_refused_registration_can_be_validated_directly()
    {
        $id = $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team V2');
        $this->sql->execute("UPDATE register SET refusal_reason = 'volante', refusal_date = NOW() WHERE id = $id");
        $this->connect_as_admin();
        (new Register())->validateRegistration($id);
        $row = $this->status_of($id);
        $this->assertSame('VALIDATED', $row['status']);
        $this->assertNull($row['refusal_reason']);
    }

    public function test_refused_registrations_are_never_engaged()
    {
        $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team V3');
        $this->connect_as_admin();
        $names = array_column((new Register())->get_pending_registrations($this->id_competition), 'new_team_name');
        $this->assertNotContains('RT Team V3', $names);
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

    // ---- #388 : préparation de saison, division X et « Non affectées » -------

    private function insert_team(string $name, int $id_club): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO equipes SET nom_equipe = '$name', code_competition = 'rt', id_club = $id_club");
    }

    private function placement_of(int $id_register): array
    {
        return $this->sql->execute("SELECT division, rank_start FROM register WHERE id = $id_register")[0];
    }

    public function test_fill_ranks_places_unranked_teams_in_division_x()
    {
        $ranked_team = $this->insert_team('RT Team Classee', $this->id_club_1);
        $this->sql->execute("INSERT INTO classements SET code_competition = 'rt', division = '2', id_equipe = $ranked_team, rank_start = 1, penalite = 0");
        $unranked_team = $this->insert_team('RT Team Revient', $this->id_club_2);

        $ranked = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team Classee');
        $this->sql->execute("UPDATE register SET old_team_id = $ranked_team WHERE id = $ranked");
        $back = $this->insert_registration($this->id_club_2, 'VALIDATED', 'RT Team Revient');
        $this->sql->execute("UPDATE register SET old_team_id = $unranked_team WHERE id = $back");
        $new = $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team Nouvelle');

        $this->connect_as_admin();
        (new Register())->fill_ranks("$ranked,$back,$new");

        $this->assertSame('2', $this->placement_of($ranked)['division'], "Une équipe classée garde sa division");
        $this->assertEquals(array('division' => 'X', 'rank_start' => 1), $this->placement_of($back),
            "Une équipe existante non classée la saison passée est à placer");
        $this->assertEquals(array('division' => 'X', 'rank_start' => 2), $this->placement_of($new),
            "Une nouvelle équipe est à placer, au rang suivant");
    }

    public function test_fill_ranks_can_be_replayed_without_renumbering()
    {
        $first = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team X1');
        $second = $this->insert_registration($this->id_club_2, 'VALIDATED', 'RT Team X2');
        $this->connect_as_admin();
        (new Register())->fill_ranks("$first,$second");
        $third = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team X3');
        (new Register())->fill_ranks("$second,$first,$third");

        $this->assertEquals(1, $this->placement_of($first)['rank_start']);
        $this->assertEquals(2, $this->placement_of($second)['rank_start']);
        $this->assertEquals(3, $this->placement_of($third)['rank_start'], "Les rangs X continuent après le plus grand");
    }

    public function test_fill_ranks_keeps_a_manual_division_and_skips_refused()
    {
        $manual = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team Manuelle');
        $this->sql->execute("UPDATE register SET division = '3', rank_start = 5 WHERE id = $manual");
        $refused = $this->insert_registration($this->id_club_2, 'REFUSED', 'RT Team Refusee');
        $this->connect_as_admin();
        (new Register())->fill_ranks("$manual,$refused");

        $this->assertEquals(array('division' => '3', 'rank_start' => 5), $this->placement_of($manual),
            "Une division saisie à la main n'est pas écrasée");
        $this->assertEquals(array('division' => null, 'rank_start' => null), $this->placement_of($refused),
            "Une inscription refusée n'est pas touchée");
    }

    public function test_insert_from_register_only_ranks_validated_registrations()
    {
        foreach (array('VALIDATED' => 'RT Team Valide', 'REFUSED' => 'RT Team Refus', 'PENDING' => 'RT Team Attente') as $status => $name) {
            $this->insert_team($name, $this->id_club_1);
            $this->insert_registration($this->id_club_1, $status, $name);
        }
        (new Rank())->insert_from_register($this->id_competition);
        $ranked = array_column($this->sql->execute(
            "SELECT e.nom_equipe FROM classements c JOIN equipes e ON e.id_equipe = c.id_equipe WHERE c.code_competition = 'rt'"),
            'nom_equipe');
        $this->assertSame(array('RT Team Valide'), $ranked);
    }

    public function test_unassigned_teams_say_which_are_registered()
    {
        $registered = $this->insert_team('RT Team Inscrite', $this->id_club_1);
        $this->insert_team('RT Team Refus Seul', $this->id_club_1);
        $this->insert_team('RT Team Ancienne', $this->id_club_2);
        $renamed = $this->insert_team('RT Team Ancien Nom', $this->id_club_2);
        $this->insert_registration($this->id_club_1, 'PENDING', 'RT Team Inscrite');
        $this->insert_registration($this->id_club_1, 'REFUSED', 'RT Team Refus Seul');
        $by_old_team = $this->insert_registration($this->id_club_2, 'VALIDATED', 'RT Team Nouveau Nom');
        $this->sql->execute("UPDATE register SET old_team_id = $renamed WHERE id = $by_old_team");

        $rows = array_column((new Rank())->getUnassignedTeams('rt'), null, 'nom_equipe');

        $this->assertEquals(1, $rows['RT Team Inscrite']['registered']);
        $this->assertEquals(1, $rows['RT Team Ancien Nom']['registered'], "Reconnue par son ancienne équipe");
        $this->assertEquals(0, $rows['RT Team Refus Seul']['registered'], "Une demande refusée n'inscrit pas");
        $this->assertEquals(0, $rows['RT Team Ancienne']['registered']);
        $this->assertEquals(1, $rows['RT Team Ancienne']['competition_has_registrations']);
        $this->assertArrayHasKey('RT Team Inscrite', $rows);
        $this->assertSame($registered, (int)$rows['RT Team Inscrite']['id_equipe']);
    }

    public function test_ranked_teams_say_which_are_not_registered_again()
    {
        $staying = $this->insert_team('RT Team Reste', $this->id_club_1);
        $leaving = $this->insert_team('RT Team Part', $this->id_club_2);
        foreach (array($staying, $leaving) as $i => $id_team) {
            $this->sql->execute("INSERT INTO classements SET code_competition = 'rt', division = '4', id_equipe = $id_team, rank_start = " . ($i + 1) . ", penalite = 0");
        }
        $renewal = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team Reste');
        $this->sql->execute("UPDATE register SET old_team_id = $staying WHERE id = $renewal");

        $division = array_column((new Rank())->getRanksByCompetitionGroupedByDivision('rt')['4'], null, 'nom_equipe');

        $this->assertEquals(1, $division['RT Team Reste']['registered']);
        $this->assertEquals(0, $division['RT Team Part']['registered'], "Sans inscription, l'équipe est à retirer");
        $this->assertEquals(1, $division['RT Team Part']['competition_has_registrations']);
    }

    // ---- #390 : homonymes ------------------------------------------------------

    public function test_a_renewal_only_designates_its_old_team_not_a_namesake()
    {
        $real = $this->insert_team('RT Team Homonyme', $this->id_club_1);
        $namesake = $this->insert_team('RT Team Homonyme', $this->id_club_1);
        $this->sql->execute("INSERT INTO classements SET code_competition = 'rt', division = '4', id_equipe = $namesake, rank_start = 7, penalite = 0");
        $renewal = $this->insert_registration($this->id_club_1, 'VALIDATED', 'RT Team Homonyme');
        $this->sql->execute("UPDATE register SET old_team_id = $real WHERE id = $renewal");

        $unassigned = array_column((new Rank())->getUnassignedTeams('rt'), null, 'id_equipe');
        $this->assertEquals(1, $unassigned[$real]['registered'], "La vraie équipe est inscrite");
        $division = array_column((new Rank())->getRanksByCompetitionGroupedByDivision('rt')['4'], null, 'id_equipe');
        $this->assertEquals(0, $division[$namesake]['registered'], "L'homonyme n'est pas inscrit : à retirer");

        $this->sql->execute("DELETE FROM classements WHERE code_competition = 'rt'");
        (new Rank())->insert_from_register($this->id_competition);
        $ranked = array_map('intval', array_column(
            $this->sql->execute("SELECT id_equipe FROM classements WHERE code_competition = 'rt'"), 'id_equipe'));
        $this->assertSame(array($real), $ranked, "Seule l'ancienne équipe est engagée, pas son homonyme");
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
