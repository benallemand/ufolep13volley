<?php
require_once __DIR__ . '/../classes/Database.php';

class SqlManager
{
    /**
     * @param $sql
     * @param array $bindings
     * @return array|int|string|null
     * @throws Exception
     */
    public function execute($sql, array $bindings = array()): array|int|string|null
    {
        $db = Database::openDbConnection();
        $sql = trim($sql);
        mysqli_query($db, "SET SESSION group_concat_max_len = 1000000");
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        if (count($bindings) > 0) {
            $array_params = array($stmt, '');
            foreach ($bindings as $binding) {
                $array_params[1] .= $binding['type'];
            }
            foreach ($bindings as $binding) {
                $array_params[] = $binding['value'];
            }
            if (call_user_func_array('mysqli_stmt_bind_param', $this->make_values_referenced($array_params)) === FALSE) {
                throw new Exception("Erreur SQL : " . mysqli_error($db));
            }
        }
        if (mysqli_stmt_execute($stmt) === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        if (str_starts_with($sql, "SELECT") || str_starts_with($sql, "SHOW")) {
            $mysqli_result = mysqli_stmt_get_result($stmt);
            $results = array();
            while ($data = mysqli_fetch_assoc($mysqli_result)) {
                $results[] = $data;
            }
            if (mysqli_stmt_close($stmt) === FALSE) {
                throw new Exception("Erreur SQL : " . mysqli_error($db));
            }
            return $results;
        }
        if (str_starts_with($sql, "INSERT INTO")) {
            return mysqli_insert_id($db);
        }
        if (mysqli_stmt_close($stmt) === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        return null;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_last_day_activity(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/last_day_activity.sql');
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_matches_not_reported(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/matches_not_reported.sql');
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_ids_team_requesting_next_matches(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/ids_team_requesting_next_matches.sql');
        return $this->execute($sql);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_next_matches_for_team($team_id): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/next_matches_for_team.sql');
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $team_id
        );
        $bindings[] = array(
            'type' => 'i',
            'value' => $team_id
        );
        return $this->execute($sql, $bindings);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_email_from_team_id($team_id): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/email_from_team_id.sql');
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => $team_id
        );
        return $this->execute($sql, $bindings);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_players_without_licence_number(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/players_without_licence_number.sql');
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_team_leaders_without_email(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/team_leaders_without_email.sql');
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_pending_reports(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/pending_reports.sql');
        return $this->execute($sql);
    }

    private function make_values_referenced($arr): array
    {
        $refs = array();
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_team_recaps(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/team_recaps.sql');
        return $this->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function get_teams_with_missing_licences(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/teams_with_missing_licences.sql');
        return $this->execute($sql);
    }
}
