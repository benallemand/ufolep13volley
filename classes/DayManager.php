<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';

class DayManager extends Generic
{

    /**
     * @param string|null $query
     * @return string
     */
    private function getSql(?string $query = "1=1"): string
    {
        $sql = "SELECT 
        j.id,
        j.code_competition,
        j.numero,
        j.nommage,
        j.libelle,
        DATE_FORMAT(j.start_date, '%d/%m/%Y') AS start_date
        FROM journees j
        WHERE $query
        ORDER BY j.start_date";
        return $sql;
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getDays($query = null)
    {
        $db = Database::openDbConnection();
        $sql = $this->getSql($query);
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $code_competition
     * @param $numero
     * @param $competition_start_date
     * @param bool $is_extra_day
     * @return int|string
     * @throws Exception
     */
    public function insertDay($code_competition, $numero, $competition_start_date, bool $is_extra_day=false)
    {
        $db = Database::openDbConnection();
        $numero_padded = str_pad($numero, 2, '0', STR_PAD_LEFT);
        $week_offset = $numero - 1;
        $nommage = "Journee $numero_padded";
        if($is_extra_day) {
            $nommage = "Journee de reports";
        }
        $sql = "INSERT INTO journees SET 
          code_competition = '$code_competition', 
          numero = $numero, 
          nommage = '$nommage',
          start_date = ADDDATE(STR_TO_DATE('$competition_start_date', '%d/%m/%Y'), INTERVAL $week_offset WEEK)";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            $message = mysqli_error($db);
            disconn_db();
            throw new Exception($message);
        }
        return mysqli_insert_id($db);
    }

    /**
     * @param $query
     * @throws Exception
     */
    public function deleteDays($query)
    {
        $db = Database::openDbConnection();
        $sql = "DELETE FROM journees WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            throw new Exception("Erreur durant l'effacement: " . mysqli_error($db));
        }
    }

}