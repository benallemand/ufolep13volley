<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class Club extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'clubs';
    }

    /**
     * @param $inputs
     * @throws Exception
     */
    public function save($inputs)
    {
        parent::save($inputs);
        $subject = "Club " . $inputs['nom'];
        $this->addActivity($this->build_activity($subject, $inputs['dirtyFields'], $inputs));
    }

    public function getClubName($idClub)
    {
        $sql = "SELECT 
        c.nom AS club_name 
        FROM clubs c 
        WHERE c.id = $idClub";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['club_name'];
    }


}