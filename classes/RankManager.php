<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 02/11/2017
 * Time: 15:51
 */
require_once __DIR__ . '/Generic.php';

class RankManager extends Generic
{
    private function getSql($query = "1=1"): string
    {
        return "SELECT 
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
                WHERE $query";
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getRanks($query = null): array
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
     * @return array
     * @throws Exception
     */
    public function getCompetitions(): array
    {
        $db = Database::openDbConnection();
        $sql = "SELECT  c.code_competition, 
                        IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date
            FROM competitions c";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getDivisionsFromCompetition($code_competition): array
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DISTINCT division 
            FROM classements
            WHERE code_competition = '$code_competition'
            ORDER BY CAST(division AS UNSIGNED)";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $division
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getTeamsFromDivisionAndCompetition($division, $code_competition): array
    {
        $db = Database::openDbConnection();
        $sql = "SELECT DISTINCT c.id_equipe,
                e.nom_equipe,
                CONCAT(e.nom_equipe, ' (', cl.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', c.division, ')'), '')) AS team_full_name,
                IF(cr.id IS NULL, '0', '1') AS has_timeslot
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                JOIN competitions comp ON comp.code_competition = e.code_competition
                LEFT JOIN creneau cr ON cr.id_equipe = c.id_equipe
                WHERE c.code_competition = '$code_competition' 
                AND c.division = '$division'
                ORDER BY c.rank_start";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $code_competition
     * @param $division
     * @return mixed
     * @throws Exception
     */
    public function getLeader($code_competition, $division)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT
              @r := @r + 1 AS rang,
              z.*
            FROM (
                   SELECT
                     e.id_equipe,
                     '$code_competition'                                                                    AS code_competition,
                     e.nom_equipe                                                                           AS equipe,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) +
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0)) - 
                    c.penalite                                                                              AS points,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) +
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))                 AS joues,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0))                 AS gagnes,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))                 AS perdus,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext))          AS sets_pour,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))          AS sets_contre,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) - 
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))          AS diff,
                    c.penalite                                                                              AS penalites,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1, 1, 0))                      AS matches_lost_by_forfeit_count,
                    c.report_count
                   FROM
                     classements c
                     JOIN equipes e ON e.id_equipe = c.id_equipe
                     LEFT JOIN matches m ON 
                       m.code_competition = c.code_competition
                         AND m.division = c.division 
                         AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
                         AND m.match_status != 'ARCHIVED'
                   WHERE c.code_competition = '$code_competition' AND c.division = '$division'
                   GROUP BY e.id_equipe
                   ORDER BY points DESC, diff DESC
                 ) z, (SELECT @r := 0) y LIMIT 0, 1";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) !== 1) {
            throw new Exception("Le champion de la division $division de la compétition $code_competition n'a pas pus être déterminé !");
        }
        return $results[0];
    }

    /**
     * @param $code_competition
     * @param $division
     * @return mixed
     * @throws Exception
     */
    public function getViceLeader($code_competition, $division)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT
              @r := @r + 1 AS rang,
              z.*
            FROM (
                   SELECT
                     e.id_equipe,
                     '$code_competition' AS code_competition,
                     e.nom_equipe                      AS equipe,
                     SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) + 
                     SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
                     SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) + 
                     SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0)) - 
                     c.penalite                      AS points,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) +
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))                 AS joues,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) AS gagnes,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0)) AS perdus,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext))  AS sets_pour,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))  AS sets_contre,
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) - 
                    SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))         AS diff,
                    c.penalite                        AS penalites,
                    SUM(IF(e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1, 1, 0)) + 
                    SUM(IF(e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1, 1, 0)) AS matches_lost_by_forfeit_count,
                    c.report_count
                   FROM
                     classements c
                     JOIN equipes e ON e.id_equipe = c.id_equipe
                     LEFT JOIN matches m ON 
                       m.code_competition = c.code_competition 
                         AND m.division = c.division 
                         AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
                         AND m.match_status != 'ARCHIVED'
                   WHERE c.code_competition = '$code_competition' AND c.division = '$division'
                   GROUP BY e.id_equipe
                   ORDER BY points DESC, diff DESC
                 ) z, (SELECT @r := 0) y LIMIT 1, 1";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (count($results) !== 1) {
            throw new Exception("Le champion de la division $division de la compétition $code_competition n'a pas pus être déterminé !");
        }
        return $results[0];
    }

    /**
     * @param $code_competition
     * @throws Exception
     */
    public function resetRankPoints($code_competition)
    {
        $db = Database::openDbConnection();
        $sql = "UPDATE classements
         SET penalite = 0,
         report_count = 0
        WHERE code_competition = '$code_competition'";
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            throw new Exception("Erreur durant l'opération: " . mysqli_error($db));
        }
    }


}