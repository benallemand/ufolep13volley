<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/../classes/UserManager.php";
require_once 'UfolepTestCase.php';

class ActAsTest extends UfolepTestCase
{
    private ?int $target_user_id = null;

    protected function setUp(): void
    {
        parent::setUp();
        @session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    private function getTargetUserId(): int
    {
        if ($this->target_user_id === null) {
            $sql = "SELECT ca.id FROM comptes_acces ca 
                    LEFT JOIN users_profiles up ON up.user_id = ca.id
                    LEFT JOIN profiles p ON p.id = up.profile_id
                    WHERE p.name != 'ADMINISTRATEUR' OR p.name IS NULL
                    LIMIT 1";
            $results = $this->sql->execute($sql);
            if (empty($results)) {
                $this->markTestSkipped("Aucun utilisateur non-admin disponible pour les tests");
            }
            $this->target_user_id = (int)$results[0]['id'];
        }
        return $this->target_user_id;
    }

    public function test_switch_to_user_as_admin()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();
        $target_user_id = $this->getTargetUserId();

        // Act
        $result = $manager->switch_to_user($target_user_id);

        // Assert
        $this->assertTrue($result);
        $this->assertTrue($_SESSION['acting_as']);
        $this->assertEquals(1, $_SESSION['original_admin_id']);
        $this->assertEquals($target_user_id, $_SESSION['id_user']);
    }

    public function test_switch_to_user_fails_for_non_admin()
    {
        // Arrange
        $this->connect_as_team_leader(5);
        $manager = new UserManager();
        $target_user_id = $this->getTargetUserId();

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Seuls les administrateurs peuvent utiliser cette fonctionnalité");
        $manager->switch_to_user($target_user_id);
    }

    public function test_switch_back_to_admin()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();
        $target_user_id = $this->getTargetUserId();
        $manager->switch_to_user($target_user_id);

        // Act
        $result = $manager->switch_back_to_admin();

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(isset($_SESSION['acting_as']));
        $this->assertEquals(1, $_SESSION['id_user']);
        $this->assertEquals('ADMINISTRATEUR', $_SESSION['profile_name']);
    }

    public function test_switch_back_fails_when_not_acting_as()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();

        // Act & Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Vous n'êtes pas en mode 'Agir en tant que'");
        $manager->switch_back_to_admin();
    }

    public function test_is_acting_as_returns_true_when_acting()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();
        $target_user_id = $this->getTargetUserId();
        $manager->switch_to_user($target_user_id);

        // Act
        $result = UserManager::is_acting_as();

        // Assert
        $this->assertTrue($result);
    }

    public function test_is_acting_as_returns_false_when_not_acting()
    {
        // Arrange
        $this->connect_as_admin();

        // Act
        $result = UserManager::is_acting_as();

        // Assert
        $this->assertFalse($result);
    }

    public function test_get_original_admin_returns_admin_data()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();
        $target_user_id = $this->getTargetUserId();
        $manager->switch_to_user($target_user_id);

        // Act
        $result = UserManager::get_original_admin();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id_user']);
        $this->assertEquals('test_user', $result['login']);
        $this->assertEquals('ADMINISTRATEUR', $result['profile_name']);
    }

    public function test_get_original_admin_returns_null_when_not_acting()
    {
        // Arrange
        $this->connect_as_admin();

        // Act
        $result = UserManager::get_original_admin();

        // Assert
        $this->assertNull($result);
    }

    public function test_get_users_for_act_as_returns_list()
    {
        // Arrange
        $this->connect_as_admin();
        $manager = new UserManager();

        // Act
        $result = $manager->get_users_for_act_as();

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('login', $result[0]);
    }
}
