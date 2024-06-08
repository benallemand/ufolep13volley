<?php
require_once __DIR__ . "/Generic.php";

class Survey extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'survey';
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " survey SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'user_id':
                case 'id_match':
                case 'on_time':
                case 'spirit':
                case 'referee':
                case 'catering':
                case 'global':
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
        if(empty($inputs['id'])) {
        } else {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
                s.id,    
                m.id_match,
                e_sondeuse.nom_equipe AS surveyor,
                e_sondee.nom_equipe AS surveyed,
                e_sondee_club.nom AS surveyed_club,
                m.code_match, 
                m.equipe_dom, 
                m.equipe_ext,
                s.on_time,
                s.spirit,
                s.referee,
                s.catering,
                s.global,
                s.comment,
                u.login
                FROM matchs_view m
                JOIN survey s ON s.id_match = m.id_match
                JOIN comptes_acces u ON u.id = s.user_id
                JOIN equipes e_sondeuse ON e_sondeuse.id_equipe = u.id_equipe
                JOIN equipes e_sondee ON e_sondee.id_equipe = IF(u.id_equipe = m.id_equipe_dom, m.id_equipe_ext, m.id_equipe_dom)
                JOIN clubs e_sondee_club ON e_sondee_club.id = e_sondee.id_club
                WHERE $query
                ORDER BY id DESC";
    }
}