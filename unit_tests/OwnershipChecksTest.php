<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/Players.php';
require_once __DIR__ . '/../classes/TimeSlot.php';
require_once __DIR__ . '/../classes/Team.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

/**
 * Issue #356 — contrôles d'appartenance manquants sur les endpoints `user`.
 *
 * Deux clubs A et B, une équipe chacun (TA, TB), un joueur par équipe (PA,
 * PB), un créneau par équipe, et un joueur du club A sans équipe (PA2).
 * Le responsable connecté gère TA ; le responsable de club gère le club B.
 */
class OwnershipChecksTest extends UfolepTestCase
{
    private array $club = array();
    private array $team = array();
    private array $player = array();
    private array $slot = array();
    private int $leader_user_id;
    private int $club_user_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->leader_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue356_leader', email = 'issue356@test.fr', password_hash = MD5('test')");
        // compte distinct : le responsable de club ne doit pas hériter de l'équipe A
        $this->club_user_id = $this->sql->execute(
            "INSERT INTO comptes_acces SET login = 'issue356_club', email = 'issue356_club@test.fr', password_hash = MD5('test')");
        $id_court = $this->sql->execute("INSERT INTO gymnase SET nom = 'issue356 court'");
        foreach (array('A', 'B') as $k) {
            $this->club[$k] = $this->sql->execute("INSERT INTO clubs SET nom = 'issue356 club $k'");
            $this->team[$k] = $this->sql->execute("INSERT INTO equipes SET code_competition = 'm', nom_equipe = 'issue356 team $k', id_club = ?",
                array(array('type' => 'i', 'value' => $this->club[$k])));
            $this->player[$k] = $this->sql->execute("INSERT INTO joueurs SET nom = 'Issue356', prenom = 'Joueur $k', sexe = 'M', id_club = ?",
                array(array('type' => 'i', 'value' => $this->club[$k])));
            $this->sql->execute("INSERT INTO joueur_equipe SET id_joueur = ?, id_equipe = ?", array(
                array('type' => 'i', 'value' => $this->player[$k]),
                array('type' => 'i', 'value' => $this->team[$k]),
            ));
            $this->slot[$k] = $this->sql->execute("INSERT INTO creneau SET id_gymnase = ?, jour = 'Lundi', heure = '20:00', id_equipe = ?",
                array(
                    array('type' => 'i', 'value' => $id_court),
                    array('type' => 'i', 'value' => $this->team[$k]),
                ));
        }
        $this->player['A2'] = $this->sql->execute("INSERT INTO joueurs SET nom = 'Issue356', prenom = 'Sans equipe', sexe = 'M', id_club = ?",
            array(array('type' => 'i', 'value' => $this->club['A'])));
        $this->sql->execute("INSERT INTO users_teams SET user_id = ?, team_id = ?", array(
            array('type' => 'i', 'value' => $this->leader_user_id),
            array('type' => 'i', 'value' => $this->team['A']),
        ));
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM creneau WHERE id_gymnase IN (SELECT id FROM gymnase WHERE nom = 'issue356 court')");
        $this->sql->execute("DELETE FROM gymnase WHERE nom = 'issue356 court'");
        $this->sql->execute("DELETE FROM joueur_equipe WHERE id_joueur IN (SELECT id FROM joueurs WHERE nom = 'Issue356')");
        $this->sql->execute("DELETE FROM joueurs WHERE nom = 'Issue356'");
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login LIKE 'issue356_%')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login LIKE 'issue356_%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE '%issue356%'");
        $this->sql->execute("DELETE FROM equipes WHERE nom_equipe LIKE 'issue356 team %'");
        $this->sql->execute("DELETE FROM clubs WHERE nom LIKE 'issue356 club %'");
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function connect_as_leader_of_a(): void
    {
        $this->connect_as_team_leader($this->team['A']);
        $_SESSION['login'] = 'issue356_leader';
        $_SESSION['id_user'] = $this->leader_user_id;
    }

    private function connect_as_club_leader_of_b(): void
    {
        $this->connect_as_club_leader($this->club['B']);
        $_SESSION['login'] = 'issue356_club';
        $_SESSION['id_user'] = $this->club_user_id;
    }

    private function assert_forbidden(callable $call): void
    {
        try {
            $call();
            self::fail("L'action devait être refusée");
        } catch (Exception $e) {
            self::assertSame(403, $e->getCode(), $e->getMessage());
        }
    }

    // --- règle commune

    public function test_can_manage_team(): void
    {
        $um = new UserManager();
        $this->connect_as_leader_of_a();
        self::assertTrue($um->canManageTeam($this->team['A']));
        self::assertFalse($um->canManageTeam($this->team['B']));
        $this->connect_as_club_leader_of_b();
        self::assertTrue($um->canManageTeam($this->team['B']));
        self::assertFalse($um->canManageTeam($this->team['A']));
        $this->connect_as_admin();
        self::assertTrue($um->canManageTeam($this->team['B']));
    }

    public function test_can_manage_player(): void
    {
        $um = new UserManager();
        $this->connect_as_leader_of_a();
        $um->assertCanManagePlayer($this->player['A']);
        $um->assertCanManagePlayer($this->player['A2']); // même club, sans équipe
        $this->assert_forbidden(fn() => $um->assertCanManagePlayer($this->player['B']));
        $this->connect_as_club_leader_of_b();
        $um->assertCanManagePlayer($this->player['B']);
        $this->assert_forbidden(fn() => $um->assertCanManagePlayer($this->player['A']));
        $this->connect_as_admin();
        $um->assertCanManagePlayer($this->player['B']);
        $this->addToAssertionCount(4);
    }

    // --- rôles dans l'équipe d'un autre club

    public function test_les_roles_d_une_equipe_d_un_autre_club_sont_refuses(): void
    {
        $this->connect_as_leader_of_a();
        $players = new Players();
        $pb = (string)$this->player['B'];
        $this->assert_forbidden(fn() => $players->set_leader($pb, $this->team['B']));
        $this->assert_forbidden(fn() => $players->set_captain($pb, $this->team['B']));
        $this->assert_forbidden(fn() => $players->set_vice_leader($pb, $this->team['B']));
        $this->assert_forbidden(fn() => $players->remove_from_team($pb, $this->team['B']));
        $still = $this->sql->execute("SELECT COUNT(*) AS n FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?", array(
            array('type' => 'i', 'value' => $this->player['B']),
            array('type' => 'i', 'value' => $this->team['B']),
        ));
        self::assertSame(1, (int)$still[0]['n'], "Le joueur n'a pas dû être retiré de son équipe");
    }

    // --- joueurs

    public function test_update_player_refuse_un_joueur_d_un_autre_club(): void
    {
        $this->connect_as_leader_of_a();
        $this->assert_forbidden(fn() => (new Players())->update_player(
            null, 'Pirate', 'Issue356', 'M', null, $this->club['B'], id: $this->player['B']));
        $row = $this->sql->execute("SELECT prenom FROM joueurs WHERE id = ?",
            array(array('type' => 'i', 'value' => $this->player['B'])));
        self::assertSame('Joueur B', $row[0]['prenom']);
        $in_a = $this->sql->execute("SELECT COUNT(*) AS n FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?", array(
            array('type' => 'i', 'value' => $this->player['B']),
            array('type' => 'i', 'value' => $this->team['A']),
        ));
        self::assertSame(0, (int)$in_a[0]['n'], "Le joueur ne doit pas avoir été ajouté à l'équipe A");
    }

    public function test_upload_photo_refuse_un_joueur_d_un_autre_club(): void
    {
        $this->connect_as_leader_of_a();
        $this->assert_forbidden(fn() => (new Players())->uploadPhoto($this->player['B'], 'Issue356', 'Joueur B'));
    }

    // --- créneaux

    public function test_creneaux_d_une_autre_equipe_refuses(): void
    {
        $this->connect_as_leader_of_a();
        $slots = new TimeSlot();
        $court = $this->sql->execute("SELECT id FROM gymnase WHERE nom = 'issue356 court'")[0]['id'];
        // créer un créneau pour l'équipe B
        $this->assert_forbidden(fn() => $slots->saveTimeSlot($court, 'Mardi', '20:00', 1, id_equipe: $this->team['B']));
        // détourner le créneau de B vers A
        $this->assert_forbidden(fn() => $slots->saveTimeSlot($court, 'Mardi', '20:00', 1, id: $this->slot['B'], id_equipe: $this->team['A']));
        // supprimer le créneau de B
        $this->assert_forbidden(fn() => $slots->removeTimeSlot($this->slot['B']));
        $left = $this->sql->execute("SELECT id_equipe, jour FROM creneau WHERE id = ?",
            array(array('type' => 'i', 'value' => $this->slot['B'])));
        self::assertSame(array('id_equipe' => $this->team['B'], 'jour' => 'Lundi'),
            array('id_equipe' => (int)$left[0]['id_equipe'], 'jour' => $left[0]['jour']));
        // son propre créneau reste supprimable
        $slots->removeTimeSlot($this->slot['A']);
        self::assertCount(0, $this->sql->execute("SELECT id FROM creneau WHERE id = ?",
            array(array('type' => 'i', 'value' => $this->slot['A']))));
    }

    // --- équipes

    public function test_save_team_refuse_une_equipe_non_geree_et_la_creation(): void
    {
        $this->connect_as_club_leader_of_b();
        $team = new Team();
        $this->assert_forbidden(fn() => $team->saveTeam(null, null, $this->team['A'], null, null, 'issue356 team pirate'));
        $this->assert_forbidden(fn() => $team->saveTeam(null, $this->club['B'], null, null, 'm', 'issue356 team nouvelle'));
        self::assertCount(0, $this->sql->execute("SELECT id_equipe FROM equipes WHERE nom_equipe IN ('issue356 team pirate', 'issue356 team nouvelle')"));
        // son équipe reste modifiable
        $team->saveTeam('https://example.org', null, $this->team['B']);
        $row = $this->sql->execute("SELECT web_site FROM equipes WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $this->team['B'])));
        self::assertSame('https://example.org', $row[0]['web_site']);
    }

    // --- matchs

    public function test_save_match_sans_match_ne_cree_rien(): void
    {
        $this->connect_as_leader_of_a();
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        (new MatchMgr())->save_match(null, 'ISSUE356', 25, 25, 25, null, null, 0, 0, 0, null, null, null, null);
    }
}
