<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:54
 */
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/UserManager.php';

class Generic
{
    protected SqlManager $sql_manager;
    protected string $table_name;
    protected string $id_name;

    public function __construct()
    {
        @session_start();
        $this->sql_manager = new SqlManager();
        $this->id_name = 'id';
    }

    public static function starts_with($string, $startString): bool
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public static function ends_with($string, $endString): bool
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCurrentUserDetails(): array
    {
        if (!(isset($_SESSION['login']))) {
            @session_start();
        }
        if (!(isset($_SESSION['login']))) {
            throw new Exception("Utilisateur non connecté !");
        }
        return $_SESSION;
    }

    /**
     * @param $comment
     * @throws Exception
     */
    protected function addActivity($comment): void
    {
        try{
            $userDetails = $this->getCurrentUserDetails();
        }
        catch (Exception $exception) {
        }
        $bindings = array(
            array('type' => 's', 'value' => $comment),
        );
        if (!empty($userDetails['id_user'])) {
            $bindings[] = array('type' => 'i', 'value' => $userDetails['id_user']);
            $sql = "INSERT activity SET comment = ?, activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s'), user_id = ?";
        } else {
            $sql = "INSERT activity SET comment = ?, activity_date=STR_TO_DATE(NOW(), '%Y-%m-%d %H:%i:%s')";
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $str
     * @return string
     */
    public static function accentedToNonAccented($str): string
    {
        $unwanted_array = array('?' => 'S', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y',
            '-' => '', ' ' => '');
        return empty($str) ? '' : strtr($str, $unwanted_array);
    }

    public static function randomPassword(): string
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    /**
     * @param $id_team
     * @return array
     * @throws Exception
     */
    public function getActivity($id_team=null): array
    {
        if(UserManager::isTeamLeader()) {
            $id_team = $_SESSION['id_equipe'];
        }
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
        return $this->sql_manager->execute($sql);
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

    public function getSql($query = "1=1"): string
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query";
    }

    /**
     * @param string $query
     * @param array $bindings
     * @return array
     * @throws Exception
     */
    public function get(string $query = "1=1", array $bindings=array()): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function get_one(string $query = "1=1", array $bindings=array()): ?array
    {
        $results = $this->get($query, $bindings);
        return count($results) == 1 ? $results[0] : null;
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function get_by_id($id): array
    {
        $query = "$this->id_name = ?";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $id
        );
        $sql = $this->getSql($query);
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            throw new Exception("Pas de donnée dispo pour l'id $id !");
        }
        return $results[0];
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete($ids): void
    {
        $sql = "DELETE FROM $this->table_name WHERE $this->id_name IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param $inputs
     * @return array|int|string|null
     * @throws Exception
     */
    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs[$this->id_name])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " $this->table_name SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case $this->id_name:
                case 'dirtyFields':
                    break;
                default:
                    $bindings[] = array(
                        'type' => 's',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs[$this->id_name])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs[$this->id_name]
            );
            $sql .= " WHERE $this->id_name = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

}