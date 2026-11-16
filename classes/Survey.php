<?php
require_once __DIR__ . "/Generic.php";

class Survey extends Generic
{
    /**
     * Échelle du sondage (issue #350). Version 1 : 0..10 étoiles, 0 = non
     * noté — les sondages des saisons passées. Version 2 : -2..+2, soit
     * -- - = + ++, 0 (=) par défaut. Le classement fair-play ne compte que
     * la version courante, pour ne jamais mélanger les deux.
     */
    const SCALE_VERSION = 2;
    const MIN_RATING = -2;
    const MAX_RATING = 2;

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
                    break;
                case 'user_id':
                case 'id_match':
                case 'id_equipe':
                case 'on_time':
                case 'spirit':
                case 'referee':
                case 'catering':
                case 'global':
                case 'scale_version':
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
        if(!empty($inputs['id'])) {
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
                s.scale_version,
                s.comment,
                s.id_equipe,
                u.login
                FROM matchs_view m
                JOIN survey s ON s.id_match = m.id_match
                JOIN comptes_acces u ON u.id = s.user_id
                -- l'équipe qui a rempli le sondage (issue #482), et non plus
                -- celle de son auteur : un responsable de club n'est rattaché
                -- à aucune équipe
                JOIN equipes e_sondeuse ON e_sondeuse.id_equipe = s.id_equipe AND e_sondeuse.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
                JOIN equipes e_sondee ON e_sondee.id_equipe = IF(s.id_equipe = m.id_equipe_dom, m.id_equipe_ext, m.id_equipe_dom)
                JOIN clubs e_sondee_club ON e_sondee_club.id = e_sondee.id_club
                -- un sondage par équipe et par match : le dernier envoyé
                JOIN (
                    SELECT id_match, id_equipe, MAX(id) as max_id
                    FROM survey
                    GROUP BY id_match, id_equipe
                ) latest ON s.id = latest.max_id
                WHERE $query
                ORDER BY id DESC";
    }
}