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
        if (UserManager::isAdmin()) {
            throw new Exception("Un administrateur ne peut pas faire ça !");
        }
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
        foreach ($players as $index => $player) {
            $players[$index]['is_captain'] = empty($player['id_captain']) ? 0 : in_array($id_team, explode(',', $player['id_captain']));
            $players[$index]['is_vice_leader'] = empty($player['id_vl']) ? 0 : in_array($id_team, explode(',', $player['id_vl']));
            $players[$index]['is_leader'] = empty($player['id_l']) ? 0 : in_array($id_team, explode(',', $player['id_l']));
        }
        return $players;
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
        $dirtyFields = null): array|int|string|null
    {
        $parameters = array(
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
            'dirtyFields' => $dirtyFields,
        );
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
        WHERE j.id = $idPlayer";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['player_full_name'];
    }

    /**
     * @throws Exception
     */
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
     * @throws Exception
     */
    public function savePlayer(
        $dirtyFields,
        $id,
        $id_team,
        $prenom,
        $nom,
        $num_licence,
        $date_homologation,
        $sexe,
        $departement_affiliation,
        $id_club,
        $est_responsable_club,
        $telephone,
        $email,
        $telephone2,
        $email2,
    )
    {
        $inputs = array(
            'dirtyFields' => $dirtyFields,
            'id' => $id,
            'id_team' => $id_team,
            'prenom' => $prenom,
            'nom' => $nom,
            'num_licence' => $num_licence,
            'date_homologation' => $date_homologation,
            'sexe' => $sexe,
            'departement_affiliation' => $departement_affiliation,
            'id_club' => $id_club,
            'est_responsable_club' => $est_responsable_club,
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
                'dirtyFields'))) {
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
                case 'est_responsable_club':
                    $val = ($value === 'on' || $value === 1 || $value === '1') ? 1 : 0;
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
                    );
                    $sql .= "$key = ?,";
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
        $newId = $this->sql_manager->execute($sql, $bindings);
        if (UserManager::isTeamLeader()) {
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
            $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
            if ($dirtyFields) {
                $fieldsArray = explode(',', $dirtyFields);
                foreach ($fieldsArray as $fieldName) {
                    $fieldValue = filter_input(INPUT_POST, $fieldName);
                    $firstName = $inputs['prenom'];
                    $name = $inputs['nom'];
                    $comment = "$firstName $name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                    $this->addActivity($comment);
                }
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
        $sql = "SELECT COUNT(*) AS cnt FROM joueurs WHERE num_licence = '$licenceNumber'";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function update_from_licence_file(): void
    {
        if (empty($_FILES['licences']['name'])) {
            return;
        }
        set_time_limit(60);
        $licences = $this->files->get_licences_data($_FILES['licences']['tmp_name']);
        foreach ($licences as $licence) {
            $this->search_player_and_save_from_licence($licence);
        }
    }

    public function uploadPhoto($id, $nom, $prenom)
    {
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
        $sql = "UPDATE joueurs j SET j.id_photo = $idPhoto WHERE id = $idPlayer";
        $this->sql_manager->execute($sql);
    }

    /**
     * @param $idPlayer
     * @return void
     * @throws Exception
     */
    function removePlayerFromMyTeam($idPlayer)
    {
        if (UserManager::isAdmin()) {
            throw new Exception("Un profil admin ne peut pas faire cette action !");
        }
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
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_joueur = $idPlayer AND id_equipe = $idTeam";
        $results = $this->sql_manager->execute($sql);
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
    public function addPlayersToTeam($id_players, $id_team, $dirtyFields = null)
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
    public function addPlayersToClub($id_players, $id_club, $dirtyFields = null)
    {
        if (!UserManager::isAdmin()) {
            if (!UserManager::isTeamLeader()) {
                return false;
            }
        }
        $sql = "UPDATE joueurs SET id_club = $id_club WHERE id IN ($id_players)";
        $this->sql_manager->execute($sql);
        foreach (explode(',', $id_players) as $idPlayer) {
            $this->addActivity($this->getPlayerFullName($idPlayer) . " a ete ajoute au club " . $this->club->getClubName($id_club));
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function addPlayerToTeam($idPlayer, $idTeam)
    {
        if ($this->isPlayerInTeam($idPlayer, $idTeam)) {
            return true;
        }
        $sql = "INSERT joueur_equipe SET id_joueur = $idPlayer, id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
        $this->addActivity("Ajout de " . $this->getPlayerFullName($idPlayer) . " a l'equipe " . $this->team->getTeamName($idTeam));
        return true;
    }

    /**
     * @throws Exception
     */
    public function getPlayersIdClub($idPlayer)
    {
        $sql = "SELECT j.id_club
        FROM joueurs j
        WHERE j.id = $idPlayer";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['id_club'];
    }

    /**
     * @throws Exception
     */
    public function addPlayerToMyTeam($idPlayer)
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $idTeam = $_SESSION['id_equipe'];
        if ($this->addPlayerToTeam($idPlayer, $idTeam) === false) {
            return false;
        }
        $idClubPlayer = $this->getPlayersIdClub($idPlayer);
        if ($idClubPlayer === '0') {
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
            $where .= " AND j.full_name LIKE '%$query%'";
        }
        // filter available match players by id_match (known teams)
        if (!empty($id_match)) {
            $where .= " AND j.id IN (
                            SELECT id_joueur 
                            FROM joueur_equipe 
                            WHERE id_equipe IN (
                                SELECT id_equipe_dom 
                                FROM matches 
                                WHERE id_match = $id_match)
                            OR id_equipe IN (
                                SELECT id_equipe_ext 
                                FROM matches 
                                WHERE id_match = $id_match)
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
        if (!$this->team->isTeamSheetAllowedForUser($idTeam)) {
            throw new Exception("Vous n'avez pas la permission de consulter cette équipe !");
        }
        $players = $this->get_players("j.id IN 
        (
            SELECT id_joueur 
            FROM joueur_equipe 
            WHERE id_equipe = $idTeam
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
        j.est_responsable_club, 
        je.is_captain, 
        je.is_vice_leader, 
        je.is_leader, 
        j.id, 
        j.date_homologation 
        FROM joueur_equipe je
        LEFT JOIN players_view j ON j.id=je.id_joueur
        WHERE id_equipe = $id_equipe";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function set_leader($ids, $id_team = null)
    {
        if (!UserManager::isAdmin() && !UserManager::isTeamLeader()) {
            throw new Exception("Cette action n'est pas autorisée !");
        }
        if (empty($id_team)) {
            @session_start();
            $id_team = $_SESSION['id_equipe'];
        }
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                $this->addPlayerToTeam($id_player, $id_team);
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
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
            if (!$this->isPlayerInTeam($id_player, $id_team)) {
                throw new Exception("Ce joueur n'est pas dans l'équipe !");
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
        if (is_string($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id_player) {
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
        foreach ($ids as $id_player) {
            if ($this->isPlayerInTeam($id_player, $id_team)) {
                continue;
            }
            $sql = "INSERT joueur_equipe SET id_joueur = ?, id_equipe = ?";
            $bindings = array(
                array('type' => 'i', 'value' => $id_player),
                array('type' => 'i', 'value' => $id_team),
            );
            $this->sql_manager->execute($sql, $bindings);
            $this->addActivity("Ajout de " . $this->getPlayerFullName($id_player) . " a l'equipe " . $this->team->getTeamName($id_team));
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

    public function updateMyTeamLeader($idPlayer)
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $idTeam = $_SESSION['id_equipe'];
        if (!$this->isPlayerInTeam($idPlayer, $idTeam)) {
            return false;
        }
        $sql = "UPDATE joueur_equipe SET is_leader = 0 WHERE id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
        $sql = "UPDATE joueur_equipe SET is_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
        $this->sql_manager->execute($sql);
        $this->addActivity("L'equipe " . $this->team->getTeamName($idTeam) . " a un nouveau responsable : " . $this->getPlayerFullName($idPlayer));
        return true;
    }

    /**
     * @throws Exception
     */
    public function updateMyTeamCaptain($idPlayer)
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $idTeam = $_SESSION['id_equipe'];
        if (!$this->isPlayerInTeam($idPlayer, $idTeam)) {
            return false;
        }
        $sql = "UPDATE joueur_equipe SET is_captain = 0 WHERE id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
        $sql = "UPDATE joueur_equipe SET is_captain = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
        $this->sql_manager->execute($sql);
        $this->addActivity("L'equipe " . $this->team->getTeamName($idTeam) . " a un nouveau capitaine : " . $this->getPlayerFullName($idPlayer));
        return true;
    }

    /**
     * @throws Exception
     */
    public function updateMyTeamViceLeader($idPlayer)
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $idTeam = $_SESSION['id_equipe'];
        if (!$this->isPlayerInTeam($idPlayer, $idTeam)) {
            return false;
        }
        $sql = "UPDATE joueur_equipe SET is_vice_leader = 0 WHERE id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
        $sql = "UPDATE joueur_equipe SET is_vice_leader = 1 WHERE id_equipe = $idTeam AND id_joueur = $idPlayer";
        $this->sql_manager->execute($sql);
        $this->addActivity("L'equipe " . $this->team->getTeamName($idTeam) . " a un nouveau suppleant : " . $this->getPlayerFullName($idPlayer));
        return true;
    }

    /**
     * @param mixed $licence
     * @return void
     * @throws Exception
     */
    public function search_player_and_save_from_licence(mixed $licence): void
    {
        // chercher si la licence ou le joueur existe déjà en base
        $query = "(
                        j.departement_affiliation = ? AND j.num_licence = ?) 
                        OR (CONCAT(UPPER(j.nom), ' ', UPPER(j.prenom)) = ?
                      )";
        $bindings = array();
        $bindings[] = array(
            'type' => 'i',
            'value' => intval($licence['departement'])
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $licence['licence_number']
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $licence['last_first_name']
        );
        $current_player = $this->get_one($query, $bindings);
        
        // Gérer la photo si présente
        $idPhoto = null;
        if (isset($licence['photo']) && $licence['photo'] !== null) {
            $idPhoto = $this->savePlayerPhotoFromLicence($licence);
        }
        
        // Vérifier si le club existe, sinon le créer
        $cur_club = $this->club->get_one("affiliation_number = ?", array(array('type' => 's', 'value' => $licence['licence_club'])));
        if (empty($cur_club)) {
            // Créer le club avec les infos de la licence
            $newClubId = $this->club->save(array(
                'nom' => $licence['club'],
                'affiliation_number' => $licence['licence_club'],
                'dirtyFields' => null,
            ));
            $cur_club = array('id' => $newClubId);
        }
        
        // s'il n'existe pas, le créer
        if (empty($current_player)) {
            $newPlayerId = $this->save(array(
                'prenom' => explode(' ', $licence['last_first_name'])[1],
                'nom' => explode(' ', $licence['last_first_name'])[0],
                'num_licence' => $licence['licence_number'],
                'sexe' => $licence['sexe'],
                'departement_affiliation' => $licence['departement'],
                'id_club' => $cur_club['id'],
                'date_homologation' => $licence['homologation_date'],
            ));
            
            // Lier la photo au joueur nouvellement créé
            if ($idPhoto !== null && $newPlayerId) {
                $this->linkPlayerToPhoto($newPlayerId, $idPhoto);
            }
        } else {
            // s'il existe, le mettre à jour
            $this->save(array(
                'id' => $current_player['id'],
                'num_licence' => $licence['licence_number'],
                'sexe' => $licence['sexe'],
                'departement_affiliation' => $licence['departement'],
                'date_homologation' => $licence['homologation_date'],
            ));
            
            // Lier la photo au joueur existant (mettre à jour si nouvelle photo)
            if ($idPhoto !== null) {
                $this->linkPlayerToPhoto($current_player['id'], $idPhoto);
            }
        }
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
     * @param int|array|string|null $results
     * @return array|int|string|null
     */
    public static function adjust_photo_path_from_results(int|array|string|null $results): string|array|int|null
    {
        foreach ($results as $index => $result) {
            $results[$index]['path_photo'] = Generic::accentedToNonAccented($result['path_photo']);
            $results[$index]['path_photo_low'] = Generic::accentedToNonAccented($result['path_photo_low']);
            if (($results[$index]['path_photo'] == '') || (file_exists(__DIR__ . '/../' . $results[$index]['path_photo']) === FALSE)) {
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
            }
        }
        return $results;
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
