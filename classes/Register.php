<?php
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Emails.php';

class Register
{
    /**
     * @var SqlManager
     */
    private $sql_manager;

    /**
     * MatchManager constructor.
     */
    public function __construct()
    {
        $this->sql_manager = new SqlManager();
    }

    /**
     * @throws Exception
     */
    public function register($parameters)
    {
        $bindings = array();
        if (empty($parameters['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " register SET ";
        foreach ($parameters as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'id_club':
                case 'old_team_id':
                case 'id_court_1':
                case 'id_court_2':
                case 'id_competition':
                    if (empty($value) || $value == 'null') {
                        $sql .= "$key = NULL,";
                    } else {
                        $sql .= "$key = ?,";
                        $bindings[] = array('type' => 'i', 'value' => $value);
                    }
                    break;
                default:
                    if (empty($value) || $value == 'null') {
                        $sql .= "$key = NULL,";
                    } else {
                        $sql .= "$key = ?,";
                        $bindings[] = array('type' => 's', 'value' => $value);
                    }
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($parameters['id'])) {
            $sql .= " WHERE id = ?";
            $bindings[] = array('type' => 'i', 'value' => $parameters['id']);
        }
        $id = $this->sql_manager->execute($sql, $bindings);
        if (!empty($id)) {
            $email_manager = new Emails();
            $email_manager->insert_email_notify_registration($id);
        }

    }

    /**
     * @throws Exception
     */
    public function get_register($id = null)
    {
        $where = "1=1";
        $bindings = array();
        if (!empty($id)) {
            $where .= " AND r.id = ?";
            $bindings[] = array('type' => 'i', 'value' => $id);
        }
        $sql = "SELECT 
                r.id,
                r.new_team_name,
                r.id_club,
                c.nom AS club,
                r.id_competition,
                c2.libelle AS competition,
                r.old_team_id,
                e.nom_equipe AS old_team,
                r.leader_name,
                r.leader_first_name,
                r.leader_email,
                r.leader_phone,
                r.id_court_1,
                g.nom AS court_1,
                r.day_court_1,
                r.hour_court_1,
                r.id_court_2,
                g2.nom AS court_2,
                r.day_court_2,
                r.hour_court_2,
                r.remarks,
                DATE_FORMAT(r.creation_date, '%d/%m/%Y %H:%i:%s') AS creation_date
                FROM register r
                JOIN clubs c on c.id = r.id_club
                JOIN competitions c2 on r.id_competition = c2.id
                LEFT JOIN equipes e on r.old_team_id = e.id_equipe
                LEFT JOIN gymnase g on g.id = r.id_court_1
                LEFT JOIN gymnase g2 on g2.id = r.id_court_2
                WHERE $where
                ORDER BY new_team_name";
        $results = $this->sql_manager->execute($sql, $bindings);
        if (!empty($id)) {
            return $results[0];
        }
        return $results;
    }

}