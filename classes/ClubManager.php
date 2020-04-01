<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class ClubManager extends Generic
{
    private $sql_manager;
    private $table_name;

    public function __construct()
    {
        $this->sql_manager = new SqlManager();
        $this->table_name = 'clubs';
    }

    private function getSql($query = "1=1")
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query";
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function get($query = "1=1")
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->getResults($sql);
    }

    /**
     * @param $inputs
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
        $sql .= " $this->table_name SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    continue;
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
        $subject = "Club " . $inputs['nom'];
        $this->addActivity($this->build_activity($subject, $inputs['dirtyFields'], $inputs));
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function delete($ids)
    {
        $sql = "DELETE FROM $this->table_name WHERE id IN($ids)";
        $this->sql_manager->execute($sql);
    }
}