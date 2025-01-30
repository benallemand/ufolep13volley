<?php
require_once __DIR__ . '/../classes/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Rank.php';
require_once __DIR__ . '/../classes/Day.php';
require_once __DIR__ . '/../classes/MatchMgr.php';

class Competition extends Generic
{
    private Rank $rank;
    private MatchMgr $match;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'competitions';
        $this->rank = new Rank();
        $this->match = new MatchMgr();
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
        c.id,
        c.code_competition,
        c.libelle,
        c.id_compet_maitre,
        IF(c.start_date, DATE_FORMAT(c.start_date, '%d/%m/%Y'), NULL) AS start_date,
        IF(c.start_register_date, DATE_FORMAT(c.start_register_date, '%d/%m/%Y'), NULL) AS start_register_date,
        IF(c.limit_register_date, DATE_FORMAT(c.limit_register_date, '%d/%m/%Y'), NULL) AS limit_register_date,
        c.is_home_and_away+0 AS is_home_and_away,
        d.date_limite AS limit_date
        FROM competitions c
        LEFT JOIN dates_limite d ON d.code_competition = c.code_competition
        WHERE $query
        ORDER BY libelle";
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function getCompetitions(string $query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function isCompetitionOver($id): bool
    {
        $sql = "SELECT date_limite FROM dates_limite WHERE code_competition IN (SELECT code_competition FROM competitions WHERE id = $id)";
        $results = $this->sql_manager->execute($sql);
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

    /**
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function isCompetitionStarted($id): bool
    {
        $sql = "SELECT DATE_FORMAT(start_date, '%d/%m/%Y') AS start_date FROM competitions WHERE id = $id";
        $results = $this->sql_manager->execute($sql);
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

    /**
     * @return array
     * @throws Exception
     */
    public function get_friendships(): array
    {
        $sql = "SELECT f.id, 
                       f.id_club_1, 
                       f.id_club_2, 
                       c1.nom AS nom_club_1,
                       c2.nom AS nom_club_2
                FROM friendships f
                JOIN clubs c1 ON c1.id = f.id_club_1
                JOIN clubs c2 ON c2.id = f.id_club_2";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $id
     * @param $id_club_1
     * @param $id_club_2
     * @param null $dirtyFields
     * @throws Exception
     */
    public function save_friendships(
        $id,
        $id_club_1,
        $id_club_2,
        $dirtyFields = null
    )
    {
        $inputs = array(
            'id' => $id,
            'id_club_1' => $id_club_1,
            'id_club_2' => $id_club_2,
            'dirtyFields' => $dirtyFields,
        );
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " friendships SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_club_1':
                case 'id_club_2':
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
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
        if (!empty($inputs['id'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id']
            );
            $sql .= " WHERE id = ?";
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete_friendships($ids)
    {
        $sql = "DELETE FROM friendships WHERE id IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_blacklist_by_city(): array
    {
        $sql = "SELECT  bbc.id,
                        bbc.city,
                        DATE_FORMAT(bbc.from_date, '%d/%m/%Y') AS from_date ,
                        DATE_FORMAT(bbc.to_date, '%d/%m/%Y') AS to_date 
                FROM blacklist_by_city bbc";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_city(): array
    {
        $sql = "SELECT DISTINCT ville AS name 
                FROM gymnase
                ORDER BY ville";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $id
     * @param $city
     * @param $from_date
     * @param $to_date
     * @param null $dirtyFields
     * @throws Exception
     */
    public function save_blacklist_by_city(
        $id,
        $city,
        $from_date,
        $to_date,
        $dirtyFields = null
    )
    {
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'city' => $city,
            'from_date' => $from_date,
            'to_date' => $to_date,
        );
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_by_city SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'from_date':
                case 'to_date':
                    $bindings[] = array(
                        'type' => 's',
                        'value' => $value
                    );
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'city':
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
        if (!empty($inputs['id'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id']
            );
            $sql .= " WHERE id = ?";
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete_blacklist_by_city($ids)
    {
        $sql = "DELETE FROM blacklist_by_city WHERE id IN($ids)";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param string $code_competition
     * @return void
     * @throws Exception
     */
    public function generate_menu(string $code_competition): void
    {
        $label = match ($code_competition) {
            'm', 'f', 'mo' => 'Division',
            'c', 'kh' => 'Poule',
            default => '?',
        };
        $result_string = "";
        $competitions = $this->getCompetitions("c.code_competition = '$code_competition'");
        foreach ($competitions as $competition) {
            if (in_array($code_competition, array('cf', 'kf'))) {
                $result_string .= "<li><a href='#matches/$code_competition'>" . $competition['libelle'] . "</a></li>";
                continue;
            }
            $result_string .= "<li class='dropdown-header'><h4>" . $competition['libelle'] . "</h4></li>";
            if (in_array($code_competition, array('c', 'kh'))) {
                $result_string .= "<li><a href='/rank_for_cup.php?code_competition=$code_competition'>Classement général</a></li>";
            }
            $divisions = $this->rank->getDivisionsFromCompetition($code_competition);
            foreach ($divisions as $division) {
                $division_string = $division['division'];
                $result_string .= "<li><a href='#championship/$code_competition/$division_string'>$label $division_string</a></li>";
            }
        }
        echo $result_string;
    }

    /**
     * @throws Exception
     */
    public function getTournaments(): array|int|string|null
    {
        $sql = "SELECT c.id, c.code_competition, c.libelle 
        FROM competitions c 
        WHERE c.code_competition IN (SELECT DISTINCT code_competition FROM classements) 
        ORDER BY c.libelle";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveCompetition(
        $code_competition,
        $libelle,
        $id_compet_maitre,
        $start_date,
        $start_register_date,
        $limit_register_date,
        $is_home_and_away,
        $id = null,
        $dirtyFields = null
    ): int|array|string|null
    {
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'code_competition' => $code_competition,
            'libelle' => $libelle,
            'id_compet_maitre' => $id_compet_maitre,
            'start_date' => $start_date,
            'start_register_date' => $start_register_date,
            'limit_register_date' => $limit_register_date,
            'is_home_and_away' => $is_home_and_away,
        );
        return $this->save($inputs);
    }

    public function save($inputs): int|array|string|null
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " competitions SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'start_date':
                case 'start_register_date':
                case 'limit_register_date':
                    if(empty($value)) {
                        $sql .= "$key = NULL,";
                        break;
                    }
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'is_home_and_away':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array('type' => 'i', 'value' => $val);
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
    public function getCompetition($code_competition)
    {
        $sql = "SELECT 
        id,
        code_competition,
        libelle,
        id_compet_maitre,
        IFNULL(DATE_FORMAT(start_date, '%d/%m/%Y'), '') AS start_date,
        IFNULL(DATE_FORMAT(start_register_date, '%d/%m/%Y'), '') AS limit_register_date,
        IFNULL(DATE_FORMAT(limit_register_date, '%d/%m/%Y'), '') AS limit_register_date,
        is_home_and_away+0 AS is_home_and_away
        FROM competitions
        WHERE code_competition = '$code_competition'
        ORDER BY libelle";
        $results = $this->sql_manager->execute($sql);
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function resetCompetition($ids)
    {
        if (empty($ids)) {
            throw new Exception("Aucune compétition sélectionnée !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $competitions = $this->getCompetitions("c.id = $id");
            if (count($competitions) !== 1) {
                throw new Exception("Une seule compétition doit être trouvée !");
            }
            if ($this->isCompetitionStarted($competitions[0]['id'])) {
                throw new Exception("La compétition a déjà commencé !!!");
            }
            $competition = $competitions[0];
            $code_competition = $competition['code_competition'];
            $this->rank->resetRankPoints($code_competition);
        }
    }

    /**
     * @throws Exception
     */
    public function getTournamentName($tournamentCode)
    {
        $sql = "SELECT 
        c.libelle AS tournament_name
        FROM competitions c 
        WHERE c.code_competition = '$tournamentCode'";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['tournament_name'];
    }

    /**
     * @throws Exception
     */
    public function getParentCompetition($compet)
    {
        $sql = "SELECT id_compet_maitre FROM competitions WHERE code_competition = '$compet'";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer la compétition $compet !");
        }
        $data = $results[0];
        return $data['id_compet_maitre'];
    }

    /**
     * @throws Exception
     */
    public function download($code_competition, $division)
    {
        $matches = $this->match->getMatches($code_competition, $division);
        if (count($matches) == 0) {
            throw new Exception("Il n'y a pas de match pour cette compétition/division !");
        }
        $delimiter = ";";
        $filename = "matchs_" . $code_competition . "_$division.csv";
        // Create a file pointer
        $f = fopen('php://memory', 'w');
        //add BOM to fix UTF-8 in Excel
        fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        // Set column headers
        function filter_hidden_csv_fields($var): bool
        {
            return !(Generic::starts_with($var, 'id') || (Generic::ends_with($var, '_raw')));
        }

        $fields = array_keys(array_filter($matches[0], 'filter_hidden_csv_fields', ARRAY_FILTER_USE_KEY));
        fputcsv($f, $fields, $delimiter);
        // Output each row of the data, format line as csv and write to file pointer
        foreach ($matches as $match_item) {
            fputcsv($f, array_values(array_filter($match_item, 'filter_hidden_csv_fields', ARRAY_FILTER_USE_KEY)), $delimiter);
        }
        // Move back to beginning of file
        fseek($f, 0);
        // Set headers to download file rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        //output all remaining data on a file pointer
        fpassthru($f);
        exit;
    }

    /**
     * automatic registration:
     * - isoardi
     * - championships when this is 2nd half
     * @throws Exception
     */
    public function is_automatic_registration($id_competition): bool
    {
        $competition = $this->get_by_id($id_competition);
        return ($competition['code_competition'] == 'c') ||
            ($this->is_championship($id_competition) && !$this->is_first_half($id_competition));
    }

    /**
     * @throws Exception
     */
    public function is_registration_available($id_competition): bool
    {
        $competition = $this->get_by_id($id_competition);
        $current_date = strtotime(date('Y-m-d'));
        $start_date = DateTime::createFromFormat('d/m/Y', $competition['start_register_date'])->getTimestamp();
        $end_date = DateTime::createFromFormat('d/m/Y', $competition['limit_register_date'])->getTimestamp();
        return ($current_date >= $start_date) && ($current_date <= $end_date);
    }

    /**
     * - take all registered teams ordered by division,current_rank.
     * - split by 2, to avoid having too much handicap
     * - randomize each array
     * - divide to set only 4 teams per pool(division), to get 3 days for pool phase
     * - insert into classements table
     * @param bool $is_dry_run
     * @return void
     * @throws Exception
     */
    public function init_classements_isoardi(bool $is_dry_run = true): void
    {
        $rank_manager = new Rank();
        $competition_isoardi = $this->getCompetition('c');
        // first, remove all ranks for competition
        $rank_manager->delete_competition($competition_isoardi['id']);
        // take all registered teams ordered by division,current_rank.
        $rank_teams = $this->rank->get_full_competition_rank('m');
        $rank_teams = $this->append_unranked_teams($rank_teams, 'm');
        // split by 2, to avoid having too much handicap
        $length_pool_1 = intdiv(count($rank_teams), 2);
        $group_1_teams = array_slice($rank_teams, 0, $length_pool_1);
        $group_2_teams = array_slice($rank_teams, $length_pool_1);
        $hats_1 = $this->make_hats($group_1_teams);
        $hats_2 = $this->make_hats($group_2_teams);
        // randomize each hat
        foreach ($hats_1 as $index => $hat) {
            shuffle($hats_1[$index]);
        }
        foreach ($hats_2 as $index => $hat) {
            shuffle($hats_2[$index]);
        }
        // make pools of 3
        $group_1_pools = Competition::make_pools_of_3($hats_1);
        $group_2_pools = Competition::make_pools_of_3($hats_2);
        // insert into classements table
        foreach ($group_1_pools as $division_index => $group_1_pool) {
            foreach ($group_1_pool as $team_index => $team) {
                $team_division = $division_index + 1;
                $team_rank = $team_index + 1;
                $team_name = $team['equipe'];
                $division_champ = $team['division'];
                if ($is_dry_run) {
                    error_log("$team_division\t$team_rank\t$team_name\t\t\tdiv $division_champ");
                } else {
                    $this->rank->insert('c', $team_division, $team['id_equipe'], $team_rank);
                }
            }
        }
        foreach ($group_2_pools as $division_index => $group_2_pool) {
            foreach ($group_2_pool as $team_index => $team) {
                $team_division = count($group_1_pools) + $division_index + 1;
                $team_rank = $team_index + 1;
                $team_name = $team['equipe'];
                $division_champ = $team['division'];
                if ($is_dry_run) {
                    error_log("$team_division\t$team_rank\t$team_name\t\t\tdiv $division_champ");
                } else {
                    $this->rank->insert('c', $team_division, $team['id_equipe'], $team_rank);
                }
            }
        }

    }

    /**
     * @throws Exception
     */
    public function is_group_draw_needed($id_competition): bool
    {
        $competition = $this->get_by_id($id_competition);
        return $competition['code_competition'] == 'kh';
    }

    /**
     * @throws Exception
     */
    private function append_unranked_teams(array|int|string|null $rank_teams, string $code_competition)
    {
        require_once __DIR__ . '/Register.php';
        require_once __DIR__ . '/Team.php';
        $register = new Register();
        $team_class = new Team();
        $competition = $this->getCompetition($code_competition);
        $pending_registrations = $register->get_pending_registrations($competition['id']);
        $max_division = $this->get_max_division($competition['id']);
        foreach ($pending_registrations as $pending_registration) {
            $team = $team_class->get_by_name(
                $pending_registration['code_competition'],
                $pending_registration['new_team_name'],
                $pending_registration['id_club']);
            $rank_teams[] = array(
                'rang' => 0,
                'id_equipe' => $team['id_equipe'],
                'equipe' => $team['nom_equipe'],
                'division' => $max_division,
            );
        }
        return $rank_teams;
    }

    /**
     * @throws Exception
     */
    private function get_max_division(mixed $id_competition)
    {
        $sql = "SELECT MAX(division) as div_max 
                FROM classements 
                WHERE code_competition IN (SELECT code_competition 
                                           FROM competitions 
                                           WHERE id = ?)";
        $bindings = array(
            array('type' => 'i', 'value' => $id_competition),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) == 0) {
            return 0;
        }
        return $results[0]['div_max'];
    }

    /**
     * @param $id_competition
     * @return bool
     * @throws Exception
     */
    public function is_championship($id_competition): bool
    {
        $competition = $this->get_by_id($id_competition);
        return in_array($competition['code_competition'], array('f', 'm', 'mo'));
    }

    /**
     * @throws Exception
     */
    public function is_first_half($id_competition): bool
    {
        $competition_by_id = $this->get_by_id($id_competition);
        // need to retrieve competition by code, as this sql formats dates
        $competition = $this->getCompetition($competition_by_id['code_competition']);
        // format d/m/Y or empty if null
        $start_date = $competition['start_date'];
        if (empty($start_date)) {
            throw new Exception("Impossible de déterminer si c'est la 1ere demi saison, la date de début est vide !");
        }
        $start_datetime = DateTime::createFromFormat('d/m/Y', $start_date);
        $month = intval($start_datetime->format('m'));
        return $month > 6;
    }

    /**
     * @throws Exception
     */
    public function generate_matches_final_phase_cup($ids, $nommage)
    {
        $day_mgr = new Day();
        if (empty($ids)) {
            throw new Exception("Il faut sélectionner une ou plusieurs compétitions pour démarrer la génération !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $competition = $this->get_by_id($id);
            $code_competition = $competition['code_competition'];
            if (!in_array($competition['code_competition'], array('kf', 'cf'))) {
                throw new Exception("Cette compétition n'est pas une phase finale de coupe !");
            }
            $days = $day_mgr->get("j.code_competition = '$code_competition' AND j.nommage = '$nommage'");
            if (empty($days)) {
                throw new Exception("Il faut créer la journée avant de générer cette compétition !");
            }
            $days_ids = implode(',', array_values(array_column($days, 'id')));
            $this->match->delete_matches("code_competition = '$code_competition' AND division = '1' AND id_journee IN ($days_ids)");
            $this->match->draw_matches($code_competition, '1', $days[0]['id']);
        }
    }


    public function make_hats(array|int|string|null $group_teams): array
    {
        $hats = array();
        foreach ($group_teams as $team) {
            $division = $team['division'];
            $hats[$division][] = $team;
        }
        return array_values($hats);
    }

    /**
     * @throws Exception
     */
    public static function make_pools_of_3(array $hats): array
    {
        $n = 3;
        $pools = array();
        while (count($hats, COUNT_RECURSIVE) > 0) {
            $pools[] = Competition::pick_n_from_hats($hats, $n);
        }
        $last = $pools[count($pools) - 1];
        if (count($last) === $n) {
            return $pools;
        }
        if (count($last) === 1) {
            if (count($pools) <= 1) {
                return $pools;
            }
            $pools[count($pools) - 2][] = array_pop($last);
            unset($pools[count($pools) - 1]);
            return array_values($pools);
        }
        if (count($last) === 2) {
            if (count($pools) <= 2) {
                return $pools;
            }
            $pools[count($pools) - 3][] = array_pop($last);
            $pools[count($pools) - 2][] = array_pop($last);
            unset($pools[count($pools) - 1]);
            return array_values($pools);
        }
        throw new Exception("Impossible de déterminer les poules...");
    }

    private static function pick_n_from_hats(array &$hats, int $n): array
    {
        $result = array();
        $nb_hats = count($hats);
        $index_hat = 0;
        while (count($result) < $n) {
            if (!empty($hats[$index_hat])) {
                if ($index_hat < $nb_hats) {
                    $result[] = array_pop($hats[$index_hat]);
                    if (empty($hats[$index_hat])) {
                        unset($hats[$index_hat]);
                        $hats = array_values($hats);
                    }
                }
            }
            $index_hat++;
            if ($index_hat == $nb_hats) {
                $index_hat = 0;
            }
            if (count($hats, COUNT_RECURSIVE) === 0) {
                break;
            }
        }
        return $result;
    }

}