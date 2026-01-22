<?php
require_once __DIR__ . '/Generic.php';

class Court extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'gymnase';
    }

    public function getGymnasiums()
    {
        $sql = "SELECT 
        id, 
        nom, 
        adresse, 
        code_postal, 
        ville, 
        gps, 
        CONCAT(ville, ' - ', nom, ' - ', adresse) AS full_name,
        nb_terrain,
        remarques
        FROM gymnase
        ORDER BY ville, nom, adresse";
        return $this->sql_manager->execute($sql);
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
                    break;
                case 'code_postal':
                case 'nb_terrain':
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
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
        $subject = "Gymnase " . $inputs['nom'];
        $this->addActivity($this->build_activity($subject, $inputs['dirtyFields'], $inputs));
    }

    /**
     * @throws Exception
     */
    public function saveGymnasium(
        $nom,
        $adresse,
        $code_postal,
        $ville,
        $gps,
        $nb_terrain,
        $dirtyFields = null,
        $id = null,
        $remarques = null
    )
    {
        $inputs = array(
            'nom' => $nom,
            'adresse' => $adresse,
            'code_postal' => $code_postal,
            'ville' => $ville,
            'gps' => $gps,
            'nb_terrain' => $nb_terrain,
            'remarques' => $remarques,
            'dirtyFields' => $dirtyFields,
            'id' => $id,
        );
        $this->save($inputs);
    }

}