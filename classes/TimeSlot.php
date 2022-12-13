<?php

require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';

class TimeSlot extends Generic
{
    private Team $team;

    /**
     * constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'creneau';
        $this->team = new Team();
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                    c.id, 
                    c.id_gymnase, 
                    c.id_equipe, 
                    c.jour, 
                    c.heure, 
                    CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse) AS gymnasium_full_name, 
                    CONCAT(e.nom_equipe, ' (', cl.nom, ') (', comp.libelle, ')') AS team_full_name,
                    c.has_time_constraint+0 AS has_time_constraint,
                    c.usage_priority
                FROM creneau c
                JOIN gymnase g ON g.id = c.id_gymnase
                JOIN equipes e ON e.id_equipe = c.id_equipe
                JOIN clubs cl ON cl.id = e.id_club
                JOIN competitions comp ON comp.code_competition = e.code_competition
                WHERE $query
                ORDER BY team_full_name, gymnasium_full_name";
    }

    /**
     * @throws Exception
     */
    public function get_my_timeslots()
    {
        @session_start();
        $id_team = $_SESSION['id_equipe'];
        return $this->getTimeSlots("c.id_equipe = $id_team");
    }

    /**
     * @param string|null $query
     * @return array
     * @throws Exception
     */
    public function getTimeSlots(?string $query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
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

    /**
     * @throws Exception
     */
    public function create($id_court_1, $day_court_1, $hour_court_1, $id_team, $has_time_constraint, $usage_priority)
    {
        $sql = "INSERT INTO creneau SET 
                    id_gymnase = ?, 
                    jour = ?, 
                    heure = ?, 
                    id_equipe = ?,
                    has_time_constraint = ?,
                    usage_priority = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_court_1),
            array('type' => 's', 'value' => $day_court_1),
            array('type' => 's', 'value' => $hour_court_1),
            array('type' => 'i', 'value' => $id_team),
            array('type' => 'i', 'value' => $has_time_constraint),
            array('type' => 'i', 'value' => $usage_priority),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function getWeekSchedule()
    {
        $sql = "SELECT 
        CONCAT(g.ville, ' - ', g.nom) AS gymnasium,
        c.jour AS dayOfWeek,
        c.heure AS startTime,
        CONCAT(e.nom_equipe, ' - ', comp.libelle) AS team
        FROM creneau c
        JOIN gymnase g ON g.id = c.id_gymnase
        JOIN equipes e ON e.id_equipe = c.id_equipe
        JOIN clubs cl ON cl.id = e.id_club
        JOIN competitions comp ON comp.code_competition = e.code_competition
        ORDER BY dayOfWeek, startTime, gymnasium";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveTimeSlot(
        $id_gymnase,
        $jour,
        $heure,
        $usage_priority,
        $has_time_constraint = null,
        $id = null,
        $id_equipe = null,
        $dirtyFields = null,
    )
    {
        if (empty($id_equipe)) {
            @session_start();
            $id_equipe = $_SESSION['id_equipe'];
        }
        $bindings = array();
        $inputs = array(
            'id' => $id,
            'id_equipe' => $id_equipe,
            'id_gymnase' => $id_gymnase,
            'jour' => $jour,
            'heure' => $heure,
            'has_time_constraint' => $has_time_constraint,
            'usage_priority' => $usage_priority,
            'dirtyFields' => $dirtyFields,
        );
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " creneau SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_equipe':
                case 'id_gymnase':
                case 'usage_priority':
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
                case 'has_time_constraint':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
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
        $teamName = $this->team->getTeamName($inputs['id_equipe']);
        if (empty($inputs['id'])) {
            $comment = "Creation d'un nouveau creneau pour l'équipe $teamName";
        } else {
            $comment = "Modification d'un creneau existant pour l'équipe $teamName";
        }
        $this->addActivity($comment);
        return true;
    }

    /**
     * @throws Exception
     */
    public function removeTimeSlot($id)
    {
        if (UserManager::isAdmin()) {
            throw new Exception("Un admin ne peut pas effacer un créneau !");
        }
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable peut effacer un créneau !");
        }
        $sql = "DELETE FROM creneau WHERE id = $id";
        $this->sql_manager->execute($sql);
        $this->addActivity("Un créneau a été supprimé");
    }

}