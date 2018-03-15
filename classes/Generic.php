<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:54
 */
require_once __DIR__ . '/Database.php';

class Generic
{
    /**
     * @return mixed
     * @throws Exception
     */
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

    /**
     * @param $comment
     * @throws Exception
     */
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

    /**
     * @param $id_team
     * @return array
     * @throws Exception
     */
    public function getActivity($id_team)
    {
        $userDetails = $this->getCurrentUserDetails();
        $db = Database::openDbConnection();
        $sql = "SELECT 
                DATE_FORMAT(a.activity_date, '%d/%m/%Y %H:%i:%s') AS date, 
                e.nom_equipe, 
                c.libelle AS competition, 
                a.comment AS description, 
                ca.login AS utilisateur, 
                ca.email AS email_utilisateur 
            FROM activity a
            LEFT JOIN comptes_acces ca ON ca.id=a.user_id
            LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe
            LEFT JOIN competitions c ON c.code_competition=e.code_competition";
        if (!empty($id_team)) {
            $sql .= " WHERE e.id_equipe = $id_team";
        }
        $sql .= " ORDER BY a.activity_date DESC";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }
}