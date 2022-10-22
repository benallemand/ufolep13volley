<?php
require_once __DIR__ . "/Generic.php";

class BlackListTeams extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_teams';
    }

    /**
     * @param $inputs
     * @return array|int|string|null
     * @throws Exception
     */
    public function save($inputs): array|int|string|null
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_teams SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_team_1':
                case 'id_team_2':
                    $bindings[] = array('type' => 'i', 'value' => $value);
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
     * @return array
     * @throws Exception
     */
    public function getBlacklistTeams(): array
    {
        $sql = "SELECT  bt.id, 
                        bt.id_team_1, 
                        bt.id_team_2, 
                        CONCAT(e_1.nom_equipe, ' (', e_1.code_competition, ')') AS libelle_equipe_1,
                        CONCAT(e_2.nom_equipe, ' (', e_2.code_competition, ')') AS libelle_equipe_2
                FROM blacklist_teams bt
                JOIN equipes e_1 ON e_1.id_equipe = bt.id_team_1
                JOIN equipes e_2 ON e_2.id_equipe = bt.id_team_2";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveBlacklistTeams($id_team_1, $id_team_2, $dirtyFields=null, $id=null) {
        $inputs = array(
            'id_team_1' => $id_team_1,
            'id_team_2' => $id_team_2,
            'dirtyFields' => $dirtyFields,
            'id' => $id,
        );
        return $this->save($inputs);
    }
}