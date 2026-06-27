<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';

class Club extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'clubs';
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT * 
                FROM $this->table_name
                WHERE $query
                ORDER BY nom";
    }

    /**
     * @throws Exception
     */
    public function deleteClubs($ids) {
        return $this->delete($ids);
    }
    /**
     * @throws Exception
     */
    public function saveClub($id,
                             $nom,
                             $affiliation_number,
                             $nom_responsable,
                             $prenom_responsable,
                             $tel1_responsable,
                             $tel2_responsable,
                             $email_responsable,
                             $dirtyFields = null
    ): array|int|string|null
    {
        return $this->save(array(
            'id' => $id,
            'nom' => $nom,
            'affiliation_number' => $affiliation_number,
            'nom_responsable' => $nom_responsable,
            'prenom_responsable' => $prenom_responsable,
            'tel1_responsable' => $tel1_responsable,
            'tel2_responsable' => $tel2_responsable,
            'email_responsable' => $email_responsable,
            'dirtyFields' => $dirtyFields,
        ));
    }

    /**
     * @param $inputs
     * @throws Exception
     */
    public function save($inputs)
    {
        $result = parent::save($inputs);
        $subject = "Club " . $inputs['nom'];
        $this->addActivity($this->build_activity($subject, $inputs['dirtyFields'] ?? null, $inputs));
        return $result;
    }

    public function getClubName($idClub)
    {
        $sql = "SELECT
        c.nom AS club_name
        FROM clubs c
        WHERE c.id = $idClub";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['club_name'];
    }

    /**
     * Identifiant du club du responsable de club connecté.
     * Le club est posé en session au login (depuis users_clubs).
     * @throws Exception
     */
    public function getMyClubId(): int
    {
        @session_start();
        if (!UserManager::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        if (empty($_SESSION['id_club'])) {
            throw new Exception("Aucun club n'est rattaché à votre compte !", 403);
        }
        return (int)$_SESSION['id_club'];
    }

    /**
     * Liste les équipes du club du responsable de club connecté
     * (sert au sélecteur d'équipe de l'espace responsable).
     * @throws Exception
     */
    public function getMyClubTeams(): array
    {
        $id_club = $this->getMyClubId();
        $sql = "SELECT
                    e.id_equipe,
                    e.nom_equipe,
                    e.code_competition,
                    comp.libelle AS libelle_competition,
                    CONCAT(e.nom_equipe, IFNULL(CONCAT(' (', comp.libelle, ')'), '')) AS team_full_name
                FROM equipes e
                LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
                WHERE e.id_club = ?
                ORDER BY comp.libelle, e.nom_equipe";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Vérifie que l'équipe appartient bien au club du responsable connecté.
     * @throws Exception
     */
    public function assertManagesTeam($id_equipe): void
    {
        foreach ($this->getMyClubTeams() as $team) {
            if ($team['id_equipe'] == $id_equipe) {
                return;
            }
        }
        throw new Exception("Cette équipe n'appartient pas à votre club !", 403);
    }


}