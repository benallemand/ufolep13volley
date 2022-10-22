<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 02/11/2017
 * Time: 15:51
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';

class Rank extends Generic
{
    private Team $team;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'classements';
        $this->team = new Team();
    }


    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                    c.id,
                    c.code_competition,
                    comp.libelle AS nom_competition,
                    c.division,
                    c.id_equipe,
                    e.nom_equipe,
                    c.penalite,
                    c.report_count,
                    c.rank_start
                FROM classements c
                JOIN competitions comp ON comp.code_competition = c.code_competition
                JOIN equipes e ON e.id_equipe = c.id_equipe
                WHERE $query
                ORDER BY c.code_competition, CAST(c.division AS UNSIGNED), c.rank_start";
    }

    /**
     * @param string|null $query
     * @return array
     * @throws Exception
     */
    public function getRanks(?string $query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCompetitions(): array
    {
        $sql = "SELECT  c.code_competition, 
                        IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date
            FROM competitions c";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getDivisionsFromCompetition($code_competition): array
    {
        $sql = "SELECT DISTINCT division 
            FROM classements
            WHERE code_competition = '$code_competition'
            ORDER BY CAST(division AS UNSIGNED)";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $division
     * @param $code_competition
     * @return array
     * @throws Exception
     */
    public function getTeamsFromDivisionAndCompetition($division, $code_competition): array
    {
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
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $code_competition
     * @param $division
     * @return mixed
     * @throws Exception
     */
    public function getLeader($code_competition, $division)
    {
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
        $results = $this->sql_manager->execute($sql);
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
    public function getViceLeader($code_competition, $division): mixed
    {
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
        $results = $this->sql_manager->execute($sql);
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
        $sql = "UPDATE classements
         SET penalite = 0,
         report_count = 0
        WHERE code_competition = '$code_competition'";
        $this->sql_manager->execute($sql);
    }

    public function get_report_count($team_id, mixed $code_competition)
    {
        $sql = "SELECT report_count 
            FROM classements 
            WHERE id_equipe = $team_id 
            AND code_competition = '$code_competition'";
        $results = $this->sql_manager->execute($sql);
        return intval($results[0]['report_count']);
    }

    /**
     * @throws Exception
     */
    public function saveRank($id,
                             $code_competition,
                             $division,
                             $id_equipe,
                             $rank_start,
                             $dirtyFields): int|array|string|null
    {
        return $this->save(array(
            'id' => $id,
            'code_competition' => $code_competition,
            'division' => $division,
            'id_equipe' => $id_equipe,
            'rank_start' => $rank_start,
            'dirtyFields' => $dirtyFields,
        ));
    }

    public function save($inputs)
    {
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " classements SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_equipe':
                case 'rank_start':
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
                case 'division':
                    if (is_numeric($value)) {
                        $bindings[] = array('type' => 'i', 'value' => $value);
                    } else {
                        $bindings[] = array('type' => 's', 'value' => $value);
                    }
                    $sql .= "$key = ?,";
                    break;
                default:
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (empty($inputs['id'])) {
        } else {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getRank($competition, $division)
    {
        $sql = "SELECT
  @r := @r + 1 AS rang,
  z.*
FROM (
       SELECT
         e.id_equipe,
         '$competition' AS code_competition,
                  e.nom_equipe                      AS equipe,
         SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
         SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0))
         - c.penalite                      AS points,
         SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))                 AS joues,
         SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) AS gagnes,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0)) AS perdus,
         SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext))  AS sets_pour,
         SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))  AS sets_contre,
         SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) - SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom))         AS diff,
         c.penalite                        AS penalites,
         SUM(IF(e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1, 1, 0)) + SUM(IF(e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1, 1, 0)) AS matches_lost_by_forfeit_count,
          c.report_count
       FROM
         classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         LEFT JOIN matches m ON 
           m.code_competition = c.code_competition 
             AND m.division = c.division 
             AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
             AND m.match_status != 'ARCHIVED'
       WHERE c.code_competition = '$competition' AND c.division = '$division'
       GROUP BY e.id_equipe, '%name', e.nom_equipe, c.penalite, c.report_count
       ORDER BY points DESC, diff DESC, c.rank_start
     ) z, (SELECT @r := 0) y";
        return $this->sql_manager->execute($sql);
    }

    public function getDivisions()
    {
        $sql = "SELECT
        DISTINCT c.division,
        c.code_competition
      FROM classements c";
        return $this->sql_manager->execute($sql);
    }

    public function getTeamRank($competition, $league, $idTeam)
    {
        $results = $this->getRank($competition, $league);
        foreach ($results as $data) {
            if ($data['id_equipe'] === $idTeam) {
                return $data['rang'];
            }
        }
        return '';
    }

    /**
     * @throws Exception
     */
    public function addPenalty($compet, $id_equipe)
    {
        $sql = "SELECT penalite,division FROM classements WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer les pénalités de l'équipe !");
        }
        $data = $results[0];
        $penalite = $data['penalite'];
        $penalite++;
        $sqlmaj = "UPDATE classements SET penalite = $penalite WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
        $this->sql_manager->execute($sqlmaj);
        $this->addActivity("Une penalite a ete infligee a l'equipe " . $this->team->getTeamName($id_equipe));
        return true;
    }

    public function removePenalty($compet, $id_equipe)
    {
        $sql = "SELECT penalite,division FROM classements WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer les pénalités de l'équipe !");
        }
        $data = $results[0];
        $penalite = $data['penalite'];
        $penalite--;
        if ($penalite < 0) {
            $penalite = 0;
        }
        $sqlmaj = "UPDATE classements SET penalite = $penalite WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
        $this->sql_manager->execute($sqlmaj);
        $this->addActivity("Une penalite a ete annulee pour l'equipe " . $this->team->getTeamName($id_equipe));
        return true;
    }

    public function incrementReportCount($compet, $id_equipe)
    {
        $sql = "UPDATE classements SET report_count = report_count + 1 WHERE id_equipe = $id_equipe AND code_competition = '$compet'";
        $this->sql_manager->execute($sql);
        $this->addActivity("Un report a ete comptabilise pour l'equipe " . $this->team->getTeamName($id_equipe));
        return true;
    }

    public function decrementReportCount($compet, $equipe)
    {
        $sql = "UPDATE classements SET report_count = report_count - 1 WHERE id_equipe = $equipe AND code_competition = '$compet' AND report_count > 0";
        $this->sql_manager->execute($sql);
        $this->addActivity("Un report a ete retire pour l'equipe " . $this->team->getTeamName($equipe));
        return true;
    }


}