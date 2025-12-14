<?php
require_once __DIR__ . '/Generic.php';

class Registry extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'registry';
    }

    public function getIdsTeamRequestingNextMatches()
    {
        $sql = "SELECT REPLACE(REPLACE(registry_key, '.is_remind_matches',''), 'users.','') AS user_id FROM registry WHERE registry_key LIKE 'users.%.is_remind_matches' AND registry_value = 'on'";
        return $this->sql_manager->execute($sql);
    }

    public function find_by_key(string $like_key)
    {
        $sql = "SELECT * FROM registry WHERE registry_key LIKE CONCAT('%', ?, '%')";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $like_key
        );
        return $this->sql_manager->execute($sql, $bindings);
    }


}