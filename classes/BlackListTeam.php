<?php
require_once __DIR__ . "/Generic.php";
require_once __DIR__ . "/Club.php";

class BlackListTeam extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_team';
    }

    public function saveBlacklistTeam($id_team,
                                      $closed_date,
                                      $dirtyFields = null,
                                      $id = null)
    {
        // Un responsable de club ne peut déclarer une indisponibilité que pour
        // une équipe de son club.
        if (!UserManager::isAdmin()) {
            if (!UserManager::isClubLeader()) {
                throw new Exception("Vous n'êtes pas autorisé à effectuer cette action !", 403);
            }
            (new Club())->assertManagesTeam($id_team);
        }
        $inputs = array();
        $inputs['id_team'] = $id_team;
        $inputs['closed_date'] = $closed_date;
        $inputs['dirtyFields'] = $dirtyFields;
        $inputs['id'] = $id;
        $this->save($inputs);
    }

    /**
     * Indisponibilités des équipes du club du responsable connecté.
     * @throws Exception
     */
    public function getMyClubBlacklistTeam(): array
    {
        $id_club = (new Club())->getMyClubId();
        $sql = "SELECT  bt.id,
                        bt.id_team,
                        CONCAT(e.nom_equipe, ' (', e.code_competition, ')') AS libelle_equipe,
                        DATE_FORMAT(bt.closed_date, '%d/%m/%Y') AS closed_date
                FROM blacklist_team bt
                JOIN equipes e ON e.id_equipe = bt.id_team
                WHERE e.id_club = ?
                ORDER BY bt.closed_date";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Supprime une indisponibilité d'équipe. Pour un responsable de club,
     * vérifie que l'équipe appartient bien à son club.
     * @throws Exception
     */
    public function removeBlacklistTeam($id): void
    {
        if (!UserManager::isAdmin()) {
            if (!UserManager::isClubLeader()) {
                throw new Exception("Vous n'êtes pas autorisé à effectuer cette action !", 403);
            }
            $row = $this->get_by_id($id);
            (new Club())->assertManagesTeam($row['id_team']);
        }
        $this->delete($id);
        $this->addActivity("Une indisponibilité d'équipe a été supprimée");
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_team SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'closed_date':
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'id_team':
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
        if (empty($inputs['id'])) {
        } else {
            $bindings[] = array('type' => 'i', 'value' => $inputs['id']);
            $sql .= " WHERE id = ?";
        }
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBlacklistTeam(): array
    {
        $sql = "SELECT  bt.id, 
                        bt.id_team, 
                        CONCAT(e.nom_equipe, ' (', e.code_competition, ')') AS libelle_equipe,
                        DATE_FORMAT(bt.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_team bt
                JOIN equipes e ON e.id_equipe = bt.id_team";
        return $this->sql_manager->execute($sql);
    }

}