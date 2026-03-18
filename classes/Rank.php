<?php
/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 02/11/2017
 * Time: 15:51
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Registry.php';

class Rank extends Generic
{
    private Team $team;
    private Registry $registry;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'classements';
        $this->team = new Team();
        $this->registry = new Registry();
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

    public function decrementReportCount($compet, $id_equipe)
    {
        $sql = "UPDATE classements SET report_count = report_count - 1 WHERE id_equipe = $id_equipe AND code_competition = '$compet' AND report_count > 0";
        $this->sql_manager->execute($sql);
        $this->addActivity("Un report a ete retire pour l'equipe " . $this->team->getTeamName($id_equipe));
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
     * All teams in one chapeau for fully random draw
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
                ORDER BY r.creation_date";

        $teams = $this->sql_manager->execute($sql);

        if (empty($teams)) {
            return [
                'teams' => [],
                'total_teams' => 0,
                'nb_pools' => 0,
                'teams_per_pool' => $max_teams_per_pool
            ];
        }

        $total_teams = count($teams);
        $nb_pools = (int)ceil($total_teams / $max_teams_per_pool);

        // Add order based on registration date
        foreach ($teams as $index => &$team) {
            $team['rang'] = $index + 1;
        }

        return [
            'teams' => $teams,
            'total_teams' => $total_teams,
            'nb_pools' => $nb_pools,
            'teams_per_pool' => $max_teams_per_pool
        ];
    }

