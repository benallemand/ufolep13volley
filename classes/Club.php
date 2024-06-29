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

    public function getSql($query = "1=1"): string
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query
                ORDER BY nom";
    }

    /**
     * @throws Exception
     */
    public function deleteClubs($ids) {
        return $this->delete($ids);
    }
    /**
     * @throws Exception
     */
    public function saveClub($id,
                             $nom,
                             $affiliation_number,
                             $nom_responsable,
                             $prenom_responsable,
                             $tel1_responsable,
                             $tel2_responsable,
                             $email_responsable,
                             $dirtyFields = null
    ): array|int|string|null
    {
        return $this->save(array(
            'id' => $id,
            'nom' => $nom,
            'affiliation_number' => $affiliation_number,
            'nom_responsable' => $nom_responsable,
            'prenom_responsable' => $prenom_responsable,
            'tel1_responsable' => $tel1_responsable,
            'tel2_responsable' => $tel2_responsable,
            'email_responsable' => $email_responsable,
            'dirtyFields' => $dirtyFields,
        ));
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