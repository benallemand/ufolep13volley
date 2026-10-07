<?php

require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Club.php';
require_once __DIR__ . '/Photo.php';
require_once __DIR__ . '/Files.php';
require_once __DIR__ . '/UserManager.php';


class Players extends Generic
{
    private Files $files;
    private Club $club;
    private Team $team;
    private Photo $photo;
    private UserManager $userManager;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'joueurs';
        $this->team = new Team();
        $this->club = new Club();
        $this->photo = new Photo();
        $this->files = new Files();
        $this->userManager = new UserManager();
    }


    public function getSql($query = "1=1"): string
    {
        return "SELECT j.* 
                FROM players_view j
                WHERE $query";
    }

    /**
     * @param int $player_id
     * @return array
     * @throws Exception
     */
    public function get_player(int $player_id): array
    {
        $results = $this->get_players("j.id = $player_id");
        if (count($results) !== 1) {
            throw new Exception("Erreur, un seul résultat attendu !");
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
        $results = $this->sql_manager->execute($sql);
        $related_emails = array();
        foreach ($results as $result) {
            foreach ($result as $value) {
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
     * @throws Exception
     */
    public function getMyPlayers()
    {
        @session_start();
        // les rôles se cumulent (issue #251) : seul compte le rôle
        // responsable d'équipe, qu'il soit admin ou non
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable d'équipe peut faire ça !");
        }
        $id_team = $_SESSION['id_equipe'];
        $players = $this->get_players("j.id IN 
        (
            SELECT id_joueur 
            FROM joueur_equipe 
            WHERE id_equipe = $id_team
        )");
        // `players_view` agrege les roles toutes equipes confondues : on les
        // ramene a l'equipe courante. `est_jouant` est porte par
        // l'appartenance, il n'est donc pas dans la vue — on le lit a part
        // (issue #325).
        $playing = $this->getPlayingFlagsByTeam($id_team);
        foreach ($players as $index => $player) {
            $players[$index]['is_captain'] = empty($player['id_captain']) ? 0 : in_array($id_team, explode(',', $player['id_captain']));
            $players[$index]['is_vice_leader'] = empty($player['id_vl']) ? 0 : in_array($id_team, explode(',', $player['id_vl']));
            $players[$index]['is_leader'] = empty($player['id_l']) ? 0 : in_array($id_team, explode(',', $player['id_l']));
            $players[$index]['est_jouant'] = $playing[(int)$player['id']] ?? 1;
        }
        return $players;
    }

    /**
     * `est_jouant` de chaque membre d'une equipe, indexe par identifiant de
     * joueur (issue #325).
     *
     * @return array<int, int>
     * @throws Exception
     */
    private function getPlayingFlagsByTeam($id_team): array
    {
        $sql = "SELECT id_joueur, est_jouant + 0 AS est_jouant
                FROM joueur_equipe
                WHERE id_equipe = ?";
        $bindings = array(array('type' => 'i', 'value' => (int)$id_team));
        $flags = array();
        foreach ($this->sql_manager->execute($sql, $bindings) as $row) {
            $flags[(int)$row['id_joueur']] = (int)$row['est_jouant'];
        }
        return $flags;
    }

    /**
     * @param string $where
     * @param string $order_by
     * @return array
     * @throws Exception
     */
    public function get_players(string $where = "1=1", string $order_by = "j.sexe, UPPER(j.nom)"): array
    {
        $sql = "SELECT j.* 
                FROM players_view j
                WHERE $where
                ORDER BY $order_by";
        $results = $this->sql_manager->execute($sql);
        return Players::adjust_photo_path_from_results($results);
    }

    /**
     * @throws Exception
     */
    public function update_player(
        $id_team,
        $prenom,
        $nom,
        $sexe,
        $departement_affiliation,
        $id_club,
        $num_licence = null,
        $date_homologation = null,
        $telephone = null,
        $email = null,
        $telephone2 = null,
        $email2 = null,
        $id = null,
        $add_to_my_team = null): array|int|string|null
    {
        $parameters = array(
            'add_to_my_team' => $this->adds_to_my_team($add_to_my_team),
            'id_team' => $id_team,
            'prenom' => $prenom,
            'nom' => $nom,
            'num_licence' => $num_licence,
            'date_homologation' => $date_homologation,
            'sexe' => $sexe,
            'departement_affiliation' => $departement_affiliation,
            'id_club' => $id_club,
            'telephone' => $telephone,
            'email' => $email,
            'telephone2' => $telephone2,
            'email2' => $email2,
            'id' => $id,
        );
        // modifier un joueur existant : il doit être dans vos équipes ou votre club —
        // sinon n'importe quel joueur était modifié, puis ajouté à l'équipe (issue #356)
        if (!empty($parameters['id'])) {
            (new UserManager())->assertCanManagePlayer($parameters['id']);
        }
        if (empty($parameters['id'])) {
            if (!empty($parameters['num_licence'])) {
                if ($this->isPlayerExists($parameters['num_licence'])) {
                    throw new Exception($parameters['num_licence'] .
                        " : Un joueur avec le même numéro de licence existe déjà !");
                }
            }
        }
        return $this->save($parameters);
    }

    /**
     * @throws Exception
     */
    public function create($first_name, $last_name, $leader_phone, $leader_email, $id_club)
    {
        return $this->update_player(
            null,
            $first_name,
            $last_name,
            null,
            null,
            $id_club,
            null,
            null,
            $leader_phone,
            $leader_email);
    }

    /**
     * @throws Exception
     */
    public function getPlayerFullName($idPlayer)
    {
        $sql = "SELECT 
        CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS player_full_name
        FROM joueurs j
        WHERE j.id = ?";
        // issue #270
        $bindings = array(array('type' => 'i', 'value' => $idPlayer));
        $results = $this->sql_manager->execute($sql, $bindings);
        return $results[0]['player_full_name'];
    }

    /**
     * @throws Exception
     */
    /**
     * Fusionne deux fiches d'un même joueur (issue #409) : on garde
     * `$id_keep`, on y reporte ce que porte `$id_remove`, puis on supprime
     * celle-ci. C'est la correction des « Licences dupliquées » et des
     * « Joueurs potentiellement en doublon » : le plus souvent, une fiche
     * créée à la main en double de la vraie.
     *
     * Reporté sur la fiche gardée :
     *   - les équipes : une équipe des deux côtés n'est pas dupliquée, les
     *     rôles (responsable, suppléant, capitaine) et « joue » s'ajoutent ;
     *   - les feuilles de match : un match des deux côtés n'est pas dupliqué ;
     *   - les champs vides (licence, homologation, contacts, club, photo) ;
     *   - le compte, s'il n'en a pas.
     *
     * Réservé à l'administrateur, en une transaction, et journalisé.
     *
     * @throws Exception
     */
    public function mergePlayers($id_keep = null, $id_remove = null): void
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un administrateur peut fusionner deux fiches !", 403);
        }
        $keep = Generic::parse_id($id_keep, 'fiche à garder');
        $remove = Generic::parse_id($id_remove, 'fiche à supprimer');
        if ($keep === $remove) {
            throw new Exception("Choisissez deux fiches différentes.", 400);
        }
        $rows = $this->sql_manager->execute("SELECT id, id_compte FROM joueurs WHERE id IN (?, ?)", array(
            array('type' => 'i', 'value' => $keep),
            array('type' => 'i', 'value' => $remove),
        ));
        if (count($rows) !== 2) {
            throw new Exception("Fiche introuvable.", 404);
        }
        $keepName = $this->getPlayerFullName($keep);
        $removeName = $this->getPlayerFullName($remove);
        $removeAccount = null;
        foreach ($rows as $row) {
            if ((int)$row['id'] === $remove) {
                $removeAccount = $row['id_compte'];
            }
        }
        $ids = array(array('type' => 'i', 'value' => $keep), array('type' => 'i', 'value' => $remove));
        $db = Database::openDbConnection();
        mysqli_begin_transaction($db);
        try {
            // Équipes : rôles cumulés là où les deux fiches figurent, puis
            // appartenances de l'autre fiche reportées sur la fiche gardée.
            $this->sql_manager->execute(
                "UPDATE joueur_equipe k
                     JOIN joueur_equipe r ON r.id_equipe = k.id_equipe AND r.id_joueur = ?
                 SET k.est_jouant     = (k.est_jouant + 0) | (r.est_jouant + 0),
                     k.is_leader      = (COALESCE(k.is_leader, 0) + 0) | (COALESCE(r.is_leader, 0) + 0),
                     k.is_vice_leader = (COALESCE(k.is_vice_leader, 0) + 0) | (COALESCE(r.is_vice_leader, 0) + 0),
                     k.is_captain     = (COALESCE(k.is_captain, 0) + 0) | (COALESCE(r.is_captain, 0) + 0)
                 WHERE k.id_joueur = ?",
                array(array('type' => 'i', 'value' => $remove), array('type' => 'i', 'value' => $keep)));
            $this->sql_manager->execute(
                "DELETE r FROM joueur_equipe r
                     JOIN joueur_equipe k ON k.id_equipe = r.id_equipe AND k.id_joueur = ?
                 WHERE r.id_joueur = ?", $ids);
            $this->sql_manager->execute("UPDATE joueur_equipe SET id_joueur = ? WHERE id_joueur = ?", $ids);
            // Feuilles de match : même règle, sans doublon.
            $this->sql_manager->execute(
                "DELETE r FROM match_player r
                     JOIN match_player k ON k.id_match = r.id_match AND k.id_player = ?
                 WHERE r.id_player = ?", $ids);
            $this->sql_manager->execute("UPDATE match_player SET id_player = ? WHERE id_player = ?", $ids);
            // Le compte est unique : on le libère avant de le reporter.
            $this->sql_manager->execute("UPDATE joueurs SET id_compte = NULL WHERE id = ?",
                array(array('type' => 'i', 'value' => $remove)));
            $this->sql_manager->execute(
                "UPDATE joueurs k
                     JOIN joueurs r ON r.id = ?
                 SET k.num_licence             = COALESCE(NULLIF(TRIM(k.num_licence), ''), r.num_licence),
                     k.departement_affiliation = COALESCE(k.departement_affiliation, r.departement_affiliation),
                     k.date_homologation       = COALESCE(k.date_homologation, r.date_homologation),
                     k.sexe                    = COALESCE(NULLIF(k.sexe, ''), r.sexe),
                     k.id_club                 = COALESCE(NULLIF(k.id_club, 0), r.id_club),
                     k.email                   = COALESCE(NULLIF(TRIM(k.email), ''), r.email),
                     k.telephone               = COALESCE(NULLIF(TRIM(k.telephone), ''), r.telephone),
                     k.email2                  = COALESCE(NULLIF(TRIM(k.email2), ''), r.email2),
                     k.telephone2              = COALESCE(NULLIF(TRIM(k.telephone2), ''), r.telephone2),
                     k.id_photo                = COALESCE(k.id_photo, r.id_photo)
                 WHERE k.id = ?",
                array(array('type' => 'i', 'value' => $remove), array('type' => 'i', 'value' => $keep)));
            if (!empty($removeAccount)) {
                $this->sql_manager->execute("UPDATE joueurs SET id_compte = ? WHERE id = ? AND id_compte IS NULL",
                    array(array('type' => 'i', 'value' => $removeAccount), array('type' => 'i', 'value' => $keep)));
            }
            $this->sql_manager->execute("DELETE FROM joueurs WHERE id = ?",
                array(array('type' => 'i', 'value' => $remove)));
            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
        $this->addActivity("Fusion de la fiche $removeName dans $keepName");
    }

    public function delete_players($ids)
    {
        $explodedIds = explode(',', $ids);
        $playersFullNames = array();
        foreach ($explodedIds as $id) {
            $playersFullNames[] = $this->getPlayerFullName($id);
        }
        $this->delete($ids);
        foreach ($playersFullNames as $playerFullName) {
            $this->addActivity("Suppression du joueur : $playerFullName");
        }
    }

    /**
     * Enregistrement d'un joueur depuis l'administration.
     *
     * Tous les parametres ont une valeur par defaut : le routeur REST appelle
     * la methode avec des arguments nommes, et un formulaire n'envoie que les
     * champs qu'il declare. Les quinze parametres etaient obligatoires, et
     * l'ecran Vue n'en envoyait que huit -- creer ou editer un joueur echouait
     * en 500 depuis le lot 1 de #265 (issue #288).
     *
     * @throws Exception
     */
    public function savePlayer(
        $prenom = null,
        $nom = null,
        $num_licence = null,
        $date_homologation = null,
        $sexe = null,
        $departement_affiliation = null,
        $id_club = null,
        $telephone = null,
        $email = null,
        $telephone2 = null,
        $email2 = null,
        $id_team = null,
        $id = null,
    )
    {
        $inputs = array(
            'id' => $id,
            'id_team' => $id_team,
            'prenom' => $prenom,
            'nom' => $nom,
            'num_licence' => $num_licence,
            'date_homologation' => $date_homologation,
            'sexe' => $sexe,
            'departement_affiliation' => $departement_affiliation,
            'id_club' => $id_club,
            'telephone' => $telephone,
            'email' => $email,
            'telephone2' => $telephone2,
            'email2' => $email2,
        );
        $this->save($inputs);
    }

    /**
     * @param $inputs
     * @return int|array|string|null
     * @throws Exception
     */
    public function save($inputs): int|array|string|null
    {
        $bindings = array();
        // Saisi à la main, le numéro arrive souvent tel qu'imprimé sur la
        // licence, préfixe de département compris (`013_DY10000187`) : un
        // import ne le retrouverait plus (issue #404).
        if (array_key_exists('num_licence', $inputs) && is_string($inputs['num_licence'])) {
            $inputs['num_licence'] = self::normalize_licence_number($inputs['num_licence']);
        }
        if (empty($inputs['id'])) {
            if (!empty($inputs['num_licence'])) {
                if ($this->isPlayerExists($inputs['num_licence'])) {
                    throw new Exception($inputs['num_licence'] .
                        " : Un joueur avec le même numéro de licence existe déjà !");
                }
            }
        }
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " joueurs SET ";
        foreach ($inputs as $key => $value) {
            if (in_array($key, array(
                'id',
                'id_team',
                'add_to_my_team'))) {
                continue;
            }
            if (empty($value) || $value == 'null') {
                $sql .= "$key = NULL,";
                continue;
            }
            switch ($key) {
                case 'departement_affiliation':
                case 'id_club':
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
                case 'date_homologation':
                    $bindings[] = array(
                        'type' => 's',
                        'value' => $value
                    );
                    $sql .= "$key = DATE(STR_TO_DATE(?, '%d/%m/%Y')),";
                    break;
                default:
                    $bindings[] = array(
                        'type' => 's',
                        'value' => $value
                    );
                    $sql .= "$key = ?,";
                    break;
            }
        }
        $sql = trim($sql, ',');
        if (!empty($inputs['id'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id']
            );
            $sql .= " WHERE id = ?";
        }
        $before = $this->row_before($inputs['id'] ?? null, 'joueurs', 'id');
        $newId = $this->sql_manager->execute($sql, $bindings);
        // L'ajout à l'équipe courante est demandé par l'appelant
        // (`adds_to_my_team`), jamais déduit du rôle : un compte admin ET
        // responsable doit pouvoir compléter son équipe depuis son espace,
        // sans que chaque fiche modifiée depuis l'administration la rejoigne
        // (issues #407, #419).
        if (!empty($inputs['add_to_my_team'])) {
            if (!$this->addPlayerToMyTeam(!empty($newId) ? $newId : $inputs['id'])) {
                throw new Exception("Erreur durant l'ajout du joueur à l'équipe");
            }
        }
        if (empty($inputs['id'])) {
            $firstName = $inputs['prenom'];
            $name = $inputs['nom'];
            $comment = "Creation d'un nouveau joueur : $firstName $name";
            $this->addActivity($comment);
        } else {
            // L'import d'une licence ne poste ni nom ni prénom : on les prend
            // dans la fiche, sinon le journal ne dit pas de quel joueur il
            // s'agit (issue #404).
            $subject = trim(($inputs['prenom'] ?? $before['prenom'] ?? '') . ' '
                . ($inputs['nom'] ?? $before['nom'] ?? ''));
            $activity = $this->build_activity($subject, $before, $inputs);
            if ($activity !== null) {
                $this->addActivity($activity);
            }
        }
        $this->savePhoto($inputs, $newId);
        return $newId;
    }

    /**
     * @throws Exception
     */
    public function isPlayerExists($licenceNumber)
    {
        if ($licenceNumber === '') {
            return false;
        }
        // le numéro de licence vient du fichier importé / du formulaire — issue #270
        $sql = "SELECT COUNT(*) AS cnt FROM joueurs WHERE num_licence = ?";
        $bindings = array(array('type' => 's', 'value' => $licenceNumber));
        $results = $this->sql_manager->execute($sql, $bindings);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    /**
     * Import d'UN fichier de licences (issue #394) : liguasso n'en met qu'une
     * par PDF, l'écran envoie donc les fichiers un par un, quelques-uns en
     * parallèle. Un seul envoi de 100 PDF buterait sur `max_file_uploads`,
     * `post_max_size` et `max_execution_time`, réglages du mutualisé OVH.
     *
     * Rend un compte rendu par licence (le routeur le transmet tel quel, voir
     * `rest/action.php`) : `created`, `updated` ou `rejected` avec son motif,
     * et `photo` (une licence sans photo n'est pas bloquante, #343). Une
     * licence écartée n'arrête pas la suite du fichier (#404).
     *
     * @return array{message: string, report: array<int, array>}
     * @throws Exception (422) fichier absent ou sans licence reconnue
     */
    public function update_from_licence_file($add_to_my_team = null): array
    {
        // Depuis l'espace responsable, les joueurs importés rejoignent l'équipe
        // courante, administrateur compris (#419) ; depuis l'administration, non.
        $add_to_my_team = $this->adds_to_my_team($add_to_my_team);
        if (empty($_FILES['licences']['name'])) {
            throw new Exception("Aucun fichier reçu", 422);
        }
        set_time_limit(60);
        try {
            $licences = $this->files->get_licences_data($_FILES['licences']['tmp_name']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $licences = array();
        }
        if (empty($licences)) {
            throw new Exception("Aucune licence reconnue dans ce fichier : est-ce bien une licence liguasso ?", 422);
        }
        $report = array();
        foreach ($licences as $licence) {
            $line = array('joueur' => $licence['last_first_name'] ?? '?');
            try {
                $line += $this->search_player_and_save_from_licence($licence, $add_to_my_team);
            } catch (mysqli_sql_exception $e) {
                // Jamais de message MySQL brut au client (#355).
                error_log($e->getMessage());
                $line += array('status' => 'rejected', 'message' => "erreur d'enregistrement");
            } catch (Exception $e) {
                $line += array('status' => 'rejected', 'message' => $e->getMessage());
            }
            $report[] = $line;
        }
        $rejected = count(array_filter($report, static fn($line) => $line['status'] === 'rejected'));
        $message = (count($report) - $rejected) . " licence(s) importée(s)"
            . ($rejected ? ", $rejected écartée(s)" : '');
        return array('message' => $message, 'report' => $report);
    }

    public function uploadPhoto($id, $nom, $prenom)
    {
        (new UserManager())->assertCanManagePlayer($id); // issue #356
        $this->savePhoto(array(
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom
        ));
        $player = $this->get_player($id);
        if (!file_exists($player['path_photo_low'])) {
            $this->generateLowPhoto($player['path_photo']);
        }
    }

    /**
     * @param $inputs
     * @param int $newId
     * @throws Exception
     */
    public function savePhoto($inputs, $newId = 0)
    {
        if (empty($_FILES['photo']['name'])) {
            return;
        }
        $lastName = $inputs['nom'];
        $firstName = $inputs['prenom'];
        $uploaddir = '../players_pics/';
        $iteration = 1;
        $uploadfile = "$uploaddir$lastName$firstName$iteration.jpg";
        while (file_exists($uploadfile)) {
            $iteration++;
            $uploadfile = "$uploaddir$lastName$firstName$iteration.jpg";
        }
        $idPhoto = $this->photo->insertPhoto(substr($uploadfile, 3));
        $idPlayer = $inputs['id'];
        if (empty($inputs['id'])) {
            $idPlayer = $newId;
        }
        $this->linkPlayerToPhoto($idPlayer, $idPhoto);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], Generic::accentedToNonAccented($uploadfile))) {
            $this->addActivity("Une nouvelle photo a ete transmise pour le joueur $firstName $lastName");
        }
    }

    /**
     * @param $idPlayer
     * @param $idPhoto
     * @throws Exception
     */
    public function linkPlayerToPhoto($idPlayer, $idPhoto)
    {
        // issue #270
        $sql = "UPDATE joueurs j SET j.id_photo = ? WHERE id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idPhoto),
            array('type' => 'i', 'value' => $idPlayer),
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $idPlayer
     * @return void
     * @throws Exception
     */
    function removePlayerFromMyTeam($idPlayer)
    {
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un profil responsable d'équipe peut faire cette action !");
        }
        $idTeam = $_SESSION['id_equipe'];
        if (!$this->isPlayerInTeam($idPlayer, $idTeam)) {
            throw new Exception("Ce joueur n'est pas dans l'équipe !");
        }
        $sql = "DELETE FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
        $this->addActivity($this->getPlayerFullName($idPlayer) . " a ete supprime de l'equipe " . $this->team->getTeamName($idTeam));
    }

    /**
     * @throws Exception
     */
    public function isPlayerInTeam($idPlayer, $idTeam)
    {
        // $idPlayer vient du client (actions du responsable d'équipe) — issue #270
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idPlayer),
            array('type' => 'i', 'value' => $idTeam),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function get_players_by_team($id_team): array
    {
        $sql = "SELECT j.id, j.prenom, j.nom, CONCAT(j.prenom, ' ', j.nom) AS full_name,
                       IF(je.id_equipe IS NOT NULL, 1, 0) AS is_in_team
                FROM joueurs j
                JOIN equipes e ON e.id_club = j.id_club
                LEFT JOIN joueur_equipe je ON je.id_joueur = j.id AND je.id_equipe = e.id_equipe
                WHERE e.id_equipe = ?
                ORDER BY is_in_team DESC, j.nom, j.prenom";
        $bindings = array(
            array('type' => 'i', 'value' => $id_team)
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    public function addPlayersToTeam($id_players, $id_team)
    {
        if (!UserManager::isAdmin()) {
            return false;
        }
        $idClub = $this->team->getIdClubFromIdTeam($id_team);
        if (!$this->addPlayersToClub($id_players, $idClub)) {
            return false;
        }
        foreach (explode(',', $id_players) as $idPlayer) {
            if (!$this->addPlayerToTeam($idPlayer, $id_team)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function addPlayersToClub($id_players, $id_club)
    {
        if (!UserManager::isAdmin()) {
            if (!UserManager::isTeamLeader()) {
                return false;
            }
        }
        // $id_players et $id_club viennent de l'extérieur (issue #268)
        $id_list = Generic::parse_id_list($id_players);
        if (empty($id_list) || !is_numeric($id_club)) {
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($id_list), '?'));
        $sql = "UPDATE joueurs SET id_club = ? WHERE id IN ($placeholders)";
        $bindings = array(array('type' => 'i', 'value' => (int)$id_club));
        foreach ($id_list as $id) {
            $bindings[] = array('type' => 'i', 'value' => $id);
        }
        $this->sql_manager->execute($sql, $bindings);
        foreach ($id_list as $idPlayer) {
            $this->addActivity($this->getPlayerFullName($idPlayer) . " a ete ajoute au club " . $this->club->getClubName($id_club));
        }
        return true;
    }

    /**
     * Refuse l'ajout si l'effectif de l'équipe est gelé (issue #32).
     *
     * Point de passage unique : les trois actions REST qui rattachent un joueur
     * — `addPlayersToTeam`, `addPlayerToMyTeam`, `set_leader` — appellent toutes
     * `addPlayerToTeam`, et `add_to_team` y a été ramené pour qu'aucun chemin
     * ne contourne le contrôle.
     *
     * **L'administrateur n'est pas concerné.** Le verrou vise le responsable
     * d'équipe ; la commission garde la main pour corriger une saisie ou
     * accorder une dérogation, et son ajout reste tracé dans le journal
     * d'activité, donc vérifiable après coup.
     *
     * @return array|null le verrou franchi par un administrateur, s'il y en a
     *                    un — l'appelant s'en sert pour journaliser la
     *                    dérogation de façon distincte d'un ajout ordinaire.
     * @throws Exception si l'équipe a déjà signé la fiche d'un de ses matchs
     */
    private function assertSquadIsOpen($idTeam): ?array
    {
        $lock = $this->team->getSquadLock($idTeam);
        if ($lock === null) {
            return null;
        }
        if (UserManager::isAdmin()) {
            return $lock;
        }
        // 403 et non 500 : c'est un refus métier, pas une panne. Le routeur
        // rend le message tel quel au client (rest/action.php), et seul le 401
        // y déclenche une redirection vers la connexion.
        throw new Exception(sprintf(
            "L'effectif de cette équipe est figé depuis la signature de la fiche "
            . "du match %s (%s, %s) : aucun joueur ne peut plus y être ajouté. "
            . "Contactez la commission si un ajout est justifié.",
            $lock['code_match'],
            $lock['competition'],
            $lock['date_reception']
        ), 403);
    }

    /**
     * @throws Exception
     */
    public function addPlayerToTeam($idPlayer, $idTeam, bool $est_jouant = true)
    {
        if ($this->isPlayerInTeam($idPlayer, $idTeam)) {
            return true;
        }
        $lock = $this->assertSquadIsOpen($idTeam);
        // $idPlayer vient du client (actions du responsable d'équipe) — issue #270
        $sql = "INSERT joueur_equipe SET id_joueur = ?, id_equipe = ?, est_jouant = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idPlayer),
            array('type' => 'i', 'value' => $idTeam),
            array('type' => 'i', 'value' => $est_jouant ? 1 : 0),
        );
        $this->sql_manager->execute($sql, $bindings);
        // Un ajout dans un effectif deja fige ne peut venir que d'un
        // administrateur : on le journalise distinctement (issue #32). Sans ce
        // marqueur, une derogation serait indiscernable d'un ajout ordinaire, et
        // il faudrait la reconstituer par recoupement — c'est precisement ce que
        // fait, laborieusement, l'indicateur de l'issue #233.
        $this->addActivity(
            ($lock === null ? "Ajout de " : "Ajout DEROGATOIRE de ")
            . $this->getPlayerFullName($idPlayer)
            . " a l'equipe " . $this->team->getTeamName($idTeam)
            . ($est_jouant ? "" : " (membre non jouant)")
            . ($lock === null ? "" : " (effectif fige depuis le match " . $lock['code_match'] . ")")
        );
        return true;
    }

    /**
     * @throws Exception
     */
    public function getPlayersIdClub($idPlayer)
    {
        // valeur du client via addPlayerToMyTeam : liee (issue #355)
        $sql = "SELECT j.id_club
        FROM joueurs j
        WHERE j.id = ?";
        $results = $this->sql_manager->execute($sql, array(
            array('type' => 'i', 'value' => Generic::parse_id($idPlayer, 'identifiant de joueur')),
        ));
        return $results[0]['id_club'] ?? null;
    }

    /**
     * @throws Exception
     */
    /**
     * Le joueur enregistré doit-il rejoindre l'équipe courante de la session ?
     *
     * Demandé explicitement par l'espace responsable (`add_to_my_team` = 1) :
     * oui pour tout responsable d'équipe, administrateur compris. Sans
     * demande, on garde le comportement historique : oui pour un responsable
     * qui n'est pas administrateur, non pour un administrateur, dont les
     * modifications depuis l'administration (écran Joueurs, « Équipes /
     * comptes ») ne doivent pas remplir sa propre équipe (#407, #419).
     */
    private function adds_to_my_team(mixed $add_to_my_team): bool
    {
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        if ($add_to_my_team === null || $add_to_my_team === '') {
            return !UserManager::isAdmin();
        }
        return Generic::to_flag($add_to_my_team) === 1;
    }

    public function addPlayerToMyTeam($idPlayer)
    {
        // Pas de refus pour l'administrateur : responsable d'une équipe, il
        // la complète comme tout responsable (issue #419). C'est l'appelant
        // qui décide d'ajouter (`adds_to_my_team`), pas le rôle.
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $idTeam = $_SESSION['id_equipe'];
        if ($this->addPlayerToTeam($idPlayer, $idTeam) === false) {
            return false;
        }
        $idClubPlayer = $this->getPlayersIdClub($idPlayer);
        // Sans club, le joueur prend celui de l'équipe. Un club absent vaut
        // NULL en base : comparé à '0', le rattachement ne se faisait jamais.
        if (empty($idClubPlayer)) {
            $idClubMyTeam = $this->team->getMyTeamIdClub();
            if ($this->addPlayersToClub($idPlayer, $idClubMyTeam) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function getPlayers($query = null, $id_match = null)
    {
        $where = "1=1";
        if (!empty($query)) {
            // $where part dans get_players(), qui prend une clause toute faite :
            // on echappe le terme de recherche (issue #270).
            $safe_query = $this->sql_manager->escape($query);
            $where .= " AND j.full_name LIKE '%$safe_query%'";
        }
        // filter available match players by id_match (known teams)
        if (!empty($id_match)) {
            // interpolé deux fois plus bas : validé d'abord (issue #355)
            $id_match = Generic::parse_id($id_match, 'identifiant de match');
            // Les membres non jouants (issue #325) ne sont pas presentables.
            $where .= " AND j.id IN (
                            SELECT id_joueur
                            FROM joueur_equipe
                            WHERE est_jouant + 0 > 0
                            AND (id_equipe IN (
                                SELECT id_equipe_dom
                                FROM matches
                                WHERE id_match = $id_match)
                            OR id_equipe IN (
                                SELECT id_equipe_ext
                                FROM matches
                                WHERE id_match = $id_match))
                            )";
        }
        return $this->get_players($where, "j.club IS NULL, j.club, j.nom");
    }

    /**
     * @throws Exception
     */
    public function getPlayersPdf($idTeam, $doHideInactivePlayers = false)
    {
        if ($idTeam === NULL) {
            return false;
        }
        // `get_players()` prend une clause toute faite : on valide avant de
        // composer — `teamSheetPdf.php` passe `$_GET['id']` (issue #355).
        $idTeam = Generic::parse_id($idTeam, "identifiant d'équipe");
        if (!$this->team->isTeamSheetAllowedForUser($idTeam)) {
            throw new Exception("Vous n'avez pas la permission de consulter cette équipe !");
        }
        // La fiche d'equipe est la liste des licencies presentables en match :
        // les membres non jouants (issue #325) n'y figurent pas, meme quand ils
        // sont responsables de l'equipe.
        $players = $this->get_players("j.id IN
        (
            SELECT id_joueur
            FROM joueur_equipe
            WHERE id_equipe = $idTeam
              AND est_jouant + 0 > 0
        )");
        foreach ($players as $index => $player) {
            $players[$index]['is_captain'] = empty($player['id_captain']) ? 0 : in_array($idTeam, explode(',', $player['id_captain']));
            $players[$index]['is_vice_leader'] = empty($player['id_vl']) ? 0 : in_array($idTeam, explode(',', $player['id_vl']));
            $players[$index]['is_leader'] = empty($player['id_l']) ? 0 : in_array($idTeam, explode(',', $player['id_l']));
        }
        return $players;
    }

    /**
     * @throws Exception
     */
    public function getPlayersFromTeam($id_equipe): array|int|string|null
    {
        $sql = "SELECT
        j.full_name,
        j.prenom, 
        j.nom, 
        j.telephone, 
        j.email, 
        j.num_licence, 
        j.path_photo,
        j.sexe, 
        j.departement_affiliation, 
        j.est_actif, 
        j.id_club, 
        j.telephone2, 
        j.email2, 
        je.is_captain,
        je.is_vice_leader,
        je.is_leader,
        je.est_jouant + 0 AS est_jouant,
        j.id,
        j.date_homologation
        FROM joueur_equipe je
        LEFT JOIN players_view j ON j.id=je.id_joueur
        WHERE id_equipe = ?";
        // valeur du client via getLivePlayersFromTeam : liee (issue #355)
        return $this->sql_manager->execute($sql, array(
            array('type' => 'i', 'value' => Generic::parse_id($id_equipe, "identifiant d'équipe")),
        ));
    }

    /**
     * Version "live score" : ne renvoie que l'id et le nom complet des joueurs
     * d'une équipe (pas de PII type email/téléphone). Utilisé par la page
     * live.html (marquage en direct) — voir issue #228.
     *
     * @param $id_equipe
     * @return array<int, array{id: int, full_name: string}>
     */
    public function getLivePlayersFromTeam($id_equipe): array
    {
        $players = $this->getPlayersFromTeam($id_equipe);
        if (!is_array($players)) {
            return array();
        }
        // Les membres non jouants (issue #325) ne sont pas marquables : ils
        // sont rattaches a l'equipe pour la piloter, pas pour jouer.
        $playing = array_filter($players, static function ($player) {
            return (int)($player['est_jouant'] ?? 1) === 1;
        });
        return array_values(array_map(static function ($player) {
            return array(
                'id' => (int)$player['id'],
                'full_name' => $player['full_name'],
            );
        }, $playing));
    }

    /**
     * @throws Exception
     */
    public function set_leader($ids, $id_team = null, $est_jouant = null)
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        // sans cela, un responsable agissait sur l'équipe d'un autre club (issue #356)
        (new UserManager())->assertCanManageTeam($id_team);
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
            $player = $this->get_player($id_player);
            if (empty($player['email']) || empty($player['telephone'])) {
                throw new Exception("Ce joueur doit avoir une adresse email et un numéro de téléphone pour devenir responsable d'équipe !");
            }
            // On peut nommer responsable quelqu'un qui n'est pas encore dans
            // l'equipe : c'est la que se decide s'il y joue (issue #325). Le
            // cas type est l'homme qui pilote une equipe feminine — il est
            // ajoute non jouant. Le drapeau s'applique aussi a un membre deja
            // present : c'est le seul endroit ou un administrateur le regle.
            // `$est_jouant` a null veut dire « ne touche pas » : les appels qui
            // ne s'en preoccupent pas ne doivent pas rebasculer en jouant un
            // membre declare non jouant.
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                $this->addPlayerToTeam($id_player, $id_team, $est_jouant === null || (int)$est_jouant === 1);
            } elseif ($est_jouant !== null) {
                $this->applyPlayingFlag($id_player, $id_team, (int)$est_jouant === 1);
            }
            $sql = "UPDATE joueur_equipe SET is_leader = 0 WHERE id_equipe = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
            );
            $this->sql_manager->execute($sql, $bindings);
            $sql = "UPDATE joueur_equipe SET is_leader = 1 WHERE id_equipe = ? AND id_joueur = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
                array('type' => 'i', 'value' => $id_player),
            );
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity("L'equipe " . $this->team->getTeamName($id_team) . " a un nouveau responsable : " . $this->getPlayerFullName($id_player));
        }
    }

    /**
     * @throws Exception
     */
    function set_captain($ids, $id_team = null)
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        // sans cela, un responsable agissait sur l'équipe d'un autre club (issue #356)
        (new UserManager())->assertCanManageTeam($id_team);
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur n'est pas dans l'équipe !");
            }
            // Un capitaine joue : le capitanat n'a pas de sens sur une
            // appartenance non jouante (issue #325).
            if (!$this->isPlayingInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur ne joue pas dans cette équipe : il ne peut pas en être le capitaine !");
            }
            $sql = "UPDATE joueur_equipe SET is_captain = 0 WHERE id_equipe = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
            );
            $this->sql_manager->execute($sql, $bindings);
            $sql = "UPDATE joueur_equipe SET is_captain = 1 WHERE id_equipe = ? AND id_joueur = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
                array('type' => 'i', 'value' => $id_player),
            );
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity("L'equipe " . $this->team->getTeamName($id_team) . " a un nouveau capitaine : " . $this->getPlayerFullName($id_player));
        }
    }

    /**
     * Déclare qu'un membre joue — ou ne joue pas — dans l'équipe (issue #325).
     *
     * Une personne peut appartenir à une équipe sans y jouer : le cas type est
     * un joueur du championnat masculin qui est aussi responsable d'une équipe
     * féminine. Il doit être rattaché à l'équipe pour la piloter, mais il n'en
     * est pas un membre jouant — il ne compte pas dans l'effectif, n'a pas
     * besoin de licence à ce titre, et n'est pas présentable en match.
     *
     * Un responsable d'équipe n'agit que sur la sienne : contrairement à
     * `set_captain` et `set_leader`, `$id_team` n'est pris en compte que pour
     * un administrateur.
     *
     * @throws Exception
     */
    public function set_playing($ids, $id_team = null, $est_jouant = 1): void
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        // Les rôles se cumulent (issue #245) : l'administrateur est très
        // souvent AUSSI responsable d'une équipe, et il agit alors depuis
        // l'écran effectif, qui n'envoie pas d'`id_team` — l'équipe est celle
        // de la session. Ne retomber sur la session que pour les non-admins
        // renvoyait « Aucune équipe n'est désignée ! » à tout administrateur
        // utilisant cet écran.
        //
        // Le resserrage reste, et c'est lui qui compte : un responsable
        // d'équipe n'agit QUE sur la sienne, même s'il poste un autre
        // `id_team`.
        @session_start();
        if (!UserManager::isAdmin() || empty($id_team)) {
            $id_team = $_SESSION['id_equipe'] ?? null;
        }
        if (empty($id_team)) {
            throw new Exception("Aucune équipe n'est désignée !");
        }
        if (is_string($ids)) {
            $ids = array($ids);
        }
        $plays = (int)$est_jouant === 1;
        foreach ($ids as $id_player) {
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur n'est pas dans l'équipe !");
            }
            $this->applyPlayingFlag($id_player, $id_team, $plays);
        }
    }

    /**
     * Pose `est_jouant` sur une appartenance existante, et le journalise
     * (issue #325). Partagé par `set_playing` et `set_leader` : nommer
     * responsable quelqu'un qui ne joue pas dans l'équipe est le cas d'usage
     * principal du drapeau, il doit donc se régler au même endroit.
     *
     * @throws Exception si le membre est capitaine de l'équipe — un capitaine
     *                   joue.
     */
    private function applyPlayingFlag($id_player, $id_team, bool $plays): void
    {
        if ($this->isPlayingInTeam($id_player, $id_team) === $plays) {
            return;
        }
        if (!$plays && $this->isCaptainOfTeam($id_player, $id_team)) {
            throw new Exception("Le capitaine joue : nommez un autre capitaine avant de déclarer celui-ci non jouant !");
        }
        $sql = "UPDATE joueur_equipe SET est_jouant = ? WHERE id_equipe = ? AND id_joueur = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $plays ? 1 : 0),
            array('type' => 'i', 'value' => (int)$id_team),
            array('type' => 'i', 'value' => (int)$id_player),
        );
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity(
            $this->getPlayerFullName($id_player)
            . ($plays ? " joue desormais dans l'equipe " : " ne joue pas dans l'equipe ")
            . $this->team->getTeamName($id_team)
        );
    }

    /**
     * Cette appartenance est-elle jouante ? (issue #325)
     *
     * @throws Exception
     */
    public function isPlayingInTeam($idPlayer, $idTeam): bool
    {
        $sql = "SELECT est_jouant + 0 AS est_jouant
                FROM joueur_equipe
                WHERE id_joueur = ? AND id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => (int)$idPlayer),
            array('type' => 'i', 'value' => (int)$idTeam),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) === 0) {
            return false;
        }
        return (int)$results[0]['est_jouant'] === 1;
    }

    /**
     * @throws Exception
     */
    private function isCaptainOfTeam($idPlayer, $idTeam): bool
    {
        $sql = "SELECT COUNT(*) AS cnt
                FROM joueur_equipe
                WHERE id_joueur = ? AND id_equipe = ? AND is_captain + 0 > 0";
        $bindings = array(
            array('type' => 'i', 'value' => (int)$idPlayer),
            array('type' => 'i', 'value' => (int)$idTeam),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return (int)$results[0]['cnt'] > 0;
    }

    /**
     * @throws Exception
     */
    function set_vice_leader($ids, $id_team = null)
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        // sans cela, un responsable agissait sur l'équipe d'un autre club (issue #356)
        (new UserManager())->assertCanManageTeam($id_team);
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
            $player = $this->get_player($id_player);
            if (empty($player['email']) || empty($player['telephone'])) {
                throw new Exception("Ce joueur doit avoir une adresse email et un numéro de téléphone pour devenir suppléant !");
            }
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur n'est pas dans l'équipe !");
            }
            $sql = "UPDATE joueur_equipe SET is_vice_leader = 0 WHERE id_equipe = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
            );
            $this->sql_manager->execute($sql, $bindings);
            $sql = "UPDATE joueur_equipe SET is_vice_leader = 1 WHERE id_equipe = ? AND id_joueur = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_team),
                array('type' => 'i', 'value' => $id_player),
            );
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity("L'equipe " . $this->team->getTeamName($id_team) . " a un nouveau suppleant : " . $this->getPlayerFullName($id_player));
        }
    }

    /**
     * @throws Exception
     */
    public function remove_from_team($ids, $id_team = null)
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        // sans cela, un responsable agissait sur l'équipe d'un autre club (issue #356)
        (new UserManager())->assertCanManageTeam($id_team);
        foreach ($ids as $id_player) {
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur n'est pas dans l'équipe !");
            }
            $sql = "DELETE FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_player),
                array('type' => 'i', 'value' => $id_team),
            );
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity($this->getPlayerFullName($id_player) . " a ete supprime de l'equipe " . $this->team->getTeamName($id_team));
        }
    }

    /**
     * @param $id_team
     * @param $ids
     * @return void
     * @throws Exception
     */
    public function add_to_team($ids, $id_team = null): void
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        // sans cela, un responsable agissait sur l'équipe d'un autre club (issue #356)
        (new UserManager())->assertCanManageTeam($id_team);
        // Delegue a addPlayerToTeam plutot que de redupliquer l'INSERT : cette
        // methode en portait une copie, ce qui aurait laisse un chemin
        // d'ajout hors du controle de verrouillage (issue #32).
        foreach ($ids as $id_player) {
            $this->addPlayerToTeam($id_player, $id_team);
        }
    }

    /**
     * @throws Exception
     */
    public function is_player_in_team($idPlayer, $idTeam): bool
    {
        $sql = "SELECT * FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idPlayer),
            array('type' => 'i', 'value' => $idTeam),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) > 0;
    }

    /**
     * @param mixed $licence
     * @return array{status: string, photo: bool} `created` ou `updated`, et
     *         si la licence portait une photo (issue #394)
     * @throws Exception
     */
    public function search_player_and_save_from_licence(mixed $licence, ?bool $add_to_my_team = null): array
    {
        $add_to_my_team ??= $this->adds_to_my_team(null);
        // Club de la licence, reconnu à son numéro d'affiliation : le nom
        // imprimé peut différer de celui en base (issue #404).
        $licence_club = $this->licence_club_for_importer($licence);
        $current_player = $this->find_player_for_licence($licence, (int)$licence_club['id']);

        // Gérer la photo si présente. Absente, rien ne bloque : elle n'est
        // exigée qu'à l'ajout sur une feuille de match (#343).
        $idPhoto = null;
        if (isset($licence['photo']) && $licence['photo'] !== null) {
            $idPhoto = $this->savePlayerPhotoFromLicence($licence);
        }

        // s'il n'existe pas, le créer
        if (empty($current_player)) {
            [$nom, $prenom] = self::split_licence_name($licence['last_first_name']);
            $newPlayerId = $this->save(array(
                'prenom' => $prenom,
                'nom' => $nom,
                'num_licence' => $licence['licence_number'],
                'sexe' => $licence['sexe'],
                'departement_affiliation' => $licence['departement'],
                'id_club' => $licence_club['id'],
                'date_homologation' => $licence['homologation_date'],
                'add_to_my_team' => $add_to_my_team,
            ));

            // Lier la photo au joueur nouvellement créé
            if ($idPhoto !== null && $newPlayerId) {
                $this->linkPlayerToPhoto($newPlayerId, $idPhoto);
            }
        } else {
            // s'il existe, le mettre à jour. Un club différent de celui de sa
            // licence est un changement de club : la licence fait foi, et le
            // journal d'activité le trace (#404).
            $this->save(array(
                'id' => $current_player['id'],
                'num_licence' => $licence['licence_number'],
                'sexe' => $licence['sexe'],
                'departement_affiliation' => $licence['departement'],
                'id_club' => $licence_club['id'],
                'date_homologation' => $licence['homologation_date'],
                'add_to_my_team' => $add_to_my_team,
            ));

            // Lier la photo au joueur existant (mettre à jour si nouvelle photo)
            if ($idPhoto !== null) {
                $this->linkPlayerToPhoto($current_player['id'], $idPhoto);
            }
        }
        return array('status' => empty($current_player) ? 'created' : 'updated', 'photo' => $idPhoto !== null);
    }

    /**
     * Club d'une licence importée (issue #404), reconnu à son numéro
     * d'affiliation (`N°…` imprimé sur la licence), jamais à son nom.
     *
     * L'administrateur importe tout : un club inconnu est créé, comme avant.
     * Un responsable n'importe que les licences de ses clubs (ceux de son
     * compte, et celui de son équipe courante). Si l'un d'eux n'a pas de
     * numéro d'affiliation en base, la comparaison est impossible : une
     * licence d'un club inconnu lui est attribuée, s'il est le seul dans ce
     * cas. Une licence d'un autre club connu est refusée.
     *
     * @throws Exception (409) licence d'un autre club
     */
    private function licence_club_for_importer(array $licence): array
    {
        $licence_club = $this->club->get_one("affiliation_number = ?",
            array(array('type' => 's', 'value' => $licence['licence_club'])));
        if (UserManager::isAdmin()) {
            if (empty($licence_club)) {
                $newClubId = $this->club->save(array(
                    'nom' => $licence['club'],
                    'affiliation_number' => $licence['licence_club'],
                ));
                $licence_club = array('id' => $newClubId);
            }
            return $licence_club;
        }
        $my_clubs = $this->importer_clubs();
        foreach ($my_clubs as $club) {
            if (!empty($licence_club) && (int)$club['id'] === (int)$licence_club['id']) {
                return $licence_club;
            }
        }
        $without_number = array_values(array_filter($my_clubs,
            static fn($club) => trim((string)$club['affiliation_number']) === ''));
        if (empty($licence_club) && count($without_number) === 1) {
            return $without_number[0];
        }
        throw new Exception("licence du club n° " . $licence['licence_club']
            . (empty($licence_club['nom']) ? '' : ' (' . $licence_club['nom'] . ')')
            . ", qui n'est pas le vôtre", 409);
    }

    /**
     * Clubs au nom desquels le compte connecté importe des licences : ses
     * clubs de responsable, et le club de son équipe courante.
     *
     * @return array<int, array{id: int|string, affiliation_number: ?string}>
     * @throws Exception
     */
    private function importer_clubs(): array
    {
        $ids = array();
        if (UserManager::isClubLeader()) {
            $ids = $this->club->getMyClubIds();
        }
        if (UserManager::isTeamLeader() && !empty($_SESSION['id_equipe'])) {
            $id_club = $this->team->getIdClubFromIdTeam($_SESSION['id_equipe']);
            if (!empty($id_club)) {
                $ids[] = (int)$id_club;
            }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            throw new Exception("aucun club n'est rattaché à votre compte", 409);
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $this->sql_manager->execute(
            "SELECT id, nom, affiliation_number FROM clubs WHERE id IN ($placeholders)",
            array_map(static fn($id) => array('type' => 'i', 'value' => $id), $ids));
    }

    /**
     * Joueur que désigne une licence importée (issue #404).
     *
     * D'abord par son numéro de licence. Sinon par son nom, seulement s'il
     * n'y a qu'un seul joueur de ce nom, et seulement s'il n'a pas encore de
     * numéro de licence ou qu'il est du club de la licence : on n'écrase pas
     * la licence d'un homonyme. Chercher les deux à la fois renvoyait deux
     * joueurs quand licence et nom en désignaient deux différents, et l'import
     * s'arrêtait en erreur au milieu du fichier.
     *
     * @return array|null le joueur, ou null s'il est à créer
     * @throws Exception (409) cas à trancher par la commission
     */
    private function find_player_for_licence(array $licence, int $id_licence_club): ?array
    {
        $by_licence = $this->get("j.departement_affiliation = ? AND j.num_licence = ?", array(
            array('type' => 'i', 'value' => intval($licence['departement'])),
            array('type' => 's', 'value' => self::normalize_licence_number($licence['licence_number'])),
        ));
        if (count($by_licence) === 1) {
            return $by_licence[0];
        }
        if (count($by_licence) > 1) {
            throw new Exception("plusieurs joueurs portent la licence " . $licence['licence_number'], 409);
        }
        $by_name = $this->get("CONCAT(UPPER(j.nom), ' ', UPPER(j.prenom)) = UPPER(?)", array(
            array('type' => 's', 'value' => $licence['last_first_name']),
        ));
        if (count($by_name) === 0) {
            return null;
        }
        if (count($by_name) > 1) {
            throw new Exception("plusieurs joueurs portent ce nom, à rapprocher par la commission", 409);
        }
        $player = $by_name[0];
        if (trim((string)$player['num_licence']) !== '' && (int)$player['id_club'] !== $id_licence_club) {
            throw new Exception("un homonyme d'un autre club a déjà la licence " . $player['num_licence']
                . ", à rapprocher par la commission", 409);
        }
        return $player;
    }

    /**
     * « NOM COMPOSÉ Prénom » d'une licence en [nom, prénom] (issue #404) :
     * le nom, ce sont les mots en majuscules en tête, le prénom le reste. Au
     * moins un mot de chaque côté : un prénom écrit en majuscules garde le
     * dernier mot.
     *
     * @return array{0: string, 1: string}
     */
    public static function split_licence_name(string $last_first_name): array
    {
        $words = preg_split('/\s+/u', trim($last_first_name));
        $last = 0;
        while ($last < count($words) - 1
            && preg_match('/\p{L}/u', $words[$last])
            && mb_strtoupper($words[$last], 'UTF-8') === $words[$last]) {
            $last++;
        }
        $last = max(1, $last);
        return array(implode(' ', array_slice($words, 0, $last)), implode(' ', array_slice($words, $last)));
    }

    /**
     * Numéro de licence tel qu'on le stocke (issue #404) : sans espace ni
     * tabulation, et sans le préfixe de département que la licence imprime
     * (`013_DY10000187`), puisque le département a sa propre colonne.
     */
    public static function normalize_licence_number(?string $licence_number): ?string
    {
        if ($licence_number === null) {
            return null;
        }
        $licence_number = preg_replace('/\s+/u', '', $licence_number);
        return preg_replace('/^0?\d{2,3}_/', '', $licence_number);
    }

    /**
     * Sauvegarde la photo d'un joueur depuis les données de licence
     * @param array $licence Données de licence avec 'photo', 'licence_number', 'departement'
     * @return int|null L'ID de la photo dans la table photos, ou null si échec
     * @throws Exception
     */
    private function savePlayerPhotoFromLicence(array $licence): ?int
    {
        if (!isset($licence['photo']) || $licence['photo'] === null) {
            return null;
        }
        
        // Créer le nom de fichier basé sur le numéro de licence
        $licenceNumber = $licence['licence_number'];
        $departement = $licence['departement'];
        $uploaddir = __DIR__ . '/../players_pics/';
        
        // S'assurer que le dossier existe
        if (!is_dir($uploaddir)) {
            mkdir($uploaddir, 0755, true);
        }
        
        // Format: 0[departement]_[licence_number].jpg (ex: 013_96742776.jpg)
        $filename = sprintf('%02d_%s.jpg', $departement, $licenceNumber);
        $uploadfile = $uploaddir . $filename;
        $relativePath = 'players_pics/' . $filename;
        
        // Sauvegarder le contenu JPEG sur disque
        if (file_put_contents($uploadfile, $licence['photo']) === false) {
            error_log("Échec de sauvegarde de la photo du joueur: $uploadfile");
            return null;
        }
        
        // Insérer dans la table photos et récupérer l'ID
        $idPhoto = $this->photo->insertPhoto($relativePath);
        
        // Créer une version basse résolution pour les performances web
        $this->createLowResPhoto($relativePath);
        
        return $idPhoto;
    }
    
    /**
     * Créer une version basse résolution de la photo pour l'affichage web
     * @param string $path_photo Chemin relatif vers la photo (ex: 'players_pics/013_96742776.jpg')
     * @return void
     */
    private function createLowResPhoto(string $path_photo): void
    {
        try {
            $compression_rate = 50;
            $source_file = __DIR__ . '/../' . $path_photo;
            $low_photos_folder = __DIR__ . '/../players_pics_low/';
            
            if (!is_dir($low_photos_folder)) {
                mkdir($low_photos_folder, 0755, true);
            }
            
            $filename = basename($path_photo);
            $destination_file = $low_photos_folder . $filename;
            
            // Charger l'image originale
            $img = imagecreatefromjpeg($source_file);
            if ($img === false) {
                return;
            }
            
            // Sauvegarder la version compressée
            imagejpeg($img, $destination_file, $compression_rate);
            imagedestroy($img);
            
        } catch (Exception $e) {
            // Échec silencieux - la photo basse résolution est optionnelle
            error_log("Échec de création de la photo basse résolution pour $path_photo: " . $e->getMessage());
        }
    }

    /**
     * Photo enregistrée en base (issue #343) : ce qui autorise à jouer.
     */
    public static function has_photo_path(?string $path_photo): bool
    {
        return trim((string)$path_photo) !== '';
    }

    /**
     * @param int|array|string|null $results
     * @return array|int|string|null
     */
    /**
     * Substitue une image de repli quand la photo pointée par la base n'existe
     * pas sur le disque.
     *
     * PERFORMANCE (issue #295) : cette méthode faisait un `file_exists()` **par
     * joueur**, soit 3 651 accès disque pour un appel à `getPlayers`. C'était,
     * de loin, le poste le plus coûteux de l'endpoint — 3,98 s sur 4,46, la
     * requête SQL n'en prenant que 0,87. Elle lit désormais **une fois** chaque
     * répertoire concerné, puis cherche en mémoire : 3,306 s -> 0,005 s en
     * local, où le montage Windows->VM amplifie chaque accès d'un facteur 100.
     * Le gain est plus modeste en production, mais une lecture de répertoire
     * bat 3 651 `stat` sur n'importe quel système de fichiers.
     *
     * L'index est reconstruit **à chaque appel**, volontairement : le mettre en
     * cache statique ferait apparaître comme manquante une photo téléversée
     * puis relue dans la même requête. Il est déjà 619 fois moins cher.
     */
    public static function adjust_photo_path_from_results(int|array|string|null $results): string|array|int|null
    {
        $existants = array();
        foreach ($results as $index => $result) {
            // Photo enregistrée en base (issue #343) : c'est elle qui autorise à
            // jouer, indépendamment de la présence du fichier sur ce serveur —
            // l'image de remplacement ci-dessous n'est qu'un affichage.
            $results[$index]['has_photo'] = self::has_photo_path($result['path_photo'] ?? null) ? 1 : 0;
            $results[$index]['path_photo'] = Generic::accentedToNonAccented($result['path_photo']);
            $results[$index]['path_photo_low'] = Generic::accentedToNonAccented($result['path_photo_low']);
            if (($results[$index]['path_photo'] == '')
                || !self::photoFileExists($results[$index]['path_photo'], $existants)) {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/MaleMissingPhoto.png';
                        $results[$index]['path_photo_low'] = 'images/MaleMissingPhoto.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/FemaleMissingPhoto.png';
                        $results[$index]['path_photo_low'] = 'images/FemaleMissingPhoto.png';
                        break;
                    default:
                        break;
                }
                continue;
            }
            // La vignette est DEDUITE du chemin plein par un REPLACE dans
            // `players_view` : rien ne garantit que le fichier existe, et il
            // manque effectivement pour une bonne part des joueurs — seules
            // les photos televersees depuis l'application passent par
            // `generateLowPhoto()`. Le defaut est ancien, mais il est reste
            // invisible tant qu'aucun ecran n'affichait `path_photo_low` : la
            // grille des joueurs le fait depuis #295, d'ou une volee de 404.
            //
            // On se rabat sur la photo pleine, qui elle existe. Le surcout est
            // negligeable : ces photos de licence pesent 12 Ko en moyenne,
            // 24 Ko au maximum sur un echantillon de production. Generer les
            // milliers de vignettes manquantes ne rapporterait donc presque
            // rien.
            if (!self::photoFileExists($results[$index]['path_photo_low'], $existants)) {
                $results[$index]['path_photo_low'] = $results[$index]['path_photo'];
            }
        }
        return $results;
    }

    /**
     * Le fichier existe-t-il, d'après un index de répertoire constitué à la
     * demande ?
     *
     * `$index` est passé par référence et grandit au fil des appels : le
     * premier chemin rencontré dans `players_pics` déclenche la lecture de ce
     * répertoire, le premier dans `teams_pics` la sienne. Les chemins stockés
     * ne comportent jamais de sous-répertoire — vérifié sur les 4 573 lignes de
     * `photos`, toutes avec exactement un `/`.
     *
     * @param array $index répertoire -> ensemble des noms de fichiers présents
     */
    private static function photoFileExists(string $path, array &$index): bool
    {
        $dossier = dirname($path);
        $fichier = basename($path);
        if (!array_key_exists($dossier, $index)) {
            $absolu = __DIR__ . '/../' . $dossier;
            $entrees = is_dir($absolu) ? scandir($absolu) : false;
            $index[$dossier] = $entrees === false ? array() : array_flip($entrees);
        }
        return isset($index[$dossier][$fichier]);
    }

    public function generateLowPhoto(mixed $path_photo)
    {
        $compression_rate = 50;
        $source_file = __DIR__ . '/../' . $path_photo;
        $low_photos_folder = __DIR__ . '/../players_pics_low/';
        if (!is_dir($low_photos_folder)) {
            mkdir($low_photos_folder, 0777, true);
        }
        if (in_array(pathinfo($source_file, PATHINFO_EXTENSION), array('jpg', 'jpeg', 'png', 'gif'))) {
            $image = @imagecreatefromstring(file_get_contents($source_file));
            // apply quality
            @imagejpeg($image, $low_photos_folder . basename($source_file), $compression_rate);
            // flush memory
            @imagedestroy($image);
        }
    }

    /**
     * @throws Exception
     */
    public function createLeaderAccount($idPlayer): void
    {
        @session_start();
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable d'équipe peut faire ça !");
        }
        $id_team = $_SESSION['id_equipe'];
        $player = $this->get_player($idPlayer);
        if (empty($player['email'])) {
            throw new Exception("Ce joueur n'a pas d'adresse email !");
        }
        $sql = "SELECT is_leader, is_vice_leader 
                FROM joueur_equipe 
                WHERE id_joueur = ? AND id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $idPlayer),
            array('type' => 'i', 'value' => $id_team),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (empty($results)) {
            throw new Exception("Ce joueur n'appartient pas à votre équipe !");
        }
        $playerTeam = $results[0];
        if (!$playerTeam['is_leader'] && !$playerTeam['is_vice_leader']) {
            throw new Exception("Ce joueur n'est ni responsable d'équipe ni suppléant !");
        }
        $this->userManager->create_or_update_leader_account($player['email'], $id_team);
        $this->addActivity("Création du compte responsable pour " . $player['prenom'] . " " . $player['nom']);
    }
}
