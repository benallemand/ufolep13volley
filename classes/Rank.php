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
        $results = $this->getRank($code_competition, $division);
        if (count($results) === 0) {
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
        $results = $this->getRank($code_competition, $division);
        if (count($results) <= 1) {
            throw new Exception("Le vice-champion de la division $division de la compétition $code_competition n'a pas pu être déterminé !");
        }
        return $results[1];
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
        if (!empty($inputs['id'])) {
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
        // Le rang_poule est maintenant directement inclus dans les résultats
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
    public function getRank($competition, $division, $id_team = null): array|int|string|null
    {
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $competition);
        $bindings[] = array('type' => 's', 'value' => $division);
        if (empty($id_team)) {
            $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition_division.sql');
        } else {
            $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition_division_id_team.sql');
            $bindings[] = array('type' => 'i', 'value' => $id_team);
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getDivisions()
    {
        $sql = "SELECT
        DISTINCT c.division,
        c.code_competition,
        comp.libelle AS libelle_competition
      FROM classements c
      JOIN competitions comp ON comp.code_competition = c.code_competition";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function getTeamRank($competition, $division, $idTeam)
    {
        $results = $this->getRank($competition, $division, $idTeam);
        if (count($results) !== 1) {
            return '';
        }
        return $results[0]['rang'];
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
        $sql = file_get_contents(__DIR__ . '/../sql/get_rank_by_competition.sql');
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
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
        $sql = "INSERT INTO classements(
                        code_competition,
                        division,
                        id_equipe,
                        rank_start) 
                SELECT c.code_competition, 
                       CASE WHEN r.division IS NULL THEN 'X' ELSE r.division END AS division, 
                       e.id_equipe, 
                       CASE WHEN r.rank_start IS NULL THEN 1 ELSE r.rank_start END AS rank_start  
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
                                    FROM matchs_view
                                    WHERE code_competition = '$code_competition'
                                      AND score_equipe_dom = 3
                                      AND id_journee IN (SELECT id
                                                         FROM journees
                                                         WHERE code_competition = '$code_competition'
                                                           AND nommage = '$previous_day_nommage'))
                        OR
                    e.id_equipe IN (SELECT id_equipe_ext
                                    FROM matchs_view
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

    /**
     * Get teams registered for Khoury Hanna cup, sorted by registration date
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     * @throws Exception
     */
    public function getKHCupDrawData(int $max_teams_per_pool = 4): array
    {
        $sql = "SELECT 
                    r.id AS id_register,
                    COALESCE(e.id_equipe, r.old_team_id) AS id_equipe,
                    r.new_team_name AS equipe,
                    c.nom AS club,
                    DATE_FORMAT(r.creation_date, '%d/%m/%Y %H:%i') AS date_inscription
                FROM register r
                JOIN clubs c ON c.id = r.id_club
                JOIN competitions comp ON comp.id = r.id_competition
                LEFT JOIN equipes e ON e.nom_equipe = r.new_team_name 
                    AND e.code_competition = comp.code_competition
                WHERE comp.code_competition = 'kh'
                ORDER BY r.creation_date ASC";
        
        $teams = $this->sql_manager->execute($sql);
        
        if (empty($teams)) {
            return [
                'teams' => [],
                'chapeaux' => [],
                'total_teams' => 0,
                'nb_pools' => 0
            ];
        }
        
        $total_teams = count($teams);
        
        // Add rank based on registration order
        foreach ($teams as $index => &$team) {
            $team['rang'] = $index + 1;
        }
        
        // Assign chapeaux
        $result = $this->assignChapeauxToTeams($teams, $max_teams_per_pool);
        
        return [
            'teams' => $result['teams'],
            'chapeaux' => $result['chapeaux'],
            'total_teams' => $total_teams,
            'nb_pools' => $result['nb_pools']
        ];
    }

    /**
     * Get cup draw data with chapeaux based on ranking position for a competition
     * Chapeaux are created separately for tableau haut and tableau bas
     * Each chapeau has the same size to ensure max 4 teams per pool
     * @param string $code_competition
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     * @throws Exception
     */
    public function getCupDrawData(string $code_competition, int $max_teams_per_pool = 4): array
    {
        $teams = $this->get_full_competition_rank($code_competition);
        
        if (empty($teams)) {
            return [
                'teams' => [],
                'total_teams' => 0,
                'half_point' => 0,
                'tableau_haut' => ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0],
                'tableau_bas' => ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0]
            ];
        }
        
        $total_teams = count($teams);
        $half_point = (int) ceil($total_teams / 2);
        
        $tableau_haut_teams = array_slice($teams, 0, $half_point);
        $tableau_bas_teams = array_slice($teams, $half_point);
        
        $tableau_haut = $this->assignChapeauxToTeams($tableau_haut_teams, $max_teams_per_pool);
        $tableau_bas = $this->assignChapeauxToTeams($tableau_bas_teams, $max_teams_per_pool);
        
        return [
            'teams' => $teams,
            'total_teams' => $total_teams,
            'half_point' => $half_point,
            'tableau_haut' => $tableau_haut,
            'tableau_bas' => $tableau_bas
        ];
    }
    
    /**
     * Calculate optimal number of chapeaux for equal distribution
     * All chapeaux must have same size, and nb_chapeaux <= max_teams_per_pool
     * @param int $total_teams
     * @param int $max_teams_per_pool
     * @return array ['nb_chapeaux' => int, 'chapeau_size' => int]
     */
    private function calculateOptimalChapeaux(int $total_teams, int $max_teams_per_pool): array
    {
        for ($nb_chapeaux = $max_teams_per_pool; $nb_chapeaux >= 1; $nb_chapeaux--) {
            if ($total_teams % $nb_chapeaux === 0) {
                return [
                    'nb_chapeaux' => $nb_chapeaux,
                    'chapeau_size' => $total_teams / $nb_chapeaux
                ];
            }
        }
        return ['nb_chapeaux' => 1, 'chapeau_size' => $total_teams];
    }
    
    /**
     * Assign chapeaux to a list of teams based on their position
     * Uses equal-sized chapeaux to ensure fair pool distribution
     * @param array $teams
     * @param int $max_teams_per_pool
     * @return array
     */
    private function assignChapeauxToTeams(array $teams, int $max_teams_per_pool): array
    {
        $total = count($teams);
        if ($total === 0) {
            return ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0];
        }
        
        $optimal = $this->calculateOptimalChapeaux($total, $max_teams_per_pool);
        $nb_chapeaux = $optimal['nb_chapeaux'];
        $chapeau_size = $optimal['chapeau_size'];
        
        $chapeaux = [];
        for ($i = 1; $i <= $nb_chapeaux; $i++) {
            $start_index = ($i - 1) * $chapeau_size;
            $chapeaux[$i] = [
                'numero' => $i,
                'start_index' => $start_index,
                'end_index' => $start_index + $chapeau_size - 1,
                'size' => $chapeau_size
            ];
        }
        
        foreach ($teams as $index => &$team) {
            $chapeau_num = (int) floor($index / $chapeau_size) + 1;
            $team['chapeau'] = $chapeau_num;
        }
        
        return [
            'teams' => $teams,
            'chapeaux' => array_values($chapeaux),
            'nb_pools' => $chapeau_size
        ];
    }


}