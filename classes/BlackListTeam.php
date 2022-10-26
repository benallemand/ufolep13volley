<?php
require_once __DIR__ . "/Generic.php";

class BlackListTeam extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_team';
    }

    public function saveBlacklistTeam($id_team,
                                      $closed_date,
                                      $dirtyFields = null,
                                      $id = null)
    {
        $inputs = array();
        $inputs['id_team'] = $id_team;
        $inputs['closed_date'] = $closed_date;
        $inputs['dirtyFields'] = $dirtyFields;
        $inputs['id'] = $id;
        $this->save($inputs);
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_team SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'closed_date':
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'id_team':
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
    public function getBlacklistTeam(): array
    {
        $sql = "SELECT  bt.id, 
                        bt.id_team, 
                        CONCAT(e.nom_equipe, ' (', e.code_competition, ')') AS libelle_equipe,
                        DATE_FORMAT(bt.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_team bt
                JOIN equipes e ON e.id_equipe = bt.id_team";
        return $this->sql_manager->execute($sql);
    }

}