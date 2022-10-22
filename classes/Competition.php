<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Rank.php';

class Competition extends Generic
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
        c.id,
        c.code_competition,
        c.libelle,
        c.id_compet_maitre,
        DATE_FORMAT(c.start_date, '%d/%m/%Y') AS start_date,
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
     * @param $inputs
     * @throws Exception
     */
    public function save_friendships($inputs)
    {
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
     * @param $inputs
     * @throws Exception
     */
    public function save_blacklist_by_city($inputs)
    {
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
        switch ($code_competition) {
            case 'm':
            case 'f':
            case 'mo':
                $label = 'Division';
                break;
            case 'c':
            case 'kh':
                $label = 'Poule';
                break;
            default:
                $label = '?';
                break;
        }
        $rank_manager = new Rank();
        $result_string = "";
        $competitions = $this->getCompetitions("c.code_competition = '$code_competition'");
        foreach ($competitions as $competition) {
            if (in_array($code_competition, array('cf', 'kf'))) {
                $result_string .= "<li><a href='#matches/$code_competition'>" . $competition['libelle'] . "</a></li>";
                continue;
            }
            $result_string .= "<li class='dropdown-header'><h4>" . $competition['libelle'] . "</h4></li>";
            $divisions = $rank_manager->getDivisionsFromCompetition($code_competition);
            foreach ($divisions as $division) {
                $division_string = $division['division'];
                $result_string .= "<li><a href='#championship/$code_competition/$division_string'>$label $division_string</a></li>";
            }
        }
        echo $result_string;
    }

    public function getTournaments()
    {
        $sql = "SELECT c.id, c.code_competition, c.libelle 
        FROM competitions c 
        WHERE c.code_competition IN (SELECT DISTINCT code_competition FROM classements) 
        ORDER BY c.libelle";
        return $this->sql_manager->execute($sql);
    }

    public function saveCompetition(
        $code_competition,
        $libelle,
        $id_compet_maitre,
        $start_date,
        $is_home_and_away,
        $id = null,
        $dirtyFields = null
    )
    {
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'code_competition' => $code_competition,
            'libelle' => $libelle,
            'id_compet_maitre' => $id_compet_maitre,
            'start_date' => $start_date,
            'is_home_and_away' => $is_home_and_away,
        );
        return $this->save($inputs);
    }

    public function save($inputs)
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
        if (empty($inputs['id'])) {
        } else {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getCompetition($code_competition)
    {
        $sql = "SELECT 
        id,
        code_competition,
        libelle,
        id_compet_maitre,
        IFNULL(DATE_FORMAT(start_date, '%d/%m/%Y'), '') AS start_date,
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
            require_once __DIR__ . '/../classes/Rank.php';
            $rank_manager = new Rank();
            $competition = $competitions[0];
            $code_competition = $competition['code_competition'];
            $rank_manager->resetRankPoints($code_competition);
        }
    }

    public function getTournamentName($tournamentCode)
    {
        $sql = "SELECT 
        c.libelle AS tournament_name
        FROM competitions c 
        WHERE c.code_competition = '$tournamentCode'";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['tournament_name'];
    }

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


}