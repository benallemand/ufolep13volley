<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:54
 */
require_once 'Database.php';

class Generic
{
    protected function getCurrentUserDetails() {
        if (!(isset($_SESSION['login']))) {
            session_start();
        }
        if (!(isset($_SESSION['login']))) {
            throw new Exception("User not logged in");
        }
        return $_SESSION;
    }
}