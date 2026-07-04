<?php
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';

/**
 * Issue #247 — un email = un compte.
 *
 * La connexion accepte le login historique OU l'email ; le sel du hash de
 * mot de passe reste le login stocké (aucun mot de passe invalidé).
 * La création de compte refuse un email déjà utilisé et recycle le compte
 * existant quand on rattache un responsable par email.
 */
class AuthenticationTest extends UfolepTestCase
{
    private const TEST_LOGIN = 'auth_test_login';
    private const TEST_EMAIL = 'auth_test@ufolep.test';
    private const TEST_PASSWORD = 'secret_pwd';

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
        $this->sql->execute("DELETE FROM users_teams WHERE user_id IN (SELECT id FROM comptes_acces WHERE email = '" . self::TEST_EMAIL . "')");
        $this->sql->execute("DELETE FROM comptes_acces WHERE email = '" . self::TEST_EMAIL . "'");
    }

    /**
     * Compte historique : login différent de l'email, hash salé par le login.
     */
    private function create_account(): int
    {
        return (int)$this->sql->execute(
            "INSERT INTO comptes_acces SET
                login = '" . self::TEST_LOGIN . "',
                email = '" . self::TEST_EMAIL . "',
                password_hash = MD5(CONCAT('" . self::TEST_LOGIN . "', '" . self::TEST_PASSWORD . "'))");
    }

    public function test_authenticate_with_historical_login()
    {
        $userId = $this->create_account();
        $account = (new UserManager())->authenticate(self::TEST_LOGIN, self::TEST_PASSWORD);
        $this->assertNotNull($account);
        $this->assertEquals($userId, (int)$account['id_user']);
    }

    public function test_authenticate_with_email_uses_stored_login_as_salt()
    {
        $userId = $this->create_account();
        // connexion par email : le mot de passe reste valide car le sel est le login stocké
        $account = (new UserManager())->authenticate(self::TEST_EMAIL, self::TEST_PASSWORD);
        $this->assertNotNull($account);
        $this->assertEquals($userId, (int)$account['id_user']);
        $this->assertEquals(self::TEST_LOGIN, $account['login']);
    }

    public function test_authenticate_with_wrong_password_returns_null()
    {
        $this->create_account();
        $this->assertNull((new UserManager())->authenticate(self::TEST_LOGIN, 'mauvais_mdp'));
        $this->assertNull((new UserManager())->authenticate(self::TEST_EMAIL, 'mauvais_mdp'));
    }

    public function test_authenticate_unknown_account_returns_null()
    {
        $this->assertNull((new UserManager())->authenticate('compte_inexistant', 'x'));
    }

    public function test_create_leader_account_reuses_existing_account_with_same_email()
    {
        $teamRow = $this->sql->execute("SELECT id_equipe FROM equipes LIMIT 1");
        if (count($teamRow) === 0) {
            $this->markTestSkipped("Aucune équipe disponible");
        }
        $teamId = (int)$teamRow[0]['id_equipe'];
        $userId = $this->create_account();
        $this->connect_as_admin();

        // le compte existe avec un login historique différent de l'email :
        // le rattachement par email doit recycler ce compte, pas en créer un autre
        (new UserManager())->create_or_update_leader_account(self::TEST_EMAIL, $teamId);

        $accounts = $this->sql->execute("SELECT id FROM comptes_acces WHERE email = '" . self::TEST_EMAIL . "'");
        $this->assertCount(1, $accounts, "Aucun compte doublon ne doit être créé");
        $this->assertEquals($userId, (int)$accounts[0]['id']);
        $teamIds = (new UserManager())->getUserTeamIds($userId);
        $this->assertContains($teamId, array_map('intval', $teamIds));
    }

    public function test_saveUser_refuses_creation_with_existing_email()
    {
        $this->create_account();
        $this->connect_as_admin();
        $this->expectException(Exception::class);
        (new UserManager())->saveUser(null, 'autre_login', self::TEST_EMAIL);
    }
}
