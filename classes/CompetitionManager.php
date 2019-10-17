<?php
require_once __DIR__ . '/Generic.php';

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

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
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

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
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
            throw new Exception("La date limite n'a pas �t� saisie pour cette comp�tition !");
        }
        $format = "d/m/Y";
        $limit_date = DateTime::createFromFormat($format, $results[0]['date_limite']);
        $now_date = new DateTime();
        if ($now_date > $limit_date) {
            return true;
        }
        return false;
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
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
            throw new Exception("La date de d�but n'a pas �t� saisie pour cette comp�tition !");
        }
        $format = "d/m/Y";
        $start_date = DateTime::createFromFormat($format, $results[0]['start_date']);
        $now_date = new DateTime();
        if ($now_date > $start_date) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBlacklistGymnase()
    {
        $db = Database::openDbConnection();
        $sql = "SELECT  bg.id, 
                        bg.id_gymnase, 
                        CONCAT(g.nom, ' (', g.ville, ')') AS libelle_gymnase,
                        DATE_FORMAT(bg.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_gymnase bg
                JOIN gymnase g on bg.id_gymnase = g.id";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBlacklistTeam()
    {
        $db = Database::openDbConnection();
        $sql = "SELECT  bt.id, 
                        bt.id_team, 
                        CONCAT(e.nom_equipe, ' (', e.code_competition, ')') AS libelle_equipe,
                        DATE_FORMAT(bt.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_team bt
                JOIN equipes e ON e.id_equipe = bt.id_team";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBlacklistDate()
    {
        $db = Database::openDbConnection();
        $sql = "SELECT  bd.id,
                        DATE_FORMAT(bd.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_date bd";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    public function getBlacklistTeams()
    {
        $db = Database::openDbConnection();
        $sql = "SELECT  bt.id, 
                        bt.id_team_1, 
                        bt.id_team_2, 
                        CONCAT(e_1.nom_equipe, ' (', e_1.code_competition, ')') AS libelle_equipe_1,
                        CONCAT(e_2.nom_equipe, ' (', e_2.code_competition, ')') AS libelle_equipe_2
                FROM blacklist_teams bt
                JOIN equipes e_1 ON e_1.id_equipe = bt.id_team_1
                JOIN equipes e_2 ON e_2.id_equipe = bt.id_team_2";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

}