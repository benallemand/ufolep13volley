<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/LimitDate.php';

class Day extends Generic
{
    private LimitDate $limit_date;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'journees';
        $this->limit_date = new LimitDate();
    }


    /**
     * @param string|null $query
     * @return string
     */
    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                    j.id,
                    j.code_competition,
                    c.libelle                                                           AS libelle_competition,
                    j.numero,
                    j.nommage,
                    CONCAT( 'Semaine du ', 
                            DATE_FORMAT(j.start_date, '%W %d %M'), 
                            ' au ',
                            DATE_FORMAT(ADDDATE(j.start_date, INTERVAL 4 DAY), '%W %d %M %Y')) 
                                                                                        AS libelle,
                DATE_FORMAT(j.start_date, '%d/%m/%Y')                                   AS start_date
                FROM journees j
                JOIN competitions c ON c.code_competition = j.code_competition
                WHERE $query
                ORDER BY j.start_date";
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getDays($query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $code_competition
     * @param $numero
     * @param $competition_start_date
     * @param bool $is_extra_day
     * @param string $limit_date
     * @return int|string
     * @throws Exception
     */
    public function insertDay(
        $code_competition,
        $numero,
        $competition_start_date,
        bool $is_extra_day,
        string $limit_date): int|string
    {
        $numero_padded = str_pad($numero, 2, '0', STR_PAD_LEFT);
        $week_offset = $numero - 1;
        $nommage = "Journee $numero_padded";
        if ($is_extra_day) {
            $nommage = "Journee bonus";
        }
        error_log($nommage);
        while (!$this->is_week_allowed($code_competition, $competition_start_date, $week_offset, $limit_date)) {
            $week_offset++;
        }
        $sql = "INSERT INTO journees SET 
          code_competition = '$code_competition', 
          numero = $numero, 
          nommage = '$nommage',
          start_date = ADDDATE(STR_TO_DATE('$competition_start_date', '%d/%m/%Y'), INTERVAL $week_offset WEEK)";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param string $query
     * @throws Exception
     */
    public function deleteDays(string $query = "1=1")
    {
        $sql = "DELETE FROM journees WHERE $query";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function save_day(
        $id,
        $code_competition,
        $numero,
        $nommage,
        $start_date,
        $dirtyFields = null
    )
    {
        $inputs = array(
            'id' => $id,
            'code_competition' => $code_competition,
            'numero' => $numero,
            'nommage' => $nommage,
            'start_date' => $start_date,
            'dirtyFields' => $dirtyFields,
        );
        return $this->save($inputs);
    }

    /**
     * @param $inputs
     * @return array|int|string|null
     * @throws Exception
     */
    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " journees SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'numero':
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    $sql .= "numero = ?,";
                    break;
                case 'start_date':
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
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
    public function generateDays($ids)
    {
        if (empty($ids)) {
            throw new Exception("Aucune compétition sélectionnée !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            require_once __DIR__ . '/../classes/Competition.php';
            $competition_manager = new Competition();
            $competitions = $competition_manager->getCompetitions("c.id = $id");
            if (count($competitions) !== 1) {
                throw new Exception("Une seule compétition doit être trouvée !");
            }
            if ($competition_manager->isCompetitionStarted($competitions[0]['id'])) {
                throw new Exception("La compétition a déjà commencé !!!");
            }
            require_once __DIR__ . '/../classes/Rank.php';
            $rank_manager = new Rank();
            $competition = $competitions[0];
            $code_competition = $competition['code_competition'];
            if (empty($competition['start_date'])) {
                throw new Exception("Date de début de compétition non renseignée");
            }
            require_once __DIR__ . '/../classes/MatchMgr.php';
            $match_manager = new MatchMgr();
            $match_manager->delete_matches("code_competition = '$code_competition' AND match_status = 'NOT_CONFIRMED'");
            $match_manager->unset_day_matches("code_competition = '$code_competition'");
            $this->deleteDays("code_competition = '$code_competition'");
            $divisions = $rank_manager->getDivisionsFromCompetition($code_competition);
            $rounds_counts = array();
            foreach ($divisions as $division) {
                $teams = $rank_manager->getTeamsFromDivisionAndCompetition($division['division'], $code_competition);
                $teams_count = count($teams);
                if ($teams_count % 2 == 1) {
                    $teams_count++;
                }
                if ($competition['is_home_and_away'] === 1) {
                    $rounds_counts[] = ($teams_count - 1) * 2;
                } else {
                    $rounds_counts[] = $teams_count - 1;
                }
            }
            for ($round_number = 1; $round_number <= max($rounds_counts); $round_number++) {
                $this->insertDay(
                    $code_competition,
                    strval($round_number),
                    $competition['start_date'],
                    false,
                    $this->limit_date->getLimitDate($code_competition)
                );
            }
            $days = $this->getDays("j.code_competition = '$code_competition'");
            $last_day = array_pop($days);
            $limit_date = DateTime::createFromFormat('d/m/Y', $this->limit_date->getLimitDate($code_competition));
            $last_start_date = DateTime::createFromFormat('d/m/Y', $last_day['start_date']);
            $bonus_day_start_date = $last_start_date;
            $bonus_day_start_date->modify('+1 week');
            $bonus_day_number = intval($last_day['numero']);
            while ($bonus_day_start_date < $limit_date) {
                $id_day = $this->insertDay(
                    $code_competition,
                    strval(++$bonus_day_number),
                    $competition['start_date'],
                    true,
                    $this->limit_date->getLimitDate($code_competition)
                );
                $new_day = $this->get_by_id($id_day);
                $bonus_day_start_date = DateTime::createFromFormat('d/m/Y', $new_day['start_date']);
                $bonus_day_start_date->modify('+1 week');
            }
        }
    }

    public function get_by_id($id): array
    {
        $sql = $this->getSql("j.id = ?");
        $bindings = array(
            array('type' => 'i', 'value' => $id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            throw new Exception("Pas de donnée dispo pour l'id $id !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    private function is_week_allowed($code_competition,
        $competition_start_date,
                                     int $week_offset,
                                     string $limit_date): bool
    {
        // list forbidden dates
        $forbidden_date = array(
            "'19/12/2022'",
            "'26/12/2022'",
            "'13/02/2023'",
            "'20/02/2023'",
            "'17/04/2023'",
            "'24/04/2023'",
            "'15/05/2023'",
        );
        $forbidden_dates = implode(',', $forbidden_date);
        // check if we don't generate an already existing day for the competition
        $sql = "SELECT * FROM journees WHERE 
          code_competition = ?
          AND start_date = ADDDATE(STR_TO_DATE(?, '%d/%m/%Y'), INTERVAL ? WEEK)";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $code_competition);
        $bindings[] = array('type' => 's', 'value' => $competition_start_date);
        $bindings[] = array('type' => 'i', 'value' => $week_offset);
        if (count($this->sql_manager->execute($sql, $bindings)) > 0) {
            return false;
        }
        // check if we don't generate a day after the limit date
        $sql = "SELECT 1
                FROM dual
                WHERE ADDDATE(
                        STR_TO_DATE(?, '%d/%m/%Y'), 
                        INTERVAL ? WEEK) < STR_TO_DATE(?, '%d/%m/%Y')";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $competition_start_date);
        $bindings[] = array('type' => 'i', 'value' => $week_offset);
        $bindings[] = array('type' => 's', 'value' => $limit_date);
        if (count($this->sql_manager->execute($sql, $bindings)) == 0) {
            error_log($competition_start_date);
            error_log($week_offset);
            error_log($limit_date);
            throw new Exception("Impossible de générer une journée de +, date limite dépassée !");
        }
        // check if day will not occur in a forbidden date
        $sql = "SELECT 1
                FROM dual
                WHERE DATE_FORMAT(ADDDATE(STR_TO_DATE(?, '%d/%m/%Y'),INTERVAL ? WEEK), '%d/%m/%Y')
                    IN ($forbidden_dates)";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $competition_start_date);
        $bindings[] = array('type' => 'i', 'value' => $week_offset);
        return count($this->sql_manager->execute($sql, $bindings)) == 0;
    }
}