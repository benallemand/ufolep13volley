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
                    c.rank_start,
                    c.will_register_again
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
                             $will_register_again,
                             $dirtyFields): int|array|string|null
    {
        return $this->save(array(
            'id' => $id,
            'code_competition' => $code_competition,
            'division' => $division,
            'id_equipe' => $id_equipe,
            'rank_start' => $rank_start,
            'will_register_again' => $will_register_again,
            'dirtyFields' => $dirtyFields,
        ));
    }

    public function save($inputs)
    {
        $bindings = array();
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
                case 'will_register_again':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
                    );
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

    /**
     * @throws Exception
     */
    public function get_rank_for_cup($code_competition): int|array|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_for_cup.sql');
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function sort_cup_rank($code_competition): array|int|string
    {
        $results = $this->get_rank_for_cup($code_competition);
        foreach ($results as $index => $result) {
            $results[$index]['rang_poule'] = $this->getTeamRank($code_competition, $result['division'], $result['id_equipe']);
        }
        $rangs_poules = array_column($results, 'rang_poule');

        array_multisort($rangs_poules, SORT_ASC, $results);
        foreach ($results as $index => $result) {
            $results[$index]['rang'] = $index + 1;
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function getRank($competition, $division): array|int|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition_division.sql');
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $competition);
        $bindings[] = array('type' => 's', 'value' => $competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getDivisions()
    {
        $sql = "SELECT
        DISTINCT c.division,
        c.code_competition
      FROM classements c";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getTeamRank($competition, $division, $idTeam)
    {
        $results = $this->getRank($competition, $division);
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

    /**
     * @param string $code_competition
     * @param string $division
     * @param int $id_equipe
     * @param int $rank_start
     * @return array|int|string|null
     * @throws Exception
     */
    public function insert(string $code_competition, string $division, int $id_equipe, int $rank_start): array|int|string|null
    {
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start)
                VALUES (?, ?, ?, ?)";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 'i', 'value' => $rank_start);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param string $code_competition
     * @return array|int|string|null
     * @throws Exception
     */
    public function get_full_competition_rank(string $code_competition): array|int|string|null
    {
        $sql = "SELECT @r := @r + 1 AS rang,
                       z.id_equipe, z.equipe, z.division
                FROM (SELECT e.id_equipe,
                             'm'                                                                            AS code_competition,
                             c.division,
                             e.nom_equipe                                                                   AS equipe,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0))
                                 - c.penalite                                                               AS points,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))        AS joues,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0))        AS gagnes,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))        AS perdus,
                             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) AS sets_pour,
                             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom)) AS sets_contre,
                             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) -
                             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext, m.score_equipe_dom)) AS diff,
                             c.penalite                                                                     AS penalites,
                             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1, 1, 0))             AS matches_lost_by_forfeit_count,
                             c.report_count
                      FROM classements c
                               JOIN equipes e ON e.id_equipe = c.id_equipe
                               LEFT JOIN matches m ON
                                  m.code_competition = c.code_competition
                              AND m.division = c.division
                              AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
                              AND m.match_status != 'ARCHIVED'
                      WHERE c.code_competition = ?
                      AND e.is_cup_registered = 1
                      GROUP BY e.id_equipe, '%name', e.nom_equipe, c.penalite, c.report_count
                      ORDER BY division, points DESC, diff DESC, c.rank_start) z,
                     (SELECT @r := 0) y";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function delete_competition($id_competition)
    {
        $sql = "DELETE 
                FROM classements 
                WHERE code_competition IN (SELECT code_competition
                                           FROM competitions 
                                           WHERE id = ?)";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function insert_from_register($id_competition)
    {
        $sql = "INSERT INTO classements(code_competition, division, id_equipe, rank_start) 
                SELECT c.code_competition, r.division, e.id_equipe, r.rank_start 
                FROM register r 
                JOIN competitions c on r.id_competition = c.id
                JOIN equipes e on e.code_competition = c.code_competition
                                  AND e.nom_equipe = r.new_team_name
                WHERE r.id_competition = ?
                ORDER BY code_competition, division, rank_start";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_competition);
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function get_winner_teams_from_previous_day($division, $code_competition, $previous_day_nommage): int|array|string|null
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
                  AND (
                    e.id_equipe IN (SELECT id_equipe_dom
                                    FROM matches
                                    WHERE code_competition = '$code_competition'
                                      AND score_equipe_dom = 3
                                      AND id_journee IN (SELECT id
                                                         FROM journees
                                                         WHERE code_competition = '$code_competition'
                                                           AND nommage = '$previous_day_nommage'))
                        OR
                    e.id_equipe IN (SELECT id_equipe_ext
                                    FROM matches
                                    WHERE code_competition = '$code_competition'
                                      AND score_equipe_ext = 3
                                      AND id_journee IN (SELECT id
                                                         FROM journees
                                                         WHERE code_competition = '$code_competition'
                                                           AND nommage = '$previous_day_nommage'))
                    )
                ORDER BY c.rank_start";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getTeamDivision(mixed $code_competition, mixed $id_equipe)
    {
        $result = $this->sql_manager->execute(
            $this->getSql("c.code_competition = '$code_competition' AND c.id_equipe = $id_equipe"));
        if (count($result) !== 1) {
            $team = $this->team->getTeam($id_equipe);
            $team_name = $team['nom_equipe'];
            throw new Exception("division non trouvée pour l'équipe $team_name dans la compétition $code_competition !");
        }
        return $result[0]['division'];
    }


}