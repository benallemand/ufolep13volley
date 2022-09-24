<?php
require_once __DIR__ . "/../includes/fonctions_inc.php";
require_once __DIR__ . '/../classes/Configuration.php';
require_once __DIR__ . '/../classes/SqlManager.php';


class Players
{
    private $sql_manager;

    public function __construct()
    {
        $this->sql_manager = new SqlManager();
    }


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
    public function get_related_emails(int $player_id): array
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
        $results = $this->sql_manager->getResults($sql);
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
    public function get_players(string $where = "1=1"): array
    {
        $sql = "SELECT
                CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
                j.prenom, 
                j.nom, 
                j.telephone, 
                j.email, 
                j.num_licence,
                CONCAT(LPAD(j.departement_affiliation, 3, '0'), j.num_licence) AS num_licence_ext,
                p.path_photo,
                j.sexe, 
                j.departement_affiliation, 
                j.est_actif+0 AS est_actif, 
                j.id_club, 
                c.nom AS club, 
                j.telephone2, 
                j.email2, 
                j.est_responsable_club+0 AS est_responsable_club, 
                je.is_captain+0 AS is_captain,
                je.is_vice_leader+0 AS is_vice_leader,
                je.is_leader+0 AS is_leader,
                j.show_photo+0 AS show_photo,
                j.id, 
                GROUP_CONCAT( concat(e.nom_equipe, ' (', comp.libelle, ')', ' (D', cl.division, ')') SEPARATOR ', ') AS teams_list,
                DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation
        FROM joueurs j
            LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
            LEFT JOIN equipes e ON e.id_equipe=je.id_equipe
            LEFT JOIN clubs c ON c.id = j.id_club
            LEFT JOIN photos p ON p.id = j.id_photo
            LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
            LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
        WHERE $where
        GROUP BY j.id
        ORDER BY j.sexe, j.nom";
        return $this->sql_manager->getResults($sql);
    }

    /**
     * @throws Exception
     */
    public function update_player($parameters)
    {
        if (empty($parameters['id'])) {
            if (!empty($parameters['num_licence'])) {
                if (isPlayerExists($parameters['num_licence'])) {
                    throw new Exception("Un joueur avec le même numéro de licence existe déjà !");
                }
            }
        }
        $bindings = array();
        if (empty($parameters['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " joueurs SET ";
        foreach ($parameters as $key => $value) {
            switch ($key) {
                case 'id':
                case 'id_team':
                case 'dirtyFields':
                    continue;
                case 'departement_affiliation':
                case 'id_club':
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $value);
                    break;
                case 'date_homologation':
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    $bindings[] = array('type' => 's', 'value' => $value);
                    break;
                case 'est_actif':
                case 'est_responsable_club':
                case 'show_photo':
                    $val = ($value === 'on') ? 1 : 0;
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $val);
                    break;
                default:
                    if (empty($parameters[$key]) || $parameters[$key] == 'null') {
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
        $newId = $this->sql_manager->execute($sql, $bindings);
        if (empty($parameters['id'])) {
            if (isTeamLeader()) {
                if ($newId > 0) {
                    if (!addPlayerToMyTeam($newId)) {
                        throw new Exception("Erreur durant l'ajout du joueur à l'équipe");
                    }
                }
            }
        }
        if (empty($parameters['id'])) {
            $firstName = $parameters['prenom'];
            $name = $parameters['nom'];
            $comment = "Creation d'un nouveau joueur : $firstName $name";
            addActivity($comment);
        } else {
            $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
            if ($dirtyFields) {
                $fieldsArray = explode(',', $dirtyFields);
                foreach ($fieldsArray as $fieldName) {
                    $fieldValue = filter_input(INPUT_POST, $fieldName);
                    $firstName = $parameters['prenom'];
                    $name = $parameters['nom'];
                    $comment = "$firstName $name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                    addActivity($comment);
                    if ($fieldName === 'est_actif') {
                        if ($fieldValue === 'on') {
                            if (empty($parameters['id'])) {
                                $player_id = $newId;
                            } else {
                                $player_id = $parameters['id'];
                            }
                            require_once __DIR__ . '/../classes/Emails.php';
                            $email_manager = new Emails();
                            $email_manager->insert_email_notify_activated_player($player_id);
                        }
                    }
                }
            }
        }
        savePhoto($parameters, $newId);
    }
}