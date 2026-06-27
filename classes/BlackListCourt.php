<?php
require_once __DIR__ . "/Generic.php";
require_once __DIR__ . "/Club.php";

class BlackListCourt extends Generic
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'blacklist_gymnase';
    }

    public function save($inputs)
    {
        $bindings = array();
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " blacklist_gymnase SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                case 'dirtyFields':
                    break;
                case 'closed_date':
                    $bindings[] = array('type' => 's', 'value' => $value);
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                case 'id_gymnase':
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
    public function getBlacklistGymnase(): array
    {
        $sql = "SELECT  bg.id, 
                        bg.id_gymnase, 
                        CONCAT(g.nom, ' (', g.ville, ')') AS libelle_gymnase,
                        DATE_FORMAT(bg.closed_date, '%d/%m/%Y') AS closed_date 
                FROM blacklist_gymnase bg
                JOIN gymnase g on bg.id_gymnase = g.id";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveBlacklistGymnase($id_gymnase, $closed_date, $dirtyFields=null, $id=null) {
        // Un responsable de club ne peut fermer qu'un gymnase utilisé par son club.
        if (!UserManager::isAdmin()) {
            if (!UserManager::isClubLeader()) {
                throw new Exception("Vous n'êtes pas autorisé à effectuer cette action !", 403);
            }
            $this->assertClubGymnasium($id_gymnase);
        }
        $inputs = array(
            'id_gymnase' => $id_gymnase,
            'closed_date' => $closed_date,
            'dirtyFields' => $dirtyFields,
            'id' => $id,
        );
        return $this->save($inputs);
    }

    /**
     * Gymnases utilisés par les équipes du club du responsable connecté
     * (via leurs créneaux). Sert au sélecteur de fermetures.
     * @throws Exception
     */
    public function getMyClubGymnasiums(): array
    {
        $id_club = (new Club())->getMyClubId();
        $sql = "SELECT DISTINCT
                    g.id,
                    CONCAT(g.nom, ' (', g.ville, ')') AS full_name
                FROM creneau cr
                JOIN equipes e ON e.id_equipe = cr.id_equipe
                JOIN gymnase g ON g.id = cr.id_gymnase
                WHERE e.id_club = ?
                ORDER BY full_name";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Fermetures des gymnases du club du responsable connecté.
     * @throws Exception
     */
    public function getMyClubBlacklistGymnase(): array
    {
        $id_club = (new Club())->getMyClubId();
        $sql = "SELECT  bg.id,
                        bg.id_gymnase,
                        CONCAT(g.nom, ' (', g.ville, ')') AS libelle_gymnase,
                        DATE_FORMAT(bg.closed_date, '%d/%m/%Y') AS closed_date
                FROM blacklist_gymnase bg
                JOIN gymnase g ON bg.id_gymnase = g.id
                WHERE bg.id_gymnase IN (
                    SELECT DISTINCT cr.id_gymnase
                    FROM creneau cr
                    JOIN equipes e ON e.id_equipe = cr.id_equipe
                    WHERE e.id_club = ?
                )
                ORDER BY bg.closed_date";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Supprime une fermeture de gymnase. Pour un responsable de club, vérifie
     * que la fermeture concerne bien un gymnase de son club.
     * @throws Exception
     */
    public function removeBlacklistGymnase($id): void
    {
        if (!UserManager::isAdmin()) {
            if (!UserManager::isClubLeader()) {
                throw new Exception("Vous n'êtes pas autorisé à effectuer cette action !", 403);
            }
            $row = $this->get_by_id($id);
            $this->assertClubGymnasium($row['id_gymnase']);
        }
        $this->delete($id);
        $this->addActivity("Une fermeture de gymnase a été supprimée");
    }

    /**
     * Vérifie que le gymnase fait partie de ceux utilisés par le club du
     * responsable connecté.
     * @throws Exception
     */
    private function assertClubGymnasium($id_gymnase): void
    {
        foreach ($this->getMyClubGymnasiums() as $gym) {
            if ($gym['id'] == $id_gymnase) {
                return;
            }
        }
        throw new Exception("Ce gymnase n'est pas utilisé par votre club !", 403);
    }

}