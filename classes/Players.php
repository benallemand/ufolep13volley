<?php

require_once __DIR__ . '/Configuration.php';
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/Club.php';
require_once __DIR__ . '/Photo.php';


class Players extends Generic
{
    private Club $club;
    private Team $team;
    private Photo $photo;

    public function __construct()
    {
        parent::__construct();
        $this->team = new Team();
        $this->club = new Club();
        $this->photo = new Photo();
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
        $id_team = $_SESSION['id_equipe'];
        return $this->get_players("je.id_equipe = $id_team");
    }

    /**
     * @param string $where
     * @param string $order_by
     * @return array
     * @throws Exception
     */
    public function get_players(string $where = "1=1", $order_by = "j.sexe, UPPER(j.nom)"): array
    {
        $sql = "SELECT
                CONCAT(UPPER(j.nom), ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') AS full_name,
                j.prenom, 
                UPPER(j.nom) AS nom, 
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
                GROUP_CONCAT( DISTINCT concat(e.nom_equipe, ' (', comp.libelle, ')', ' (D', cl.division, ')') SEPARATOR '<br/>') AS teams_list,
                GROUP_CONCAT( DISTINCT e2.nom_equipe SEPARATOR '<br/>') AS team_leader_list,
                DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation
        FROM joueurs j
            LEFT JOIN joueur_equipe je ON je.id_joueur = j.id
            LEFT JOIN joueur_equipe je2 ON je2.id_joueur = j.id AND je2.is_leader+0 > 0
            LEFT JOIN equipes e ON e.id_equipe=je.id_equipe
            LEFT JOIN equipes e2 ON e2.id_equipe=je2.id_equipe
            LEFT JOIN clubs c ON c.id = j.id_club
            LEFT JOIN photos p ON p.id = j.id_photo
            LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
            LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
        WHERE $where
        GROUP BY j.id, j.sexe, UPPER(j.nom)
        ORDER BY $order_by";
        $results = $this->sql_manager->execute($sql);
        foreach ($results as $index => $result) {
            if ($result['show_photo'] === 1) {
                $results[$index]['path_photo'] = Generic::accentedToNonAccented($result['path_photo']);
                if (($results[$index]['path_photo'] == '') || (file_exists("../" . $results[$index]['path_photo']) === FALSE)) {
                    switch ($result['sexe']) {
                        case 'M':
                            $results[$index]['path_photo'] = 'images/MaleMissingPhoto.png';
                            break;
                        case 'F':
                            $results[$index]['path_photo'] = 'images/FemaleMissingPhoto.png';
                            break;
                        default:
                            break;
                    }
                }
            } else {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/MalePhotoNotAllowed.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/FemalePhotoNotAllowed.png';
                        break;
                    default:
                        break;
                }
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function update_player(
        $id_team,
        $prenom,
        $nom,
        $num_licence,
        $date_homologation,
        $sexe,
        $departement_affiliation,
        $est_actif,
        $id_club,
        $show_photo,
        $telephone,
        $email,
        $telephone2,
        $email2,
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
            'est_actif' => $est_actif,
            'id_club' => $id_club,
            'show_photo' => $show_photo,
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
                    break;
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
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $sql .= "$key = ?,";
                    $bindings[] = array('type' => 'i', 'value' => $val);
                    break;
                default:
                    if (empty($value) || $value == 'null') {
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
            if (UserManager::isTeamLeader()) {
                if ($newId > 0) {
                    if (!$this->addPlayerToMyTeam($newId)) {
                        throw new Exception("Erreur durant l'ajout du joueur à l'équipe");
                    }
                }
            }
            $firstName = $parameters['prenom'];
            $name = $parameters['nom'];
            $comment = "Creation d'un nouveau joueur : $firstName $name";
            $this->addActivity($comment);
        } else {
            $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
            if ($dirtyFields) {
                $fieldsArray = explode(',', $dirtyFields);
                foreach ($fieldsArray as $fieldName) {
                    $fieldValue = filter_input(INPUT_POST, $fieldName);
                    $firstName = $parameters['prenom'];
                    $name = $parameters['nom'];
                    $comment = "$firstName $name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                    $this->addActivity($comment);
                    if ($fieldName === 'est_actif') {
                        if ($fieldValue === 'on' || $fieldValue === 1) {
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
        $this->savePhoto($parameters, $newId);
        return $newId;
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
            null,
            null,
            null,
            $id_club,
            null,
            $leader_phone,
            $leader_email,
            null,
            null);
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
        $est_actif,
        $id_club,
        $show_photo,
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
            'est_actif' => $est_actif,
            'id_club' => $id_club,
            'show_photo' => $show_photo,
            'est_responsable_club' => $est_responsable_club,
            'telephone' => $telephone,
            'email' => $email,
            'telephone2' => $telephone2,
            'email2' => $email2,
        );
        $bindings = array();
        if (empty($inputs['id'])) {
            if (!empty($inputs['num_licence'])) {
                if ($this->isPlayerExists($inputs['num_licence'])) {
                    throw new Exception("Un joueur avec le même numéro de licence existe déjà !");
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
            switch ($key) {
                case 'id':
                case 'id_team':
                case 'dirtyFields':
                    break;
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
                case 'est_actif':
                case 'est_responsable_club':
                case 'show_photo':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $val
                    );
                    $sql .= "$key = ?,";
                    break;
                default:
                    if (empty($value) || $value == 'null') {
                        $sql .= "$key = NULL,";
                    } else {
                        $bindings[] = array(
                            'type' => 's',
                            'value' => $value
                        );
                        $sql .= "$key = ?,";
                    }
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
        if (empty($inputs['id'])) {
            if (UserManager::isTeamLeader()) {
                if ($newId > 0) {
                    if (!$this->addPlayerToMyTeam($newId)) {
                        throw new Exception("Erreur durant l'ajout du joueur à l'équipe");
                    }
                }
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
                    if ($fieldName === 'est_actif') {
                        if ($fieldValue === 'on' || $fieldValue === 1) {
                            if (empty($inputs['id'])) {
                                $player_id = $newId;
                            } else {
                                $player_id = $inputs['id'];
                            }
                            require_once __DIR__ . '/../classes/Emails.php';
                            $email_manager = new Emails();
                            $email_manager->insert_email_notify_activated_player($player_id);
                        }
                    }
                }
            }
        }
        $this->savePhoto($inputs, $newId);
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
            $where .= " AND CONCAT(j.nom, ' ', j.prenom, ' (', IFNULL(j.num_licence, ''), ')') LIKE '%$query%'";
        }
        // filter available match players by id_match (known teams)
        if (!empty($id_match)) {
            $where .= " AND (
                        e.id_equipe IN (SELECT id_equipe_dom FROM matches WHERE id_match = $id_match) 
                        OR e.id_equipe IN (SELECT id_equipe_ext FROM matches WHERE id_match = $id_match)
                        )";
        }
        return $this->get_players($where, "c.nom IS NULL, c.nom, j.nom");
    }

    /**
     * @throws Exception
     */
    public function getPlayersPdf($idTeam, $rootPath = '../', $doHideInactivePlayers = false)
    {
        if ($idTeam === NULL) {
            return false;
        }
        if (!$this->team->isTeamSheetAllowedForUser($idTeam)) {
            throw new Exception("Vous n'avez pas la permission de consulter cette équipe !");
        }
        $results = $this->get_players("je.id_equipe = $idTeam");
        foreach ($results as $index => $result) {
            if ($result['show_photo'] === 1) {
                $results[$index]['path_photo'] = Generic::accentedToNonAccented($result['path_photo']);
                if (($results[$index]['path_photo'] == '') || (file_exists($rootPath . $results[$index]['path_photo']) === FALSE)) {
                    switch ($result['sexe']) {
                        case 'M':
                            $results[$index]['path_photo'] = 'images/MaleMissingPhoto.png';
                            break;
                        case 'F':
                            $results[$index]['path_photo'] = 'images/FemaleMissingPhoto.png';
                            break;
                        default:
                            break;
                    }
                }
            } else {
                switch ($result['sexe']) {
                    case 'M':
                        $results[$index]['path_photo'] = 'images/MalePhotoNotAllowed.png';
                        break;
                    case 'F':
                        $results[$index]['path_photo'] = 'images/FemalePhotoNotAllowed.png';
                        break;
                    default:
                        break;
                }
            }
        }
        return $results;
    }

    /**
     * @throws Exception
     */
    public function getPlayersFromTeam($id_equipe)
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
        j.telephone2, 
        j.email2, 
        j.est_responsable_club+0 AS est_responsable_club, 
        je.is_captain+0 AS is_captain, 
        je.is_vice_leader+0 AS is_vice_leader, 
        je.is_leader+0 AS is_leader, 
        j.id, 
        j.show_photo+0 AS show_photo,
        DATE_FORMAT(j.date_homologation, '%d/%m/%Y') AS date_homologation 
        FROM joueur_equipe je
        LEFT JOIN joueurs j ON j.id=je.id_joueur
        LEFT JOIN photos p ON p.id = j.id_photo
    WHERE id_equipe = $id_equipe";
        $results = $this->sql_manager->execute($sql);

        return $results;
    }

    /**
     * @throws Exception
     */
    public function set_leader($ids, $id_team = null)
    {
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
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
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
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
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
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
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
}
