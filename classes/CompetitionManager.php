<?php
require_once 'Generic.php';

class CompetitionManager extends Generic
{
    private function getSql($query = null)
    {
        $sql = "SELECT 
        c.id,
        c.code_competition,
        c.libelle,
        c.id_compet_maitre,
        DATE_FORMAT(c.start_date, '%d/%m/%Y') AS start_date
        FROM competitions c
        WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        return $sql;
    }

    public function getCompetitions($query = null)
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

    public function isCompetitionOver($id)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT date_limite FROM dates_limite WHERE code_competition IN (SELECT code_competition FROM competitions WHERE id = $id)";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) !== 1) {
            throw new Exception("La date limite n'a pas été saisie pour cette compétition !");
        }
        $format = "d/m/Y";
        $limit_date = DateTime::createFromFormat($format, $results[0]['date_limite']);
        $now_date = new DateTime();
        if ($now_date > $limit_date) {
            return true;
        }
        return false;
    }

    public function isCompetitionStarted($id)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DATE_FORMAT(start_date, '%d/%m/%Y') AS start_date FROM competitions WHERE id = $id";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) !== 1) {
            throw new Exception("La date de début n'a pas été saisie pour cette compétition !");
        }
        $format = "d/m/Y";
        $start_date = DateTime::createFromFormat($format, $results[0]['start_date']);
        $now_date = new DateTime();
        if ($now_date > $start_date) {
            return true;
        }
        return false;
    }

}