    /**
     * Get cup finals draw data for knockout rounds (1/8 finals with 16 teams)
     * Based on pool count: generates position names (1er poule X, meilleur 2e)
     * This is used BEFORE pools are played to define the bracket
     * @param int $nb_pools Total number of pools
     * @param bool $has_tableau Whether the cup has tableau haut/bas (Isoardi) or not (Khoury Hanna)
     * @return array
     */
    public function getCupFinalsDraw(int $nb_pools, bool $has_tableau = true): array
    {
        $nb_qualified = 16;

        if ($nb_pools === 0) {
            return [
                'qualified' => [],
                'bracket' => [],
                'nb_pools' => 0,
                'nb_first_places' => 0,
                'nb_best_seconds' => 0
            ];
        }

        // All 1st places qualify
        $nb_first_places = $nb_pools;
        // Remaining slots go to best 2nd places
        $nb_best_seconds = max(0, $nb_qualified - $nb_first_places);

        // Generate qualified positions list
        $qualified = [];

        if ($has_tableau) {
            // Isoardi: pools split into tableau haut (1-7) and tableau bas (8-14)
            $half_pools = (int)ceil($nb_pools / 2);

            // Tableau haut: 1er poule 1 to 1er poule 7
            for ($i = 1; $i <= $half_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => 'haut', 'position' => 1, 'pool' => $i];
            }
            // Tableau bas: 1er poule 8 to 1er poule 14
            for ($i = $half_pools + 1; $i <= $nb_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => 'bas', 'position' => 1, 'pool' => $i];
            }
            // Best 2nd places
            for ($i = 1; $i <= $nb_best_seconds; $i++) {
                $qualified[] = ['label' => "meilleur 2e $i/$nb_best_seconds", 'tableau' => 'mixte', 'position' => 2, 'pool' => null];
            }
        } else {
            // Khoury Hanna: no tableau distinction
            for ($i = 1; $i <= $nb_pools; $i++) {
                $qualified[] = ['label' => "1er poule $i", 'tableau' => null, 'position' => 1, 'pool' => $i];
            }
            for ($i = 1; $i <= $nb_best_seconds; $i++) {
                $qualified[] = ['label' => "meilleur 2e $i/$nb_best_seconds", 'tableau' => null, 'position' => 2, 'pool' => null];
            }
        }

        // Generate bracket for 1/8 finals
        $bracket = $this->generateFinalsBracketFromPositions($qualified, $has_tableau);

        return [
            'qualified' => $qualified,
            'bracket' => $bracket,
            'nb_pools' => $nb_pools,
            'nb_first_places' => $nb_first_places,
            'nb_best_seconds' => $nb_best_seconds
        ];
    }

    /**
     * Generate bracket for 1/8 finals from position labels
     * @param array $qualified List of qualified positions (16 teams)
     * @param bool $has_tableau Whether to cross tableaux
     * @return array
     */
    private function generateFinalsBracketFromPositions(array $qualified, bool $has_tableau): array
    {
        $bracket = [];
        $nb_matches = 8; // 1/8 finals = 8 matches

        if ($has_tableau) {
            // Isoardi: cross tableau haut vs tableau bas
            // Separate qualified by tableau
            $haut = array_values(array_filter($qualified, fn($q) => $q['tableau'] === 'haut'));
            $bas = array_values(array_filter($qualified, fn($q) => $q['tableau'] === 'bas'));
            $seconds = array_values(array_filter($qualified, fn($q) => $q['position'] === 2));

            // Add seconds to the smaller tableau to balance
            foreach ($seconds as $second) {
                if (count($haut) <= count($bas)) {
                    $haut[] = $second;
                } else {
                    $bas[] = $second;
                }
            }

            // Now pair haut[0] vs bas[last], haut[1] vs bas[last-1], etc.
            // This ensures crossing: best haut vs worst bas, etc.
            for ($i = 0; $i < $nb_matches && $i < count($haut); $i++) {
                $opponent_idx = count($bas) - 1 - $i;
                if ($opponent_idx >= 0 && isset($bas[$opponent_idx])) {
                    $bracket[] = [
                        'match' => $i + 1,
                        'team1' => $haut[$i]['label'],
                        'team2' => $bas[$opponent_idx]['label']
                    ];
                }
            }
        } else {
            // Khoury Hanna: simple 1 vs 16, 2 vs 15, etc.
            // All qualified in one list, sorted by seed
            for ($i = 0; $i < $nb_matches; $i++) {
                $team1_idx = $i;
                $team2_idx = count($qualified) - 1 - $i;
                if ($team1_idx < $team2_idx && isset($qualified[$team1_idx]) && isset($qualified[$team2_idx])) {
                    $bracket[] = [
                        'match' => $i + 1,
                        'team1' => $qualified[$team1_idx]['label'],
                        'team2' => $qualified[$team2_idx]['label']
                    ];
                }
            }
        }

        return $bracket;
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
        $half_point = (int)ceil($total_teams / 2);

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
     * Save cup pool assignments from drag&drop interface
     * @param string $code_competition The cup competition code
     * @param array|string $pools JSON string or array of pools, each containing team IDs
     * @return array
     * @throws Exception
     */
    public function saveCupPoolAssignments(string $code_competition, array|string $pools): array
    {
        // Check if user is admin
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier le tirage au sort !");
        }

        // Decode JSON pools if string, otherwise use directly if already an array
        if (is_string($pools)) {
            $poolsArray = json_decode($pools, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Format JSON invalide pour les pools");
            }
        } else {
            $poolsArray = $pools;
        }

        // Validate input
        if (empty($code_competition) || empty($poolsArray)) {
            throw new Exception("code_competition et pools sont requis");
        }

        $pools = $poolsArray;

        // Get database connection for transaction control
        $db = Database::openDbConnection();

        // Start transaction using mysqli_query (not prepared statements)
        mysqli_begin_transaction($db);

        try {
            // Delete existing assignments for this cup competition
            $sql = "DELETE FROM classements WHERE code_competition = ?";
            $bindings = [['type' => 's', 'value' => $code_competition]];
            $this->sql_manager->execute($sql, $bindings);

            // Insert new assignments
            $inserted = 0;
            foreach ($pools as $poolIndex => $pool) {
                $division = strval($poolIndex + 1); // Pool 1, 2, 3... become division "1", "2", "3"...

                if (!is_array($pool) || empty($pool)) {
                    continue;
                }

                foreach ($pool as $rankIndex => $teamId) {
                    if (empty($teamId)) {
                        continue;
                    }

                    $rank_start = $rankIndex + 1; // Position in pool: 1, 2, 3, 4

                    $sql = "INSERT INTO classements (code_competition, division, id_equipe, rank_start) VALUES (?, ?, ?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $code_competition],
                        ['type' => 's', 'value' => $division],
                        ['type' => 'i', 'value' => intval($teamId)],
                        ['type' => 'i', 'value' => $rank_start]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $inserted++;
                }
            }

            mysqli_commit($db);

            $this->addActivity("Tirage au sort sauvegardé pour la compétition $code_competition: $inserted équipes réparties dans " . count($pools) . " poules");

            return [
                'success' => true,
                'inserted' => $inserted,
                'pools_count' => count($pools)
            ];

        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }

    /**
     * Get all cup competitions for dropdown selection
     * @return array
     * @throws Exception
     */
    public function getCupCompetitions(): array
    {
        $sql = "SELECT 
                    c.code_competition,
                    c.libelle,
                    c.id_compet_maitre,
                    IFNULL(DATE_FORMAT(c.start_date, '%d/%m/%Y'), '') AS start_date
                FROM competitions c
                WHERE c.code_competition IN ('c', 'kh')
                ORDER BY c.libelle";
        return $this->sql_manager->execute($sql);
    }

    /**
     * Get teams available for cup draw (reuses existing methods)
     * @param string $code_competition The cup code (c for Isoardi, kh for Khoury Hanna)
     * @return array
     * @throws Exception
     */
    public function getTeamsForCupDraw(string $code_competition): array
    {
        // Use existing methods based on competition type
        if ($code_competition === 'kh') {
            // Khoury Hanna: use getKHCupDrawData
            $data = $this->getKHCupDrawData();
            return array_map(function ($team) {
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'],
                    'division_origine' => '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang']
                ];
            }, $data['teams'] ?? []);
        } elseif ($code_competition === 'c') {
            // Coupe Isoardi (c): use getCupDrawData
            $data = $this->getCupDrawData('m');

            // Combine tableau haut and tableau bas teams
            $teams = [];

            // Add tableau haut teams
            foreach ($data['tableau_haut']['teams'] ?? [] as $team) {
                $teams[] = [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'] ?? '',
                    'division_origine' => $team['division'] ?? '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang'],
                    'chapeau' => $team['chapeau'],
                    'tableau' => 'haut'
                ];
            }

            // Add tableau bas teams
            foreach ($data['tableau_bas']['teams'] ?? [] as $team) {
                $teams[] = [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'],
                    'club' => $team['club'] ?? '',
                    'division_origine' => $team['division'] ?? '-',
                    'rank_start' => $team['rang'],
                    'rang_global' => $team['rang'],
                    'chapeau' => $team['chapeau'],
                    'tableau' => 'bas'
                ];
            }

            return $teams;
        }
        throw new Exception("cette compétition n'est pas une coupe...");
    }

    /**
     * Get current pool assignments for a cup competition
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    public function getCupPoolAssignments(string $code_competition): array
    {
        $sql = "SELECT 
                    c.division AS pool,
                    c.id_equipe,
                    e.nom_equipe,
                    cl.nom AS club,
                    c.rank_start
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                WHERE c.code_competition = ?
                ORDER BY CAST(c.division AS UNSIGNED), c.rank_start";

        $bindings = [['type' => 's', 'value' => $code_competition]];
        $results = $this->sql_manager->execute($sql, $bindings);

        // Group by pool
        $pools = [];
        foreach ($results as $row) {
            $poolNum = intval($row['pool']);
            if (!isset($pools[$poolNum])) {
                $pools[$poolNum] = [];
            }
            $pools[$poolNum][] = $row;
        }

        return $pools;
    }

    /**
     * Assign chapeaux to a list of teams based on their position
     * Creates pools of 3-4 teams with proper chapeau distribution
     * @param array $teams
     * @param int $max_teams_per_pool Maximum teams per pool (default 4)
     * @return array
     */
    private function assignChapeauxToTeams(array $teams, int $max_teams_per_pool): array
    {
        $total = count($teams);
        if ($total === 0) {
            return ['teams' => [], 'chapeaux' => [], 'nb_pools' => 0];
        }

        // Calculate number of pools needed (pools of 3-4 teams)
        $nb_pools = (int)ceil($total / $max_teams_per_pool);

        // Calculate how many pools have max teams vs (max-1) teams
        // nb_pools * max - total = number of pools with (max-1) teams
        $pools_with_less = $nb_pools * $max_teams_per_pool - $total;
        $pools_with_max = $nb_pools - $pools_with_less;

        // Number of chapeaux = max teams per pool (usually 4, but could be 3 if not enough teams)
        $nb_chapeaux = min($max_teams_per_pool, (int)ceil($total / $nb_pools));

        // Build chapeaux info
        // Chapeau 1 to (nb_chapeaux-1) have nb_pools teams each
        // Last chapeau has pools_with_max teams (the pools that have max_teams_per_pool teams)
        $chapeaux = [];
        $cumulative = 0;
        for ($i = 1; $i <= $nb_chapeaux; $i++) {
            // Last chapeau may have fewer teams if some pools have only 3 teams
            if ($i <= $nb_chapeaux - 1 || $pools_with_less === 0) {
                $size = $nb_pools;
            } else {
                // Last chapeau: only pools that have max_teams_per_pool teams
                $size = $pools_with_max;
            }

            // Adjust if we're running out of teams
            $size = min($size, $total - $cumulative);

            if ($size > 0) {
                $chapeaux[$i] = [
                    'numero' => $i,
                    'size' => $size
                ];
                $cumulative += $size;
            }
        }

        // Assign chapeau to each team
        // Team 0 to (nb_pools-1) -> chapeau 1
        // Team nb_pools to (2*nb_pools-1) -> chapeau 2
        // etc.
        foreach ($teams as $index => &$team) {
            $chapeau_num = (int)floor($index / $nb_pools) + 1;
            // Make sure we don't exceed nb_chapeaux
            $chapeau_num = min($chapeau_num, $nb_chapeaux);
            $team['chapeau'] = $chapeau_num;
        }

        return [
            'teams' => $teams,
            'chapeaux' => array_values($chapeaux),
            'nb_pools' => $nb_pools
        ];
    }

    /**
     * Get teams registered for a competition but not assigned to any division
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    public function getUnassignedTeams(string $code_competition): array
    {
        $sql = "SELECT 
                    e.id_equipe,
                    e.nom_equipe,
                    c.nom AS club
                FROM equipes e
                JOIN clubs c ON c.id = e.id_club
                WHERE e.code_competition = ?
                AND e.id_equipe NOT IN (
                    SELECT id_equipe 
                    FROM classements 
                    WHERE code_competition = ?
                )
                ORDER BY e.nom_equipe";
        $bindings = [
            ['type' => 's', 'value' => $code_competition],
            ['type' => 's', 'value' => $code_competition]
        ];
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Get ranks for a competition grouped by division
     * @param string $code_competition
     * @return array
     * @throws Exception
     */
    public function getRanksByCompetitionGroupedByDivision(string $code_competition): array
    {
        $sql = "SELECT 
                    c.id,
                    c.division,
                    c.id_equipe,
                    e.nom_equipe,
                    cl.nom AS club,
                    c.rank_start
                FROM classements c
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                WHERE c.code_competition = ?
                ORDER BY CAST(c.division AS UNSIGNED), c.rank_start";
        $bindings = [['type' => 's', 'value' => $code_competition]];
        $results = $this->sql_manager->execute($sql, $bindings);

        // Group by division
        $divisions = [];
        foreach ($results as $row) {
            $div = $row['division'];
            if (!isset($divisions[$div])) {
                $divisions[$div] = [];
            }
            $divisions[$div][] = $row;
        }

        return $divisions;
    }

    /**
     * Update multiple ranks in batch (for drag & drop reordering)
     * @param string $code_competition
     * @param string $updates JSON array of updates [{id, division, rank_start}, ...]
     * @return array
     * @throws Exception
     */
    public function updateRanksBatch(string $code_competition, string $updates): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier les classements !");
        }

        $updatesArray = json_decode($updates, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour les updates");
        }

        $db = Database::openDbConnection();
        mysqli_begin_transaction($db);

        try {
            $updated = 0;
            foreach ($updatesArray as $update) {
                if (!empty($update['id']) && is_numeric($update['id'])) {
                    // Update existing rank
                    $sql = "UPDATE classements 
                            SET division = ?, rank_start = ? 
                            WHERE id = ? AND code_competition = ?";
                    $bindings = [
                        ['type' => 's', 'value' => $update['division']],
                        ['type' => 'i', 'value' => $update['rank_start']],
                        ['type' => 'i', 'value' => $update['id']],
                        ['type' => 's', 'value' => $code_competition]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $updated++;
                } elseif (isset($update['id_equipe'])) {
                    // Insert new rank (team was unassigned)
                    $sql = "INSERT INTO classements (code_competition, division, id_equipe, rank_start) 
                            VALUES (?, ?, ?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $code_competition],
                        ['type' => 's', 'value' => $update['division']],
                        ['type' => 'i', 'value' => $update['id_equipe']],
                        ['type' => 'i', 'value' => $update['rank_start']]
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $updated++;
                }
            }

            mysqli_commit($db);

            $this->addActivity("Classements mis à jour pour la compétition $code_competition: $updated modifications");

            return [
                'success' => true,
                'updated' => $updated
            ];

        } catch (Exception $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }

    /**
     * Remove a team from a division (move to unassigned)
     * @param int $id Rank ID to delete
     * @return array
     * @throws Exception
     */
    public function removeFromDivision(int $id): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut modifier les classements !");
        }

        $sql = "DELETE FROM classements WHERE id = ?";
        $bindings = [['type' => 'i', 'value' => $id]];
        $this->sql_manager->execute($sql, $bindings);

        $this->addActivity("Équipe retirée du classement (id: $id)");

        return ['success' => true];
    }

    /**
     * Get the raw finals draw from registry for a given finals competition
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array Associative array: match_number => ['team1' => label, 'team2' => label]
     * @throws Exception
     */
    public function getFinalsDrawRaw(string $code_competition_finals): array
    {
        $entries = $this->registry->find_by_key("finals_draw.$code_competition_finals.1_8.");
        if (empty($entries)) {
            return [];
        }

        $matches = [];
        foreach ($entries as $entry) {
            // Key format: finals_draw.{comp}.1_8.{match_num}.{team1|team2}
            $key = $entry['registry_key'];
            $parts = explode('.', $key);
            if (count($parts) !== 5) {
                continue;
            }
            $matchNum = intval($parts[3]);
            $side = $parts[4]; // 'team1' or 'team2'
            if (!isset($matches[$matchNum])) {
                $matches[$matchNum] = [];
            }
            $matches[$matchNum][$side] = $entry['registry_value'];
        }

        ksort($matches);
        return $matches;
    }

    /**
     * Resolve a finals position label (e.g. "1er poule 3") to an actual team
     * using the pool rankings of the parent competition
     * @param string $positionLabel e.g. "1er poule 3", "2e poule 1", "meilleur 2e 1/2"
     * @param string $code_competition_pools The pool competition code (e.g. 'kh', 'c')
     * @return array|null Team data with id_equipe and nom_equipe, or null if not resolvable
     * @throws Exception
     */
    public function resolveFinalsPosition(string $positionLabel, string $code_competition_pools): ?array
    {
        // Parse "1er poule X" or "2e poule X"
        if (preg_match('/^1er poule (\d+)$/', $positionLabel, $m)) {
            $pool = $m[1];
            $rankInPool = 1;
        } elseif (preg_match('/^2e poule (\d+)$/', $positionLabel, $m)) {
            $pool = $m[1];
            $rankInPool = 2;
        } elseif (preg_match('/^meilleur 2e (\d+)\/(\d+)$/', $positionLabel, $m)) {
            // "meilleur 2e X/Y" — requires sorting all 2nd places across pools
            return $this->resolveBestSecond($code_competition_pools, intval($m[1]));
        } else {
            return null;
        }

        // Get the ranking for this specific pool
        try {
            $rankResults = $this->getRank($code_competition_pools, $pool);
        } catch (Exception) {
            return null;
        }

        if (empty($rankResults) || count($rankResults) < $rankInPool) {
            return null;
        }

        $team = $rankResults[$rankInPool - 1];
        return [
            'id_equipe' => $team['id_equipe'],
            'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
        ];
    }

    /**
     * Resolve the Nth best 2nd place across all pools
     * @param string $code_competition_pools
     * @param int $nth Which best 2nd (1 = best, 2 = second best, etc.)
     * @return array|null
     * @throws Exception
     */
    private function resolveBestSecond(string $code_competition_pools, int $nth): ?array
    {
        $divisions = $this->getDivisionsFromCompetition($code_competition_pools);
        $seconds = [];

        foreach ($divisions as $division) {
            $pool = $division['division'];
            try {
                $rankResults = $this->getRank($code_competition_pools, $pool);
            } catch (Exception) {
                continue;
            }
            if (count($rankResults) >= 2) {
                $seconds[] = $rankResults[1]; // 2nd place
            }
        }

        if (empty($seconds)) {
            return null;
        }

        // Sort by points_ponderes DESC, diff_sets_ponderes DESC, diff_points_ponderes DESC
        usort($seconds, function ($a, $b) {
            $cmp = ($b['points_ponderes'] ?? 0) <=> ($a['points_ponderes'] ?? 0);
            if ($cmp !== 0) return $cmp;
            $cmp = ($b['diff_sets_ponderes'] ?? 0) <=> ($a['diff_sets_ponderes'] ?? 0);
            if ($cmp !== 0) return $cmp;
            return ($b['diff_points_ponderes'] ?? 0) <=> ($a['diff_points_ponderes'] ?? 0);
        });

        if ($nth > count($seconds)) {
            return null;
        }

        $team = $seconds[$nth - 1];
        return [
            'id_equipe' => $team['id_equipe'],
            'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
        ];
    }

    /**
     * Save a single finals draw entry in registry
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param int $matchNum Match number (1-8)
     * @param string $side 'team1' or 'team2'
     * @param string $positionLabel e.g. "1er poule 3"
     * @return bool
     * @throws Exception
     */
    public function saveFinalsDrawEntry(string $code_competition_finals, int $matchNum, string $side, string $positionLabel): bool
    {
        $key = "finals_draw.$code_competition_finals.1_8.$matchNum.$side";

        // Check if entry already exists
        $existing = $this->registry->find_by_key($key);
        if (!empty($existing)) {
            // Update existing
            $sql = "UPDATE registry SET registry_value = ? WHERE registry_key = ?";
        } else {
            // Insert new
            $sql = "INSERT INTO registry SET registry_value = ?, registry_key = ?";
        }
        $bindings = [
            ['type' => 's', 'value' => $positionLabel],
            ['type' => 's', 'value' => $key],
        ];
        $this->sql_manager->execute($sql, $bindings);
        return true;
    }

    /**
     * Save a full finals draw (all 8 matches) in registry
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param string $drawJson JSON array of [{match, team1, team2}, ...]
     * @return array
     * @throws Exception
     */
    public function saveFullFinalsDraw(string $code_competition_finals, string $drawJson): array
    {
        $drawArray = json_decode($drawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour le tirage");
        }

        // Delete existing draw entries for this competition (only 1_8, not host)
        $existingEntries = $this->registry->find_by_key("finals_draw.$code_competition_finals.1_8.");
        foreach ($existingEntries as $entry) {
            $sql = "DELETE FROM registry WHERE id = ?";
            $this->sql_manager->execute($sql, [['type' => 'i', 'value' => $entry['id']]]);
        }

        // Insert new entries
        $count = 0;
        foreach ($drawArray as $match) {
            $matchNum = $match['match'];
            $this->saveFinalsDrawEntry($code_competition_finals, $matchNum, 'team1', $match['team1']);
            $this->saveFinalsDrawEntry($code_competition_finals, $matchNum, 'team2', $match['team2']);
            $count += 2;
        }

        $this->addActivity("Tirage des phases finales sauvegardé pour $code_competition_finals: " . count($drawArray) . " matchs");

        return [
            'success' => true,
            'entries_count' => $count,
        ];
    }

    /**
     * Get the parent pool competition code from a finals competition code
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return string|null e.g. 'c' or 'kh'
     * @throws Exception
     */
    private function getParentCompetitionCode(string $code_competition_finals): ?string
    {
        $sql = "SELECT id_compet_maitre FROM competitions WHERE code_competition = ?";
        $bindings = [['type' => 's', 'value' => $code_competition_finals]];
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            return null;
        }
        return $results[0]['id_compet_maitre'];
    }

    /**
     * Get the full resolved finals bracket for a competition
     * Reads draw from registry, resolves positions to real teams, builds full bracket
     * Uses sort_cup_rank to fetch all rankings in a single query for performance
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array Full bracket structure with rounds
     * @throws Exception
     */
    public function getFinalsDrawResolved(string $code_competition_finals): array
    {
        $parentCode = $this->getParentCompetitionCode($code_competition_finals);

        // Get raw draw from registry
        $rawDraw = $this->getFinalsDrawRaw($code_competition_finals);

        // Pre-fetch all rankings in ONE query using sort_cup_rank
        $allRankings = [];
        $ranksByPool = [];
        $bestSeconds = [];

        if ($parentCode) {
            $allRankings = $this->sort_cup_rank($parentCode);

            // Build lookup: pool -> [1st place, 2nd place, ...]
            foreach ($allRankings as $team) {
                $pool = $team['division'];
                if (!isset($ranksByPool[$pool])) {
                    $ranksByPool[$pool] = [];
                }
                $ranksByPool[$pool][] = $team;
            }

            // Collect all 2nd places for "meilleur 2e" resolution
            foreach ($ranksByPool as $pool => $teams) {
                if (count($teams) >= 2) {
                    $bestSeconds[] = $teams[1]; // 2nd place (already sorted by rang_poule)
                }
            }

            // Sort best seconds by points_ponderes DESC, diff_sets_ponderes DESC, diff_points_ponderes DESC
            usort($bestSeconds, function ($a, $b) {
                $cmp = ($b['points_ponderes'] ?? 0) <=> ($a['points_ponderes'] ?? 0);
                if ($cmp !== 0) return $cmp;
                $cmp = ($b['diff_sets_ponderes'] ?? 0) <=> ($a['diff_sets_ponderes'] ?? 0);
                if ($cmp !== 0) return $cmp;
                return ($b['diff_points_ponderes'] ?? 0) <=> ($a['diff_points_ponderes'] ?? 0);
            });
        }

        // Build 1/8 finals using cached data
        $eighthFinals = [];
        foreach ($rawDraw as $matchNum => $match) {
            $team1Resolved = $this->resolvePositionFromCache($match['team1'] ?? '', $ranksByPool, $bestSeconds);
            $team2Resolved = $this->resolvePositionFromCache($match['team2'] ?? '', $ranksByPool, $bestSeconds);

            $eighthFinals[] = [
                'match' => $matchNum,
                'team1_label' => $match['team1'] ?? null,
                'team2_label' => $match['team2'] ?? null,
                'team1_resolved' => $team1Resolved,
                'team2_resolved' => $team2Resolved,
            ];
        }

        // Get host draw for quarters and semis
        $hostDraw = $this->getFinalsHostDraw($code_competition_finals);

        return [
            'code_competition' => $code_competition_finals,
            'parent_competition' => $parentCode,
            'rounds' => [
                '1_8' => $eighthFinals,
            ],
            'host_draw' => $hostDraw,
        ];
    }

    /**
     * Get the host draw for quarters and semi-finals
     * Format: which winner of previous round hosts the next match
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @return array ['1_4' => [1 => 2, 2 => 3, ...], '1_2' => [1 => 1, ...]]
     */
    public function getFinalsHostDraw(string $code_competition_finals): array
    {
        $result = [
            '1_4' => [], // For each quarter (1-4), which 1/8 winner hosts (1-8)
            '1_2' => [], // For each semi (1-2), which 1/4 winner hosts (1-4)
        ];

        // Read from registry
        $entries = $this->registry->find_by_key("finals_draw.$code_competition_finals.host.");
        foreach ($entries as $entry) {
            // Key format: finals_draw.{comp}.host.{round}.{match_num}
            $key = $entry['registry_key'];
            $parts = explode('.', $key);
            if (count($parts) === 5) {
                $round = $parts[3]; // '1_4' or '1_2'
                $matchNum = intval($parts[4]);
                $hostWinner = intval($entry['registry_value']);
                if (isset($result[$round])) {
                    $result[$round][$matchNum] = $hostWinner;
                }
            }
        }

        return $result;
    }

    /**
     * Save the host draw for quarters and semi-finals
     * @param string $code_competition_finals e.g. 'cf' or 'kf'
     * @param string $hostDrawJson JSON with format {1_4: {1: 2, 2: 4, ...}, 1_2: {1: 1, ...}}
     * @return array
     * @throws Exception
     */
    public function saveFinalsHostDraw(string $code_competition_finals, string $hostDrawJson): array
    {
        $hostDraw = json_decode($hostDrawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Format JSON invalide pour le tirage de réception");
        }

        // Delete existing host draw entries for this competition
        $existingEntries = $this->registry->find_by_key("finals_draw.$code_competition_finals.host.");
        foreach ($existingEntries as $entry) {
            $sql = "DELETE FROM registry WHERE id = ?";
            $this->sql_manager->execute($sql, [['type' => 'i', 'value' => $entry['id']]]);
        }

        // Insert new entries
        $count = 0;
        foreach (['1_4', '1_2'] as $round) {
            if (isset($hostDraw[$round]) && is_array($hostDraw[$round])) {
                foreach ($hostDraw[$round] as $matchNum => $hostWinner) {
                    // Skip null values
                    if ($hostWinner === null) {
                        continue;
                    }
                    $key = "finals_draw.$code_competition_finals.host.$round.$matchNum";
                    $sql = "INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)";
                    $bindings = [
                        ['type' => 's', 'value' => $key],
                        ['type' => 's', 'value' => (string)$hostWinner],
                    ];
                    $this->sql_manager->execute($sql, $bindings);
                    $count++;
                }
            }
        }

        $this->addActivity("Tirage de réception des phases finales sauvegardé pour $code_competition_finals: $count entrées");

        return [
            'success' => true,
            'entries_count' => $count,
        ];
    }

    /**
     * Resolve a position label using pre-cached rankings (no additional queries)
     * @param string $positionLabel e.g. "1er poule 3", "meilleur 2e 1/7"
     * @param array $ranksByPool Pool rankings indexed by pool number
     * @param array $bestSeconds Sorted array of best 2nd places
     * @return array|null
     */
    private function resolvePositionFromCache(string $positionLabel, array $ranksByPool, array $bestSeconds): ?array
    {
        if (empty($positionLabel)) {
            return null;
        }

        // Parse "1er poule X"
        if (preg_match('/^1er poule (\d+)$/', $positionLabel, $m)) {
            $pool = intval($m[1]);
            if (isset($ranksByPool[$pool]) && count($ranksByPool[$pool]) >= 1) {
                $team = $ranksByPool[$pool][0];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        // Parse "2e poule X"
        if (preg_match('/^2e poule (\d+)$/', $positionLabel, $m)) {
            $pool = intval($m[1]);
            if (isset($ranksByPool[$pool]) && count($ranksByPool[$pool]) >= 2) {
                $team = $ranksByPool[$pool][1];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        // Parse "meilleur 2e X/Y"
        if (preg_match('/^meilleur 2e (\d+)\/(\d+)$/', $positionLabel, $m)) {
            $nth = intval($m[1]);
            if ($nth <= count($bestSeconds)) {
                $team = $bestSeconds[$nth - 1];
                return [
                    'id_equipe' => $team['id_equipe'],
                    'nom_equipe' => $team['equipe'] ?? $team['nom_equipe'] ?? null,
                ];
            }
            return null;
        }

        return null;
    }
}