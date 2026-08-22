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
     * Identifiant du club COURANT du responsable de club connecté.
     * Un compte peut être rattaché à plusieurs clubs (users_clubs) : la session
     * porte la liste complète (club_ids) et le club courant (id_club), posés au
     * login puis modifiables par UserManager::switchCurrentUserClub().
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
     * Identifiants de TOUS les clubs gérés par le responsable connecté.
     * Repli sur le club courant pour les sessions ouvertes avant l'arrivée de
     * club_ids en session.
     * @throws Exception
     */
    public function getMyClubIds(): array
    {
        @session_start();
        if (!UserManager::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        if (!empty($_SESSION['club_ids'])) {
            return array_map('intval', $_SESSION['club_ids']);
        }
        if (!empty($_SESSION['id_club'])) {
            return array((int)$_SESSION['id_club']);
        }
        throw new Exception("Aucun club n'est rattaché à votre compte !", 403);
    }

    /**
     * Clubs gérés par le responsable connecté (sélecteur de club).
     * @throws Exception
     */
    public function getMyClubs(): array
    {
        $club_ids = $this->getMyClubIds();
        $placeholders = implode(',', array_fill(0, count($club_ids), '?'));
        $sql = "SELECT  c.id,
                        c.nom
                FROM clubs c
                WHERE c.id IN ($placeholders)
                ORDER BY c.nom";
        $bindings = array_map(static function ($id_club) {
            return array('type' => 'i', 'value' => $id_club);
        }, $club_ids);
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Club de l'équipe si celle-ci appartient à l'un des clubs gérés par le
     * responsable connecté, null sinon.
     * @throws Exception
     */
    public function getClubIdOfManagedTeam($id_equipe): ?int
    {
        if (empty($id_equipe) || !is_numeric($id_equipe)) {
            return null;
        }
        $results = $this->sql_manager->execute(
            "SELECT id_club FROM equipes WHERE id_equipe = ?",
            array(array('type' => 'i', 'value' => $id_equipe)));
        if (count($results) === 0 || empty($results[0]['id_club'])) {
            return null;
        }
        $id_club = (int)$results[0]['id_club'];
        return in_array($id_club, $this->getMyClubIds(), true) ? $id_club : null;
    }

    /**
     * Liste les équipes du club COURANT du responsable de club connecté
     * (reconduction d'inscription, indisponibilités, comptes responsables).
     * Pour les équipes sélectionnables tous clubs confondus, voir
     * UserManager::getMyManageableTeams().
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
                    CONCAT(e.nom_equipe, IFNULL(CONCAT(' (', comp.libelle, ')'), '')) AS team_full_name,
                    (SELECT COUNT(DISTINCT cl.code_competition)
                       FROM classements cl
                      WHERE cl.id_equipe = e.id_equipe) AS nb_competitions,
                    (SELECT GROUP_CONCAT(DISTINCT CONCAT(cc.libelle, IFNULL(CONCAT(' ', cl.division), '')) ORDER BY cc.libelle SEPARATOR ', ')
                       FROM classements cl
                       JOIN competitions cc ON cc.code_competition = cl.code_competition
                      WHERE cl.id_equipe = e.id_equipe) AS competitions
                FROM equipes e
                LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
                WHERE e.id_club = ?
                ORDER BY comp.libelle, e.nom_equipe";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Vérifie que l'équipe appartient bien à l'un des clubs du responsable
     * connecté (pas seulement au club courant : la frontière d'autorisation
     * est le rattachement users_clubs, pas la bascule d'écran).
     * @throws Exception
     */
    public function assertManagesTeam($id_equipe): void
    {
        if ($this->getClubIdOfManagedTeam($id_equipe) === null) {
            throw new Exception("Cette équipe n'appartient pas à votre club !", 403);
        }
    }


}