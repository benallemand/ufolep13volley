<?php
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

/**
 * Issue #245 — rôles dérivés et cumulables.
 *
 * Les profils exclusifs (profiles/users_profiles) sont remplacés par :
 *  - admin            -> comptes_acces.is_admin
 *  - resp. d'équipe   -> existence d'une ligne users_teams
 *  - resp. de club    -> existence d'une ligne users_clubs
 * Un même compte peut cumuler les trois. Les flags sont posés en session
 * au login et par les bascules « agir en tant que ».
 */
class RolesTest extends UfolepTestCase
{
    private const TEST_LOGIN = 'roles_test_user';

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = '" . self::TEST_LOGIN . "')");
        $this->sql->execute("DELETE FROM users_clubs WHERE user_id IN (SELECT id FROM comptes_acces WHERE login = '" . self::TEST_LOGIN . "')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE login = '" . self::TEST_LOGIN . "'");
    }

    private function create_account(bool $is_admin): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET login = '" . self::TEST_LOGIN . "', email = 'roles_test@ufolep.test', password_hash = MD5('x'), is_admin = " . ($is_admin ? 1 : 0));
    }

    private function login_as(int $user_id): void
    {
        // reproduit la pose de session du login via l'act-as admin (même
        // mécanisme setSessionRoles), sans passer par le POST de login()
        $this->connect_as_admin();
        (new UserManager())->switch_to_user($user_id);
    }

    public function test_account_without_links_has_no_role()
    {
        $userId = $this->create_account(false);
        $this->login_as($userId);
        $this->assertFalse(UserManager::isAdmin());
        $this->assertFalse(UserManager::isTeamLeader());
        $this->assertFalse(UserManager::isClubLeader());
        $this->assertTrue(UserManager::is_connected());
    }

    public function test_team_link_makes_team_leader()
    {
        $teamRow = $this->sql->execute("SELECT id_equipe FROM equipes LIMIT 1");
        if (count($teamRow) === 0) {
            $this->markTestSkipped("Aucune équipe disponible");
        }
        $userId = $this->create_account(false);
        $this->sql->execute("INSERT INTO users_teams SET user_id = $userId, team_id = " . (int)$teamRow[0]['id_equipe']);
        $this->login_as($userId);
        $this->assertTrue(UserManager::isTeamLeader());
        $this->assertFalse(UserManager::isAdmin());
        $this->assertFalse(UserManager::isClubLeader());
        $this->assertEquals((int)$teamRow[0]['id_equipe'], $_SESSION['id_equipe']);
    }

    public function test_club_link_makes_club_leader()
    {
        $clubRow = $this->sql->execute("SELECT id FROM clubs LIMIT 1");
        if (count($clubRow) === 0) {
            $this->markTestSkipped("Aucun club disponible");
        }
        $userId = $this->create_account(false);
        $this->sql->execute("INSERT INTO users_clubs SET user_id = $userId, club_id = " . (int)$clubRow[0]['id']);
        $this->login_as($userId);
        $this->assertTrue(UserManager::isClubLeader());
        $this->assertFalse(UserManager::isAdmin());
        $this->assertFalse(UserManager::isTeamLeader());
        $this->assertEquals((int)$clubRow[0]['id'], $_SESSION['id_club']);
    }

    public function test_roles_are_cumulative()
    {
        $teamRow = $this->sql->execute(
            "SELECT e.id_equipe, e.id_club FROM equipes e WHERE e.id_club IS NOT NULL LIMIT 1");
        if (count($teamRow) === 0) {
            $this->markTestSkipped("Aucune équipe avec club disponible");
        }
        $userId = $this->create_account(true);
        $this->sql->execute("INSERT INTO users_teams SET user_id = $userId, team_id = " . (int)$teamRow[0]['id_equipe']);
        $this->sql->execute("INSERT INTO users_clubs SET user_id = $userId, club_id = " . (int)$teamRow[0]['id_club']);
        $this->login_as($userId);
        $this->assertTrue(UserManager::isAdmin());
        $this->assertTrue(UserManager::isTeamLeader());
        $this->assertTrue(UserManager::isClubLeader());
        $this->assertEquals((int)$teamRow[0]['id_club'], $_SESSION['id_club']);
    }

    public function test_setAdmin_grants_and_revokes()
    {
        $userId = $this->create_account(false);
        $this->connect_as_admin();
        $userManager = new UserManager();
        $userManager->setAdmin($userId, 'true');
        $row = $this->sql->execute("SELECT is_admin FROM comptes_acces WHERE id = $userId");
        $this->assertEquals(1, (int)$row[0]['is_admin']);
        $userManager->setAdmin($userId, 'false');
        $row = $this->sql->execute("SELECT is_admin FROM comptes_acces WHERE id = $userId");
        $this->assertEquals(0, (int)$row[0]['is_admin']);
    }

    public function test_setAdmin_refused_for_non_admin()
    {
        $userId = $this->create_account(false);
        $this->connect_as_team_leader(1);
        $this->expectException(Exception::class);
        (new UserManager())->setAdmin($userId, 'true');
    }

    public function test_admin_can_now_be_linked_to_a_team()
    {
        $teamRow = $this->sql->execute("SELECT id_equipe FROM equipes LIMIT 1");
        if (count($teamRow) === 0) {
            $this->markTestSkipped("Aucune équipe disponible");
        }
        $userId = $this->create_account(true);
        $this->connect_as_admin();
        // l'ancienne implémentation levait "Les administrateurs ne peuvent pas être associés à une équipe"
        (new UserManager())->updateUserTeams($userId, (string)$teamRow[0]['id_equipe']);
        $teamIds = (new UserManager())->getUserTeamIds($userId);
        $this->assertContains((int)$teamRow[0]['id_equipe'], array_map('intval', $teamIds));
    }
}
