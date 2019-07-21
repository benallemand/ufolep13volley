<?php
require_once __DIR__ . '/../classes/Configuration.php';
require_once __DIR__ . '/../classes/SqlManager.php';


class Players
{

    /**
     * @param int $player_id
     * @return array
     * @throws Exception
     */
    public function get_player(int $player_id)
    {
        $results = $this->get_players("j.id = $player_id");
        if (count($results) !== 1) {
            throw new Exception("Error during SQL request: 1 and only 1 result is expected");
        }
        return $results[0];
    }

    /**
     * @param int $player_id
     * @return array
     * @throws Exception
     */
    public function get_related_emails(int $player_id)
    {
        $sql = "SELECT GROUP_CONCAT(DISTINCT j.email)           AS player_email_1,
                       GROUP_CONCAT(DISTINCT j.email2)          AS player_email_2,
                       GROUP_CONCAT(DISTINCT j_leader.email)    AS leader_email_1,
                       GROUP_CONCAT(DISTINCT j_leader.email2)   AS leader_email_2,
                       GROUP_CONCAT(DISTINCT j_captain.email)   AS captain_email_1,
                       GROUP_CONCAT(DISTINCT j_captain.email2)  AS captain_email_2,
                       GROUP_CONCAT(DISTINCT j_v_leader.email)  AS v_leader_email_1,
                       GROUP_CONCAT(DISTINCT j_v_leader.email2) AS v_leader_email_2
                FROM joueurs j
                         LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
                         LEFT JOIN equipes e ON e.id_equipe = je.id_equipe AND e.id_equipe IN (SELECT id_equipe FROM classements)
                         LEFT JOIN joueur_equipe je_leader ON je_leader.id_equipe = e.id_equipe AND je_leader.is_leader + 0 > 0
                         LEFT JOIN joueurs j_leader ON j_leader.id = je_leader.id_joueur
                         LEFT JOIN joueur_equipe je_captain ON je_captain.id_equipe = e.id_equipe AND je_captain.is_captain + 0 > 0
                         LEFT JOIN joueurs j_captain ON j_captain.id = je_captain.id_joueur
                         LEFT JOIN joueur_equipe je_v_leader ON je_v_leader.id_equipe = e.id_equipe AND je_v_leader.is_vice_leader + 0 > 0
                         LEFT JOIN joueurs j_v_leader ON j_v_leader.id = je_v_leader.id_joueur
                WHERE j.id = $player_id
                GROUP BY j.id";
        $sql_manager = new SqlManager();
        $results = $sql_manager->getResults($sql);
        $related_emails = array();
        foreach ($results as $result) {
            foreach ($result as $key => $value) {
                if (!empty($value)) {
                    $emails = explode(',', $value);
                    foreach ($emails as $email) {
                        $related_emails[] = $email;
                    }
                }
            }
        }
        return array_unique($related_emails);
    }

    /**
     * @param string $where
     * @return array
     * @throws Exception
     */
    public function get_players(string $where = "1=1")
    {
        $sql = "SELECT
               CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
               j.prenom, 
               j.nom, 
               j.telephone, 
               j.email, 
               j.num_licence,
               p.path_photo,
               j.sexe, 
               j.departement_affiliation, 
               j.est_actif+0 AS est_actif, 
               j.id_club, 
               c.nom AS club, 
               j.telephone2, 
               j.email2, 
               j.est_responsable_club+0 AS est_responsable_club, 
               j.show_photo+0 AS show_photo,
               j.id, 
               GROUP_CONCAT( CONCAT(e.nom_equipe, '(',e.code_competition,')') SEPARATOR ', ') AS teams_list,
               DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation
        FROM joueurs j
            LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
            LEFT JOIN equipes e ON e.id_equipe=je.id_equipe AND e.id_equipe IN (SELECT id_equipe FROM classements)
            LEFT JOIN clubs c ON c.id = j.id_club
            LEFT JOIN photos p ON p.id = j.id_photo
        WHERE $where
        GROUP BY j.id";
        $sql_manager = new SqlManager();
        return $sql_manager->getResults($sql);
    }
}