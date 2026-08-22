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
        $_SESSION['club_ids'] = array();
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

    /**
     * Un compte peut gérer plusieurs clubs : $id_club accepte un id ou un
     * tableau d'ids (le premier devient le club courant).
     */
    protected function connect_as_club_leader(mixed $id_club, mixed $id_equipe = null)
    {
        $this->reset_session_roles();
        $club_ids = array_values(array_filter(
            array_map('intval', is_array($id_club) ? $id_club : array($id_club))));
        $_SESSION['is_club_leader'] = true;
        $_SESSION['club_ids'] = $club_ids;
        // repli sur la valeur brute pour les tests qui se connectent sans club
        $_SESSION['id_club'] = $club_ids[0] ?? $id_club;
        $_SESSION['id_equipe'] = $id_equipe;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }
}
