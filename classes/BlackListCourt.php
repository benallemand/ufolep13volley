<?php
require_once __DIR__ . "/Generic.php";

class BlackListCourt extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_gymnase';
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_gymnase SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'closed_date':
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'id_gymnase':
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
    public function getBlacklistGymnase(): array
    {
        $sql = "SELECT  bg.id, 
                        bg.id_gymnase, 
                        CONCAT(g.nom, ' (', g.ville, ')') AS libelle_gymnase,
                        DATE_FORMAT(bg.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_gymnase bg
                JOIN gymnase g on bg.id_gymnase = g.id";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveBlacklistGymnase($id_gymnase, $closed_date, $dirtyFields=null, $id=null) {
        $inputs = array(
            'id_gymnase' => $id_gymnase,
            'closed_date' => $closed_date,
            'dirtyFields' => $dirtyFields,
            'id' => $id,
        );
        return $this->save($inputs);
    }

}