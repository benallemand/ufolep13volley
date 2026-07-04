<?php

use PHPUnit\Framework\TestCase;

class UfolepTestCase extends TestCase
{
    protected SqlManager $sql;

    public function __construct()
    {
        parent::__construct();
        $this->sql = new SqlManager();
    }

    // Les rôles sont des flags de session cumulables (issue #245).
    private function reset_session_roles()
    {
        @session_start();
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = 1;
        $_SESSION['is_admin'] = false;
        $_SESSION['is_team_leader'] = false;
        $_SESSION['is_club_leader'] = false;
        $_SESSION['id_equipe'] = null;
        unset($_SESSION['id_club']);
    }

    protected function connect_as_admin()
    {
        $this->reset_session_roles();
        $_SESSION['is_admin'] = true;
    }

    protected function connect_as_team_leader(mixed $id_equipe)
    {
        $this->reset_session_roles();
        $_SESSION['is_team_leader'] = true;
        $_SESSION['id_equipe'] = $id_equipe;
    }

    protected function connect_as_club_leader(mixed $id_club, mixed $id_equipe = null)
    {
        $this->reset_session_roles();
        $_SESSION['is_club_leader'] = true;
        $_SESSION['id_club'] = $id_club;
        $_SESSION['id_equipe'] = $id_equipe;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }
}
