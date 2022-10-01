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
     * @return array
     * @throws Exception
     */
    public function getCurrentUserDetails(): array
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
        $comment = mysqli_real_escape_string($db, $comment);
        if (!empty($userDetails['id_user'])) {
            $sessionIdUser = $userDetails['id_user'];
            $sql = "INSERT activity SET comment='$comment', activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id=$sessionIdUser";
        } else {
            $sql = "INSERT activity SET comment='$comment', activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
        }
        mysqli_query($db, $sql);
    }

    /**
     * @param $str
     * @return string
     */
    protected function accentedToNonAccented($str): string
    {
        $unwanted_array = array('?' => 'S', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y',
            '-' => '', ' ' => '');
        return strtr($str, $unwanted_array);
    }

    /**
     * @param $id_team
     * @return array
     * @throws Exception
     */
    public function getActivity($id_team): array
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

    protected function build_activity($subject, $dirty_fields, $inputs): ?string
    {
        if (empty($dirty_fields)) {
            return null;
        }
        $fieldsArray = explode(',', $dirty_fields);
        $comment = "$subject : " . "<br/>";
        foreach ($fieldsArray as $fieldName) {
            $fieldValue = $inputs[$fieldName];
            $comment .= "- $fieldName => $fieldValue" . "<br/>";
        }
        return $comment;
    }
}