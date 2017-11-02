<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 02/11/2017
 * Time: 15:51
 */
require_once 'Generic.php';

class RankManager extends Generic
{
    private function getSql($query = null)
    {
        $sql = "SELECT 
        c.id,
        c.code_competition,
        c.division,
        c.id_equipe,
        c.penalite,
        c.report_count,
        c.rank_start
        FROM classements c
        JOIN competitions comp ON comp.code_competition = c.code_competition
        JOIN equipes e ON e.id_equipe = c.id_equipe
        WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        return $sql;
    }

    public function getRanks($query = null)
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

    public function getCompetitions()
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DISTINCT code_competition FROM classements";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    public function getDivisionsFromCompetition($code_competition)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DISTINCT division 
FROM classements
WHERE code_competition = '$code_competition'";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    public function getTeamsFromDivisionAndCompetition($division, $code_competition)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT id_equipe 
FROM classements 
WHERE code_competition = '$code_competition' 
AND division = '$division'
ORDER BY rank_start";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

}