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
     * @return bool
     * @throws Exception
     */
    public function modifierMonMotDePasse($new_password, $new_password_again)
    {
        $userDetails = $this->getCurrentUserDetails();
        $id_team = $userDetails['id_equipe'];
        $password = $new_password;
        $passwordAgain = $new_password_again;
        if (!isset($password)) {
            throw new Exception("Password has not been submitted!");
        }
        if (!isset($passwordAgain)) {
            throw new Exception("Password confirmation has not been submitted!");
        }
        if ($password !== $passwordAgain) {
            throw new Exception("Password and password confirmation do not match!");
        }
        $sql = "UPDATE comptes_acces SET password = '$password' WHERE id_equipe = $id_team";
        $this->sql_manager->execute($sql);
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
                    SET registry_value = '$is_remind_matches' 
                    WHERE registry_key = 'users.$id_user.is_remind_matches'";
        } else {
            $sql = "INSERT INTO registry 
                    SET registry_value = '$is_remind_matches', 
                        registry_key = 'users.$id_user.is_remind_matches'";
        }
        $this->sql_manager->execute($sql);
        $this->activity->add("Préférence de réception modifiée: rappel de match");
    }

    /**
     * @param $key
     * @return bool
     * @throws Exception
     */
    private function isRegistryKeyPresent($key): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM registry WHERE registry_key = '$key'";
        $results = $this->sql_manager->execute($sql);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @param $login
     * @param $email
     * @param $team_id
     * @throws Exception
     */
    public function create_leader_account($login, $email, $team_id)
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
                        password = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $team_id),
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $email),
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
    public function remove($login, $team_id)
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
        $sql = "SELECT COUNT(*) AS cnt FROM comptes_acces WHERE login = '$login'";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @param $login
     * @param $email
     * @param $idTeam
     * @throws Exception
     */
    public function createUser($login, $email, $id_equipe)
    {
        if ($this->isUserExists($login)) {
            throw new Exception("Account already exists ! !");
        }
        if ($id_equipe === NULL) {
            $id_equipe = 0;
        }
        $password = Generic::randomPassword();
        $sql = "INSERT comptes_acces SET id_equipe = ?, login = ?, email = ?, password = ?";
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_equipe);
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $email);
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
        $sql = "SELECT ca.id, ca.login, ca.password, ca.email, e.id_equipe AS id_team, e.nom_equipe AS team_name, c.nom AS club_name, up.profile_id AS id_profile, p.name AS profile
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
    public function saveUser()
    {
        $bindings = array();
        $inputs = array(
            'id' => filter_input(INPUT_POST, 'id'),
            'login' => filter_input(INPUT_POST, 'login'),
            'email' => filter_input(INPUT_POST, 'email'),
            'password' => filter_input(INPUT_POST, 'password'),
            'id_team' => filter_input(INPUT_POST, 'id_team')
        );
        if (empty($inputs['id'])) {
            if ($this->isUserExists($inputs['login'])) {
                throw new Exception("L'utilisateur n'existe pas !");
            }
        }
        if (empty($inputs['id'])) {
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
        if (!empty($inputs['id'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id']
            );
            $sql .= " WHERE id = ?";
        }
        $this->sql_manager->execute($sql, $bindings);
        if (empty($inputs['id'])) {
            $login = $inputs['login'];
            $comment = "Creation d'un nouvel utilisateur : $login";
            $this->addActivity($comment);
        } else {
            $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
            if ($dirtyFields) {
                $fieldsArray = explode(',', $dirtyFields);
                foreach ($fieldsArray as $fieldName) {
                    $fieldValue = filter_input(INPUT_POST, $fieldName);
                    $login = $inputs['login'];
                    $comment = "$login : Modification du champ $fieldName, nouvelle valeur : $fieldValue";
                    if ($fieldName === 'password') {
                        $comment = "$login : Modification du champ $fieldName";
                    }
                    $this->addActivity($comment);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function saveProfile()
    {
        $bindings = array();
        $inputs = array(
            'id' => filter_input(INPUT_POST, 'id'),
            'name' => filter_input(INPUT_POST, 'name')
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
            $name = $inputs['name'];
            $comment = "Creation d'un nouveau profil : $name";
            $this->addActivity($comment);
        } else {
            $dirtyFields = filter_input(INPUT_POST, 'dirtyFields');
            if ($dirtyFields) {
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
        $sql = "SELECT COUNT(*) AS cnt FROM profiles WHERE name = '$name'";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    function addProfileToUsers($idProfile, $idUsers)
    {
        foreach (explode(',', $idUsers) as $idUser) {
            $hasProfile = $this->hasProfile($idUser);
            if ($hasProfile) {
                $sql = "UPDATE ";
            } else {
                $sql = "INSERT ";
            }
            $sql .= "users_profiles SET profile_id = $idProfile, user_id = $idUser ";
            if ($hasProfile) {
                $sql .= "WHERE user_id = $idUser";
            }
            $this->sql_manager->execute($sql);
            $this->addActivity($this->getUserLogin($idUser) . " a obtenu le profil " . $this->getProfileName($idProfile));
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

//    public function getConnectedUser()
//    {
//        if (UserManager::isAdmin()) {
//            return "Administrateur";
//        }
//        if (UserManager::isTeamLeader()) {
//            $jsonTeamDetails = json_decode($this->team->getMyTeam());
//            return $jsonTeamDetails[0]->team_full_name;
//        }
//        if (isset($_SESSION['login'])) {
//            return $_SESSION['login'];
//        }
//        return "";
//    }

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
    public function login()
    {
        $login = filter_input(INPUT_POST, 'login');
        $password = filter_input(INPUT_POST, 'password');
        if (($login === NULL) || ($password === NULL)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Veuillez remplir les champs de connexion'
            ));
            return;
        }
        $password = addslashes($password);
        $sql = "SELECT ca.id_equipe, ca.login, ca.password, ca.id AS id_user, p.name AS profile_name FROM comptes_acces ca
        LEFT JOIN users_profiles up ON up.user_id=ca.id
        LEFT JOIN profiles p ON p.id=up.profile_id
        WHERE ca.login = '$login' LIMIT 1";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Login incorrect'
            ));
            return;
        }
        $data = $results[0];
        if ($data['password'] != $password) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Mot de passe invalide'
            ));
            return;
        }
        $_SESSION['id_equipe'] = $data['id_equipe'];
        $_SESSION['login'] = $data['login'];
        $_SESSION['password'] = $data['password'];
        $_SESSION['id_user'] = $data['id_user'];
        $_SESSION['profile_name'] = $data['profile_name'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }


}