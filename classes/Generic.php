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
        $unwanted_array = array('?' => 'S', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'İ' => 'Y', 'Ş' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ğ' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ı' => 'y', 'ş' => 'b', 'ÿ' => 'y',
            '-' => '', ' ' => '');
        return strtr($str, $unwanted_array);
    }
}