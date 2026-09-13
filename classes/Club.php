<?php
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/UserManager.php';

class Club extends Generic
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'clubs';
    }

    public function getSql($query = "1=1"): string
    {
        // `comptes` : les comptes rattachés au club (issue #326). C'est eux, le
        // référent du club — pas les colonnes `*_responsable`, qui partiront
        // avec #327. La grille d'administration en fait une colonne, pour que
        // le manque se voie là où on le corrige.
        return "SELECT c.*,
                       (SELECT GROUP_CONCAT(DISTINCT ca.email ORDER BY ca.email SEPARATOR ', ')
                          FROM users_clubs uc
                                   JOIN comptes_acces ca ON ca.id = uc.user_id
                         WHERE uc.club_id = c.id) AS comptes
                FROM $this->table_name c
                WHERE $query
                ORDER BY c.nom";
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
     * Adresses proposables pour créer le compte d'un club (issue #326).
     *
     * Le rattrapage des comptes manquants se fait club par club, et l'adresse
     * à reprendre est presque toujours déjà quelque part : dans les
     * coordonnées du club, ou chez l'une de ses personnes. Les proposer évite
     * de la ressaisir — et donc de la saisir de travers.
     *
     * `email_responsable` disparaîtra avec les colonnes `clubs.*_responsable`
     * (#327) ; les personnes du club, elles, resteront.
     *
     * @throws Exception
     */
    public function getAccountCandidates($id_club): array
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut faire ça !", 403);
        }
        $id_club = (int)$id_club;
        $binding = array(array('type' => 'i', 'value' => $id_club));
        $candidats = array();

        $club = $this->sql_manager->execute(
            "SELECT nom, prenom_responsable, nom_responsable, email_responsable
             FROM clubs WHERE id = ?", $binding);
        if (count($club) === 0) {
            throw new Exception("Ce club n'existe pas !");
        }
        $email_club = trim((string)($club[0]['email_responsable'] ?? ''));
        if ($email_club !== '') {
            $candidats[strtolower($email_club)] = array(
                'email' => $email_club,
                'label' => trim(($club[0]['prenom_responsable'] ?? '') . ' ' . ($club[0]['nom_responsable'] ?? '')),
                'origine' => 'coordonnées du club',
            );
        }

        // Les personnes du club qui ont une adresse. Un responsable d'équipe
        // est proposé en premier : c'est le profil le plus probable.
        $personnes = $this->sql_manager->execute(
            "SELECT j.prenom,
                    j.nom,
                    j.email,
                    EXISTS(SELECT 1
                             FROM joueur_equipe je
                            WHERE je.id_joueur = j.id
                              AND je.is_leader + 0 > 0) AS est_responsable
             FROM joueurs j
             WHERE j.id_club = ?
               AND NULLIF(TRIM(j.email), '') IS NOT NULL
             ORDER BY est_responsable DESC, j.nom, j.prenom", $binding);
        foreach ($personnes as $personne) {
            $cle = strtolower(trim($personne['email']));
            if (isset($candidats[$cle])) {
                continue;
            }
            $candidats[$cle] = array(
                'email' => trim($personne['email']),
                'label' => trim($personne['prenom'] . ' ' . $personne['nom']),
                'origine' => ((int)$personne['est_responsable'] === 1)
                    ? "responsable d'équipe"
                    : 'personne du club',
            );
        }
        return array_values($candidats);
    }

    /**
     * Crée le compte d'un club, ou rattache au club un compte existant
     * (issue #326).
     *
     * Le référent d'un club, c'est son compte : `users_clubs` est la seule
     * source où l'email est à la fois obligatoire et unique, et c'est cette
     * ligne qui porte le rôle (issue #245). Les identifiants partent
     * immédiatement, pas au cron horaire — la création se fait à l'unité, en
     * face de quelqu'un qui attend (issue #305).
     *
     * @throws Exception
     */
    public function createClubAccount($id_club, $email): void
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut faire ça !", 403);
        }
        $id_club = (int)$id_club;
        $email = trim((string)$email);
        if ($id_club <= 0) {
            throw new Exception("Aucun club n'est désigné !");
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception("« $email » n'est pas une adresse email valide !");
        }
        $club = $this->sql_manager->execute(
            "SELECT nom FROM clubs WHERE id = ?",
            array(array('type' => 'i', 'value' => $id_club))
        );
        if (count($club) === 0) {
            throw new Exception("Ce club n'existe pas !");
        }
        (new UserManager())->create_or_update_club_account($email, $id_club);
        $this->addActivity("Compte de club rattache a " . $club[0]['nom'] . " : $email");
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