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


    protected function connect_as_admin()
    {
        @session_start();
        $_SESSION['id_equipe'] = null;
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = 1;
        $_SESSION['profile_name'] = 'ADMINISTRATEUR';
    }

    protected function connect_as_team_leader(mixed $id_equipe)
    {
        @session_start();
        $_SESSION['id_equipe'] = $id_equipe;
        $_SESSION['login'] = 'test_user';
        $_SESSION['id_user'] = 1;
        $_SESSION['profile_name'] = 'RESPONSABLE_EQUIPE';
    }
}