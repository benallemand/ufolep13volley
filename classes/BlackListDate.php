<?php
require_once __DIR__ . "/Generic.php";

class BlackListDate extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_date';
    }

    /**
     * @throws Exception
     */
    public function saveBlacklistDate($closed_date, $id = null, $dirtyFields = null): int|array|string|null
    {
        $inputs = array(
            'closed_date' => $closed_date,
            'id' => $id,
            'dirtyFields' => $dirtyFields,
        );
        return $this->save($inputs);
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
        $sql .= " blacklist_date SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'closed_date':
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
    public function getBlacklistDate(): array
    {
        $sql = "SELECT  bd.id,
                        DATE_FORMAT(bd.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_date bd";
        return $this->sql_manager->execute($sql);
    }


}