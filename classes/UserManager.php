<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Emails.php';
require_once __DIR__ . '/Team.php';
require_once __DIR__ . '/SqlManager.php';
require_once __DIR__ . '/Activity.php';

class UserManager extends Generic
{
    private Activity $activity;
    private Emails $email;
    private Team $team;

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'comptes_acces';
        $this->email = new Emails();
        $this->team = new Team();
        $this->activity = new Activity();
    }

    /**
     * @return array
     * @throws Exception
     */
    function getMyPreferences(): array
    {
        $userDetails = $this->getCurrentUserDetails();
        $user_id = $userDetails['id_user'];
        $sql = "SELECT r.registry_value AS is_remind_matches 
                FROM registry r
                WHERE r.registry_key = 'users.$user_id.is_remind_matches'";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $new_password
     * @param $new_password_again
     * @throws Exception
     */
    public function modifierMonMotDePasse($new_password, $new_password_again)
    {
        $userDetails = $this->getCurrentUserDetails();
        $id_team = $userDetails['id_equipe'];
        $login = $userDetails['login'];
        $password = $new_password;
        $passwordAgain = $new_password_again;
        if (!isset($password)) {
            throw new Exception("Le mot de passe n'a pas été soumis !");
        }
        if (!isset($passwordAgain)) {
            throw new Exception("La confirmation du mot de passe n'a pas été soumise !");
        }
        if ($password !== $passwordAgain) {
            throw new Exception("Les 2 mots de passes ne correspondent pas !");
        }
        $sql = "UPDATE comptes_acces 
                SET password_hash = MD5(CONCAT(?, ?)) 
                WHERE id_equipe = ? 
                AND login = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $password);
        $bindings[] = array('type' => 'i', 'value' => $id_team);
        $bindings[] = array('type' => 's', 'value' => $login);
        $this->sql_manager->execute($sql, $bindings);
        $this->activity->add("Mot de passe modifie");
    }

    /**
     * @param $is_remind_matches
     * @return void
     * @throws Exception
     */
    public function saveMyPreferences($is_remind_matches): void
    {
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        if ($this->isRegistryKeyPresent("users.$id_user.is_remind_matches")) {
            $sql = "UPDATE registry 
                    SET registry_value = ? 
                    WHERE registry_key = ?";
        } else {
            $sql = "INSERT INTO registry 
                    SET registry_value = ?, 
                        registry_key = ?";
        }
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $is_remind_matches);
        $bindings[] = array('type' => 's', 'value' => "users.$id_user.is_remind_matches");
        $this->sql_manager->execute($sql, $bindings);
        $this->activity->add("Préférence de réception modifiée: rappel de match");
    }

    /**
     * @param $key
     * @return bool
     * @throws Exception
     */
    private function isRegistryKeyPresent($key): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM registry WHERE registry_key = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $key);
        $results = $this->sql_manager->execute($sql, $bindings);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @param $login
     * @param $email
     * @param $team_id
     * @throws Exception
     */
    public function create_leader_account($login, $email, $team_id): void
    {
        // do not create team account if it already exists
        if ($this->is_existing_user($login, $email, $team_id)) {
            return;
        }
        $password = Generic::randomPassword();
        $sql = "INSERT INTO comptes_acces SET 
                        id_equipe = ?, 
                        login = ?, 
                        email = ?, 
                        password_hash = MD5(CONCAT(?, ?))";
        $bindings = array(
            array('type' => 'i', 'value' => $team_id),
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $email),
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $password),
        );
        $id_account = $this->sql_manager->execute($sql, $bindings);
        $sql = "INSERT INTO users_profiles SET 
                        user_id = ?, 
                        profile_id = (SELECT id FROM profiles WHERE name = 'RESPONSABLE_EQUIPE')";
        $bindings = array(
            array('type' => 'i', 'value' => $id_account),
        );
        $this->sql_manager->execute($sql, $bindings);
        $team = $this->team->getTeam($team_id);
        $team_name = $team['nom_equipe'];
        $this->activity->add("Creation du compte $login responsable de l'equipe $team_name");
        $this->email->sendMailNewUser($email, $login, $password, $team_id);
    }

    /**
     * @param $login
     * @param $email
     * @param $team_id
     * @return bool
     * @throws Exception
     */
    public function is_existing_user($login, $email, $team_id): bool
    {
        $sql = "SELECT COUNT(*) AS cnt 
                FROM comptes_acces 
                WHERE login = ?
                  AND email = ?
                  AND id_equipe = ?";
        $bindings = array(
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $email),
            array('type' => 'i', 'value' => $team_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @throws Exception
     */
    public function remove($login, $team_id): void
    {
        $sql = "DELETE 
                FROM comptes_acces 
                WHERE login = ? 
                  AND id_equipe = ?";
        $bindings = array(
            array('type' => 's', 'value' => $login),
            array('type' => 'i', 'value' => $team_id),
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @param $login
     * @return bool
     * @throws Exception
     */
    public function isUserExists($login): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM comptes_acces WHERE login = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $results = $this->sql_manager->execute($sql, $bindings);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @param $login
     * @param $email
     * @param $id_equipe
     * @throws Exception
     */
    public function createUser($login, $email, $id_equipe)
    {
        if ($this->isUserExists($login)) {
            throw new Exception("Ce compte existe déjà !");
        }
        if ($id_equipe === NULL) {
            $id_equipe = 0;
        }
        $password = Generic::randomPassword();
        $sql = "INSERT INTO comptes_acces SET 
                    id_equipe = ?, 
                    login = ?, 
                    email = ?, 
                    password_hash = MD5(CONCAT(?, ?))";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $email);
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $password);
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity("Creation du compte $login pour l'equipe " . $this->team->getTeamName($id_equipe));
        $this->email->sendMailNewUser($email, $login, $password, $id_equipe);
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function deleteUsers($ids)
    {
        $explodedIds = explode(',', $ids);
        $logins = array();
        foreach ($explodedIds as $id) {
            $logins[] = $this->getUserLogin($id);
        }
        $sql = "DELETE FROM comptes_acces WHERE id IN($ids)";
        $this->sql_manager->execute($sql);
        foreach ($logins as $login) {
            $this->addActivity("Suppression du compte : $login");
        }
    }

    /**
     * @throws Exception
     */
    public function getUsers(): array|int|string|null
    {
        $sql = "SELECT  ca.id, 
                        ca.login, 
                        ca.password_hash,
                        ca.email,
                        e.id_equipe AS id_team,
                        e.nom_equipe AS team_name,
                        c.nom AS club_name,
                        up.profile_id AS id_profile,
                        p.name AS profile
        FROM comptes_acces ca 
        LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe 
        LEFT JOIN clubs c ON c.id=e.id_club 
        LEFT JOIN users_profiles up ON up.user_id=ca.id 
        LEFT JOIN profiles p ON p.id=up.profile_id";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveUser(
        $id,
        $login,
        $email,
        $id_team,
        $dirtyFields = null)
    {
        $bindings = array();
        $inputs = array(
            'id' => $id,
            'login' => $login,
            'email' => $email,
            'id_team' => $id_team,
        );
        if (empty($id)) {
            if ($this->isUserExists($login)) {
                throw new Exception("L'utilisateur n'existe pas !");
            }
        }
        if (empty($id)) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " comptes_acces SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
                    break;
                case 'id_team':
                    if (strlen($value) === 0) {
                        $sql .= "id_equipe = NULL,";
                    } else {
                        $bindings[] = array(
                            'type' => 'i',
                            'value' => $value
                        );
                        $sql .= "id_equipe = ?,";
                    }
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
        if (!empty($id)) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $id
            );
            $sql .= " WHERE id = ?";
        }
        $this->sql_manager->execute($sql, $bindings);
        if (empty($id)) {
            $comment = "Creation d'un nouvel utilisateur : $login";
            $this->addActivity($comment);
            return;
        }
        if (empty($dirtyFields)) {
            return;
        }
        $fieldsArray = explode(',', $dirtyFields);
        foreach ($fieldsArray as $fieldName) {
            $fieldValue = filter_input(INPUT_POST, $fieldName);
            $comment = "$login : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
            $this->addActivity($comment);
        }
    }

    /**
     * @throws Exception
     */
    public function saveProfile($id,
                                $name,
                                $dirtyFields = null)
    {
        $bindings = array();
        $inputs = array(
            'id' => $id,
            'name' => $name,
        );
        if (empty($inputs['id'])) {
            if ($this->isProfileExists($inputs['name'])) {
                throw new Exception("Le profil n'existe pas !");
            }
        }
        if (empty($inputs['id'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " profiles SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id':
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
        $this->sql_manager->execute($sql, $bindings);
        if (empty($inputs['id'])) {
            $comment = "Creation d'un nouveau profil : $name";
            $this->addActivity($comment);
        } else {
            if (!empty($dirtyFields)) {
                $fieldsArray = explode(',', $dirtyFields);
                foreach ($fieldsArray as $fieldName) {
                    $fieldValue = filter_input(INPUT_POST, $fieldName);
                    $name = $inputs['name'];
                    $comment = "$name : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                    $this->addActivity($comment);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function isProfileExists($name): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM profiles WHERE name = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $name);
        $results = $this->sql_manager->execute($sql, $bindings);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @throws Exception
     */
    function addProfileToUsers($id_profile, $id_users, $dirtyFields = null)
    {
        foreach (explode(',', $id_users) as $idUser) {
            $hasProfile = $this->hasProfile($idUser);
            if ($hasProfile) {
                $sql = "UPDATE ";
            } else {
                $sql = "INSERT ";
            }
            $sql .= "users_profiles SET profile_id = $id_profile, user_id = $idUser ";
            if ($hasProfile) {
                $sql .= "WHERE user_id = $idUser";
            }
            $this->sql_manager->execute($sql);
            $this->addActivity($this->getUserLogin($idUser) . " a obtenu le profil " . $this->getProfileName($id_profile));
        }
    }

    /**
     * @throws Exception
     */
    public function hasProfile($idUser): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM users_profiles WHERE user_id = $idUser";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function getProfileName($idProfile)
    {
        $sql = "SELECT 
        p.name AS profile_name 
        FROM profiles p 
        WHERE p.id = $idProfile";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['profile_name'];
    }

    /**
     * @throws Exception
     */
    public function getUserLogin($idUser)
    {
        $sql = "SELECT 
        ca.login AS login
        FROM comptes_acces ca
        WHERE ca.id = $idUser";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['login'];
    }

    /**
     * @throws Exception
     */
    public function getProfiles(): array|int|string|null
    {
        $sql = "SELECT id, name FROM profiles";
        return $this->sql_manager->execute($sql);
    }

    public static function is_connected(): bool
    {
        @session_start();
        return isset($_SESSION['profile_name']);
    }

    public static function isTeamLeader(): bool
    {
        @session_start();
        return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "RESPONSABLE_EQUIPE");
    }

    public static function isAdmin(): bool
    {
        @session_start();
        return (isset($_SESSION['profile_name']) && $_SESSION['profile_name'] == "ADMINISTRATEUR");
    }


    /**
     * @return void
     */
    #[NoReturn] public function logout(): void
    {
        @session_start();
        @session_destroy();
        die('<META HTTP-equiv="refresh" content=0;URL=' . filter_input(INPUT_SERVER, 'HTTP_REFERER') . '>');
    }

    /**
     * @throws Exception
     */
    public function login(): void
    {
        $login = filter_input(INPUT_POST, 'login');
        $password = filter_input(INPUT_POST, 'password');
        $redirect = filter_input(INPUT_POST, 'redirect');
        if (($login === NULL) || ($password === NULL)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Veuillez remplir les champs de connexion'
            ));
            return;
        }
        $sql = "SELECT  ca.id_equipe, 
                        ca.login, 
                        ca.id AS id_user,
                        p.name AS profile_name 
                FROM comptes_acces ca
                LEFT JOIN users_profiles up ON up.user_id=ca.id
                LEFT JOIN profiles p ON p.id=up.profile_id
                WHERE ca.login = ?
                AND ca.password_hash = MD5(CONCAT(?, ?))
                LIMIT 1";
        $bindings = array();
        $bindings[] = array(
            'type' => 's',
            'value' => $login
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $login
        );
        $bindings[] = array(
            'type' => 's',
            'value' => $password
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) != 1) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Login ou mot de passe incorrect'
            ));
            return;
        }
        $data = $results[0];
        $_SESSION['id_equipe'] = $data['id_equipe'];
        $_SESSION['login'] = $data['login'];
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['profile_name'] = $data['profile_name'];
        if (!empty($redirect)) {
            header('Location: ' . urldecode($redirect));
            exit(0);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }

    /**
     * @throws Exception
     */
    public function reset_my_password($id, $hash): array
    {
        // check hash
        $user_details = $this->get_by_id($id);
        $expected_hash = md5($id . $user_details['login'] . $user_details['email'] . date('Y-m-d'));
        if ($expected_hash !== $hash) {
            throw new Exception("Le lien n'est pas ou plus valide !");
        }
        $this->reset_password($id);
        // method is GET, display status
        return array(
            'Reset password' => 'OK',
            'Message' => "Reset du mot de passe ok, vous allez recevoir un nouvel email avec vos identifiants.",
        );
    }

    /**
     * @param $id
     * @throws Exception
     */
    public function reset_password($id): void
    {
        $userDetails = $this->get_by_id($id);
        $id_team = empty($userDetails['id_equipe']) ? 0 : $userDetails['id_equipe'];
        $email = $userDetails['email'];
        $login = $userDetails['login'];
        $password = Generic::randomPassword();
        $sql = "UPDATE comptes_acces 
                SET password_hash = MD5(CONCAT(?, ?)) 
                WHERE (id_equipe IS NULL OR id_equipe = ?) 
                AND login = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $password);
        $bindings[] = array('type' => 'i', 'value' => $id_team);
        $bindings[] = array('type' => 's', 'value' => $login);
        $this->sql_manager->execute($sql, $bindings);
        $this->activity->add("Mot de passe modifie");
        $this->email->sendMailNewUser($email, $login, $password, $id_team);
    }

    /**
     * @throws Exception
     */
    public function request_reset_password($user_team_id,
                                           $user_email,
                                           $dirtyFields = null): void
    {
        $results = $this->get("id_equipe = $user_team_id AND email = '$user_email'");
        if (count($results) === 0) {
            throw new Exception("Il n'existe pas de compte avec cette adresse email pour cette équipe !");
        }
        $result = $results[0];
        $url = $this->get_page_url() .
            '/rest/action.php/usermanager/reset_my_password?' .
            http_build_query(array(
                'id' => $result['id'],
                'hash' => md5($result['id'] . $result['login'] . $result['email'] . date('Y-m-d')),));
        $this->email->send_reset_password($user_email, $result['login'], $result['id_equipe'], $url);
        $message = "Demande d'initialisation de mot de passe effectuée.<br/>Vous allez recevoir un email vous indiquant la marche à suivre.";
        throw new Exception($message, 201);
    }

    private function get_page_url(): string
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $url = "https://";
        } else {
            $url = "http://";
        }
        // Append the host(domain name, ip) to the URL.
        $url .= $_SERVER['HTTP_HOST'];
        // Append the requested resource location to the URL
//        $url.= $_SERVER['REQUEST_URI'];
        return $url;
    }

}