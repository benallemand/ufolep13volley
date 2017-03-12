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
    public function getCurrentUserDetails()
    {
        if (!(isset($_SESSION['login']))) {
            session_start();
        }
        if (!(isset($_SESSION['login']))) {
            throw new Exception("User not logged in");
        }
        return $_SESSION;
    }

    protected function addActivity($comment)
    {
        $userDetails = $this->getCurrentUserDetails();
        $db = Database::openDbConnection();
        if (!empty($userDetails['id_user'])) {
            $sessionIdUser = $userDetails['id_user'];
            $sql = "INSERT activity SET comment=\"$comment\", activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id=$sessionIdUser";
        } else {
            $sql = "INSERT activity SET comment=\"$comment\", activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
        }
        mysqli_query($db, $sql);
    }

    protected function accentedToNonAccented($str)
    {
        $unwanted_array = array('?' => 'S', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'C', '�' => 'E', '�' => 'E',
            '�' => 'E', '�' => 'E', '�' => 'I', '�' => 'I', '�' => 'I', '�' => 'I', '�' => 'N', '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'U',
            '�' => 'U', '�' => 'U', '�' => 'U', '�' => 'Y', '�' => 'B', '�' => 'Ss', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'c',
            '�' => 'e', '�' => 'e', '�' => 'e', '�' => 'e', '�' => 'i', '�' => 'i', '�' => 'i', '�' => 'i', '�' => 'o', '�' => 'n', '�' => 'o', '�' => 'o', '�' => 'o', '�' => 'o',
            '�' => 'o', '�' => 'o', '�' => 'u', '�' => 'u', '�' => 'u', '�' => 'y', '�' => 'b', '�' => 'y',
            '-' => '', ' ' => '');
        return strtr($str, $unwanted_array);
    }
}