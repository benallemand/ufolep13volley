<?php
require_once __DIR__ . '/Generic.php';

class Commission extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'commission';
        $this->id_name = 'id_commission';
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
        c.id_commission,
        c.nom,
        c.prenom,
        c.fonction,
        c.telephone1,
        c.telephone2,
        c.email,
        c.photo,
        c.type,
        GROUP_CONCAT(cd.division ORDER BY cd.division ASC) AS attribution
        FROM commission c 
        LEFT JOIN commission_division cd ON c.id_commission = cd.id_commission
        WHERE $query
        GROUP BY c.id_commission";
    }

    /**
     * @param $ids
     * @param null $divisions
     * @return void
     * @throws Exception
     */
    public function attribution($ids, $divisions = null): void
    {
        if (empty($ids)) {
            throw new Exception("Il faut sélectionner un ou plusieurs membres de la commission !");
        }
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            $this->delete_commission_division($id);
            if (!empty($divisions)) {
                $exploded_divisions = explode(',', $divisions);
                foreach ($exploded_divisions as $division) {
                    $this->insert_commission_division($id, $division);
                }
            }
        }
    }


    /**
     * @param string $id
     * @param string|null $division
     * @return void
     * @throws Exception
     */
    private function delete_commission_division(string $id, string $division = null): void
    {
        if (empty($division)) {
            $sql = "DELETE FROM commission_division WHERE id_commission = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id),
            );
        } else {
            $sql = "DELETE FROM commission_division WHERE id_commission = ? AND division = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id),
                array('type' => 's', 'value' => $division),
            );
        }
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param string $id
     * @param string $division
     * @return void
     * @throws Exception
     */
    private function insert_commission_division(string $id, string $division): void
    {
        $sql = "INSERT INTO commission_division SET id_commission = ?, division = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id),
            array('type' => 's', 'value' => $division),
        );
        $this->sql_manager->execute($sql, $bindings);
    }


}