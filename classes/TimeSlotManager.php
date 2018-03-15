<?php

require_once __DIR__ . '/Generic.php';

class TimeSlotManager extends Generic
{
    private function getSql($query = null)
    {
        $sql = "SELECT 
        c.id, 
        c.id_gymnase, 
        c.id_equipe, 
        c.jour, 
        c.heure, 
        CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse) AS gymnasium_full_name, 
        CONCAT(e.nom_equipe, ' (', cl.nom, ') (', comp.libelle, ')') AS team_full_name,
        c.has_time_constraint+0 AS has_time_constraint
        FROM creneau c
        JOIN gymnase g ON g.id = c.id_gymnase
        JOIN equipes e ON e.id_equipe = c.id_equipe
        JOIN clubs cl ON cl.id = e.id_club
        JOIN competitions comp ON comp.code_competition = e.code_competition
        WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        $sql .= " ORDER BY team_full_name, gymnasium_full_name";
        return $sql;
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getTimeSlots($query = null)
    {
        $db = Database::openDbConnection();
        $sql = $this->getSql($query);
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function getTimeSlot($id)
    {
        $results = $this->getTimeSlots("c.id = $id");
        if (count($results) !== 1) {
            throw new Exception("Error while retrieving timeslot data");
        }
        return $results[0];
    }
}