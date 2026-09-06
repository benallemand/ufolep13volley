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
        $results = $this->sql_manager->execute($sql);
        if (count($results) === 0) {
            return array('is_remind_matches' => false);
        } else {
            return array('is_remind_matches' => $results[0]['is_remind_matches'] == 'on');
        }
    }

    /**
     * @param $new_password
     * @param $new_password_again
     * @throws Exception
     */
    public function modifierMonMotDePasse($new_password, $new_password_again)
    {
        $userDetails = $this->getCurrentUserDetails();
        $user_id = $userDetails['id_user'];
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
                WHERE id = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $password);
        $bindings[] = array('type' => 'i', 'value' => $user_id);
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
        $bindings[] = array('type' => 's', 'value' => in_array($is_remind_matches, array('on', '1', 'true')) ? 'on' : 'off');
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
     * @param $email
     * @param $team_id
     * @throws Exception
     */
    public function create_or_update_leader_account($email, $team_id): void
    {
        $login = strtolower($email);
        // recherche par email : un email = un compte (issue #247), même si le
        // compte existant porte un login historique différent de l'email
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $user = $this->get_one("email = ?", $bindings);
        if (!$user) {
            $password = Generic::randomPassword();
            $this->insert_user($login, $email, $password);
            $user = $this->get_one("email = ?", $bindings);
            $this->email->sendMailNewUser($email, $login, $password);
            $this->activity->add("Compte $login créé");
            error_log("le compte $login n'existe pas, création ok");
        } else {
            error_log("le compte $login existe déjà, ok");
        }
        if (!$user) {
            throw new Exception("Impossible de créer le compte $login !");
        }
        // link team if not already linked
        if (!$this->is_existing_user_team($user['id'], $team_id)) {
            $this->insert_user_team($user['id'], $team_id);
            $team = $this->team->getTeam($team_id);
            $team_name = $team['nom_equipe'];
            $this->activity->add("Compte $login responsable de l'equipe $team_name");
            error_log("le compte $login n'est pas lié à l'équipe, création du lien ok");
        } else {
            error_log("le compte $login est déjà lié à l'équipe, ok");
        }
    }

    /**
     * @param $login
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function is_existing_user($login, $email): bool
    {
        $sql = "SELECT COUNT(*) AS cnt 
                FROM comptes_acces 
                WHERE login = ?
                  AND email = ?";
        $bindings = array(
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $email),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return intval($results[0]['cnt']) > 0;
    }

    /**
     * @throws Exception
     */
    public function remove($login): void
    {
        $sql = "DELETE 
                FROM comptes_acces 
                WHERE login = ?";
        $bindings = array(
            array('type' => 's', 'value' => $login),
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
     * @throws Exception
     */
    public function isEmailExists($email): bool
    {
        $sql = "SELECT COUNT(*) AS cnt FROM comptes_acces WHERE email = ?";
        $bindings = array(array('type' => 's', 'value' => $email));
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
        // un email = un compte (issue #247)
        if ($this->isEmailExists($email)) {
            throw new Exception("Un compte existe déjà avec cet email !");
        }
        $password = Generic::randomPassword();
        $user_id = $this->insert_user($login, $email, $password);
        $this->insert_user_team($user_id, $id_equipe);
        $this->addActivity("Creation du compte $login pour l'equipe " . $this->team->getTeamName($id_equipe));
        $this->email->sendMailNewUser($email, $login, $password);
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
        $sql = file_get_contents(__DIR__ . '/../sql/get_users.sql');
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveUser(
        $id,
        $login,
        $email,
        $dirtyFields = null)
    {
        // un email = un compte (issue #247) : le login des nouveaux comptes est l'email
        if (empty($id) && empty($login)) {
            $login = strtolower($email);
        }
        $bindings = array();
        $inputs = array(
            'id' => $id,
            'login' => $login,
            'email' => $email,
        );
        if (empty($id)) {
            if ($this->isUserExists($login)) {
                throw new Exception("Ce login existe déjà !");
            }
            // un email = un compte (issue #247)
            if ($this->isEmailExists($email)) {
                throw new Exception("Un compte existe déjà avec cet email !");
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
     * Donne ou retire le rôle admin à un compte.
     * @throws Exception
     */
    public function setAdmin($user_id, $is_admin, $dirtyFields = null): void
    {
        if (!self::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent faire ça !", 403);
        }
        $is_admin_bool = filter_var($is_admin, FILTER_VALIDATE_BOOLEAN);
        $sql = "UPDATE comptes_acces SET is_admin = ? WHERE id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $is_admin_bool ? 1 : 0),
            array('type' => 'i', 'value' => $user_id),
        );
        $this->sql_manager->execute($sql, $bindings);
        $this->addActivity($this->getUserLogin($user_id) . ($is_admin_bool ? " a obtenu" : " a perdu") . " le rôle administrateur");
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

    public static function is_connected(): bool
    {
        @session_start();
        return isset($_SESSION['id_user']);
    }

    // Les rôles sont dérivés et cumulables (issue #245) : un même compte peut
    // être admin (comptes_acces.is_admin), responsable d'équipe (users_teams)
    // et responsable de club (users_clubs). Les flags sont posés en session
    // au login (cf. setSessionRoles).
    // is_team_leader traduit l'existence d'une équipe COURANTE en session : elle
    // vient de users_teams, ou d'une équipe d'un club géré sélectionnée via
    // switchCurrentUserTeam().
    public static function isTeamLeader(): bool
    {
        @session_start();
        return !empty($_SESSION['is_team_leader']);
    }

    public static function isClubLeader(): bool
    {
        @session_start();
        return !empty($_SESSION['is_club_leader']);
    }

    public static function isAdmin(): bool
    {
        @session_start();
        return !empty($_SESSION['is_admin']);
    }


    /**
     * @return void
     */
    #[NoReturn]
    public function logout(): void
    {
        @session_start();
        @session_destroy();
        die('<META HTTP-equiv="refresh" content=0;URL=/>');
    }

    /**
     * Authentifie un compte par login OU email (issue #247). Le sel du hash
     * reste le login stocké : aucun mot de passe n'est invalidé.
     * @return array|null id_user, login, is_admin, id_equipe (première équipe liée)
     * @throws Exception
     */
    public function authenticate(string $login_or_email, string $password): ?array
    {
        $sql = "SELECT  ut.team_id AS id_equipe,
                        ca.login,
                        ca.id AS id_user,
                        ca.is_admin
                FROM comptes_acces ca
                LEFT JOIN users_teams ut ON ca.id = ut.user_id
                WHERE (ca.login = ? OR ca.email = ?)
                AND ca.password_hash = MD5(CONCAT(CONVERT(ca.login USING utf8mb4), ?))
                LIMIT 1";
        $bindings = array(
            array('type' => 's', 'value' => $login_or_email),
            array('type' => 's', 'value' => $login_or_email),
            array('type' => 's', 'value' => $password),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) === 0) {
            return null;
        }
        return $results[0];
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
        $data = $this->authenticate($login, $password);
        if ($data === null) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Login ou mot de passe incorrect'
            ));
            return;
        }
        $this->setSessionRoles((int)$data['id_user'], $data['login'], !empty($data['is_admin']), $data['id_equipe']);
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
        $email = $userDetails['email'];
        $login = $userDetails['login'];
        $password = Generic::randomPassword();
        $sql = "UPDATE comptes_acces 
                SET password_hash = MD5(CONCAT(?, ?)) 
                WHERE login = ?";
        $bindings = array();
        $bindings[] = array('type' => 's', 'value' => $login);
        $bindings[] = array('type' => 's', 'value' => $password);
        $bindings[] = array('type' => 's', 'value' => $login);
        $this->sql_manager->execute($sql, $bindings);
        $this->activity->add("Mot de passe modifie", $id);
        $this->email->sendMailNewUser($email, $login, $password);
    }

    /**
     * Demande de réinitialisation par email seul : un email = un compte
     * (issue #247). Le paramètre $login est conservé pour compatibilité.
     * @throws Exception
     */
    public function request_reset_password($user_email,
                                           $login = null,
                                           $dirtyFields = null): void
    {
        $bindings = array(array('type' => 's', 'value' => $user_email));
        $results = $this->get("email = ?", $bindings);
        if (count($results) === 0) {
            throw new Exception("Il n'existe pas de compte avec cette adresse email !");
        }
        $result = $results[0];
        // Le lien atterrit sur une page Vue qui appelle reset_my_password et
        // affiche le résultat (issue #266) — avant, il pointait directement sur
        // l'endpoint REST et l'utilisateur voyait du JSON brut.
        $url = $this->get_page_url() .
            '/pages/home.html#/reset_password/confirm?' .
            http_build_query(array(
                'id' => $result['id'],
                'hash' => md5($result['id'] . $result['login'] . $result['email'] . date('Y-m-d')),));
        $this->email->send_reset_password($user_email, $result['login'], $url);
        $message = "Demande d'initialisation de mot de passe effectuée. Vous allez recevoir un email vous indiquant la marche à suivre.";
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

    /**
     * @throws Exception
     */
    private function insert_user(string $login, string $email, string $password): array|int|string|null
    {
        $sql = "INSERT INTO comptes_acces SET 
                        login = ?, 
                        email = ?, 
                        password_hash = MD5(CONCAT(?, ?))";
        $bindings = array(
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $email),
            array('type' => 's', 'value' => $login),
            array('type' => 's', 'value' => $password),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function is_existing_user_team($user_id, $team_id): bool
    {
        $sql = "SELECT * 
                FROM users_teams 
                WHERE user_id = ?
                  AND team_id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
            array('type' => 'i', 'value' => $team_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return count($results) > 0;
    }

    /**
     * @throws Exception
     */
    private function insert_user_team(int $user_id, $team_id): void
    {
        $sql = "INSERT INTO users_teams SET 
                        user_id = ?, 
                        team_id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
            array('type' => 'i', 'value' => $team_id),
        );
        $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * @throws Exception
     */
    private function delete_user_team(int $user_id, $team_id): void
    {
        $sql = "DELETE FROM users_teams WHERE 
                        user_id = ? AND 
                        team_id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
            array('type' => 'i', 'value' => $team_id),
        );
        $this->sql_manager->execute($sql, $bindings);
    }


    /**
     * @throws Exception
     */
    public function getUserTeams($user_id): array|int|string|null
    {
        $sql = file_get_contents(__DIR__ . '/../sql/get_user_teams.sql');
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Liste les équipes du club du responsable de club connecté, avec les
     * comptes responsables d'équipe qui leur sont rattachés (user_id null si
     * aucun compte). Sert à l'écran d'attribution des comptes.
     * @throws Exception
     */
    public function getMyClubTeamLeaders(): array
    {
        @session_start();
        if (!self::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        require_once __DIR__ . '/Club.php';
        $id_club = (new Club())->getMyClubId();
        $sql = "SELECT  e.id_equipe,
                        e.nom_equipe,
                        comp.libelle AS libelle_competition,
                        CONCAT(e.nom_equipe, IFNULL(CONCAT(' (', comp.libelle, ')'), '')) AS team_full_name,
                        (SELECT COUNT(DISTINCT cl.code_competition)
                           FROM classements cl
                          WHERE cl.id_equipe = e.id_equipe) AS nb_competitions,
                        (SELECT GROUP_CONCAT(DISTINCT CONCAT(cc.libelle, IFNULL(CONCAT(' ', cl.division), '')) ORDER BY cc.libelle SEPARATOR ', ')
                           FROM classements cl
                           JOIN competitions cc ON cc.code_competition = cl.code_competition
                          WHERE cl.id_equipe = e.id_equipe) AS competitions,
                        ca.id AS user_id,
                        ca.login,
                        ca.email
                FROM equipes e
                LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
                LEFT JOIN users_teams ut ON ut.team_id = e.id_equipe
                LEFT JOIN comptes_acces ca ON ca.id = ut.user_id
                WHERE e.id_club = ?
                ORDER BY comp.libelle, e.nom_equipe, ca.login";
        $bindings = array(array('type' => 'i', 'value' => $id_club));
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Crée (si besoin) et rattache un compte RESPONSABLE_EQUIPE à une équipe du
     * club du responsable connecté.
     * @throws Exception
     */
    public function attachClubTeamLeader($email, $id_equipe): void
    {
        @session_start();
        if (!self::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        if (empty($email)) {
            throw new Exception("L'adresse email est obligatoire !", 400);
        }
        require_once __DIR__ . '/Club.php';
        (new Club())->assertManagesTeam($id_equipe);
        $this->create_or_update_leader_account($email, $id_equipe);
    }

    /**
     * Détache un compte d'une équipe du club du responsable connecté
     * (supprime le lien users_teams, sans supprimer le compte).
     * @throws Exception
     */
    public function detachClubTeamLeader($user_id, $id_equipe): void
    {
        @session_start();
        if (!self::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        require_once __DIR__ . '/Club.php';
        (new Club())->assertManagesTeam($id_equipe);
        $this->delete_user_team($user_id, $id_equipe);
        $this->addActivity("Compte " . $this->getUserLogin($user_id) . " détaché de l'équipe " . $this->team->getTeamName($id_equipe));
    }

    /**
     * Pose en session l'identité et les rôles (cumulables) d'un compte :
     * admin (comptes_acces.is_admin), responsable d'équipe (users_teams),
     * responsable de club (users_clubs). Utilisé au login et par les
     * bascules « agir en tant que ».
     *
     * Un compte peut être rattaché à plusieurs clubs : la session porte la
     * liste complète (club_ids) et le club courant (id_club, le premier par
     * ordre alphabétique), que switchCurrentUserClub() fait ensuite varier.
     * @throws Exception
     */
    private function setSessionRoles(int $id_user, string $login, bool $is_admin, $id_equipe): void
    {
        $_SESSION['id_user'] = $id_user;
        $_SESSION['login'] = $login;
        $_SESSION['is_admin'] = $is_admin;
        $_SESSION['id_equipe'] = (int)$id_equipe;
        $_SESSION['is_team_leader'] = !empty($id_equipe);
        $sql = "SELECT  uc.club_id
                FROM users_clubs uc
                JOIN clubs c ON c.id = uc.club_id
                WHERE uc.user_id = ?
                ORDER BY c.nom";
        $bindings = array(array('type' => 'i', 'value' => $id_user));
        $club_ids = array_map('intval', array_column($this->sql_manager->execute($sql, $bindings), 'club_id'));
        if (count($club_ids) > 0) {
            $_SESSION['club_ids'] = $club_ids;
            $_SESSION['id_club'] = $club_ids[0];
            $_SESSION['is_club_leader'] = true;
        } else {
            unset($_SESSION['id_club']);
            $_SESSION['club_ids'] = array();
            $_SESSION['is_club_leader'] = false;
        }
    }

    /**
     * Bascule l'équipe courante : équipes rattachées au compte (users_teams) ou,
     * pour un responsable de club, n'importe quelle équipe de ses clubs — même
     * sans compte responsable rattaché. La sélection porte l'accès aux écrans
     * équipe (effectif, créneaux, coordonnées, matchs, messages).
     * @throws Exception
     */
    public function switchCurrentUserTeam($id_equipe): void
    {
        if (!(isset($_SESSION['login']))) {
            @session_start();
        }
        if (!(isset($_SESSION['login']))) {
            throw new Exception("Utilisateur non connecté !");
        }
        $id_equipe = (int)$id_equipe;
        // équipes rattachées au compte : on lit users_teams directement, et non
        // getUserTeams() qui passe par teams_view (JOIN competitions, donc sans
        // les équipes non engagées cette saison)
        $own_team_ids = array_map('intval', $this->getUserTeamIds((int)$_SESSION['id_user']));
        if (in_array($id_equipe, $own_team_ids, true)) {
            $_SESSION['id_equipe'] = $id_equipe;
            $_SESSION['is_team_leader'] = true;
            return;
        }
        if (self::isClubLeader()) {
            require_once __DIR__ . '/Club.php';
            $id_club = (new Club())->getClubIdOfManagedTeam($id_equipe);
            if ($id_club !== null) {
                $_SESSION['id_equipe'] = $id_equipe;
                $_SESSION['is_team_leader'] = true;
                // le club courant suit l'équipe choisie, pour que les écrans
                // club restent cohérents avec l'équipe en cours de gestion
                $_SESSION['id_club'] = $id_club;
                return;
            }
        }
        throw new Exception("Equipe non autorisée !", 403);
    }

    /**
     * Bascule le club courant d'un compte rattaché à plusieurs clubs.
     * @throws Exception
     */
    public function switchCurrentUserClub($id_club): void
    {
        @session_start();
        if (!isset($_SESSION['login'])) {
            throw new Exception("Utilisateur non connecté !");
        }
        require_once __DIR__ . '/Club.php';
        $club = new Club();
        $id_club = (int)$id_club;
        if (!in_array($id_club, $club->getMyClubIds(), true)) {
            throw new Exception("Club non autorisé !", 403);
        }
        $_SESSION['id_club'] = $id_club;
        // l'équipe courante est relâchée si elle n'appartient ni au compte ni au
        // nouveau club, pour ne pas gérer une équipe hors du club affiché
        $id_equipe = (int)($_SESSION['id_equipe'] ?? 0);
        if (empty($id_equipe)) {
            return;
        }
        $own_team_ids = array_map('intval', $this->getUserTeamIds((int)$_SESSION['id_user']));
        if (in_array($id_equipe, $own_team_ids, true)) {
            return;
        }
        if ($club->getClubIdOfManagedTeam($id_equipe) !== $id_club) {
            $_SESSION['id_equipe'] = null;
            $_SESSION['is_team_leader'] = false;
        }
    }

    /**
     * Équipes sélectionnables par le compte connecté : celles qui lui sont
     * rattachées (users_teams) et celles des clubs qu'il gère (users_clubs),
     * qu'un compte responsable y soit rattaché ou non.
     *
     * Les lignes d'equipes sans nom sont écartées : ce sont des créations
     * d'équipe avortées (aucun engagement, match, créneau, joueur ni compte),
     * qui ne donneraient qu'une entrée blanche et inexploitable.
     * @throws Exception
     */
    public function getMyManageableTeams(): array
    {
        @session_start();
        if (empty($_SESSION['id_user'])) {
            // 403 et non 401 : le routeur REST redirige les 401 vers le login,
            // ce qui ferait répondre du HTML à un appel axios
            throw new Exception("Utilisateur non connecté !", 403);
        }
        $id_user = (int)$_SESSION['id_user'];
        $where = "e.id_equipe IN (SELECT ut.team_id FROM users_teams ut WHERE ut.user_id = ?)";
        $club_ids = array();
        if (self::isClubLeader()) {
            require_once __DIR__ . '/Club.php';
            $club_ids = (new Club())->getMyClubIds();
            $placeholders = implode(',', array_fill(0, count($club_ids), '?'));
            $where .= " OR e.id_club IN ($placeholders)";
        }
        // mysqli lie les paramètres dans l'ordre d'apparition : le ? du SELECT
        // (is_my_team) précède ceux du WHERE
        $bindings = array(
            array('type' => 'i', 'value' => $id_user),
            array('type' => 'i', 'value' => $id_user),
        );
        foreach ($club_ids as $id_club) {
            $bindings[] = array('type' => 'i', 'value' => $id_club);
        }
        $sql = "SELECT  e.id_equipe,
                        e.nom_equipe,
                        e.id_club,
                        c.nom AS club_name,
                        comp.libelle AS libelle_competition,
                        CONCAT(e.nom_equipe, IFNULL(CONCAT(' (', comp.libelle, ')'), '')) AS team_full_name,
                        (SELECT COUNT(DISTINCT cl.code_competition)
                           FROM classements cl
                          WHERE cl.id_equipe = e.id_equipe) AS nb_competitions,
                        (SELECT GROUP_CONCAT(DISTINCT CONCAT(cc.libelle, IFNULL(CONCAT(' ', cl.division), '')) ORDER BY cc.libelle SEPARATOR ', ')
                           FROM classements cl
                           JOIN competitions cc ON cc.code_competition = cl.code_competition
                          WHERE cl.id_equipe = e.id_equipe) AS competitions,
                        EXISTS(SELECT 1 FROM users_teams ut2 WHERE ut2.team_id = e.id_equipe) AS has_leader_account,
                        EXISTS(SELECT 1 FROM users_teams ut3
                                WHERE ut3.team_id = e.id_equipe
                                  AND ut3.user_id = ?) AS is_my_team
                FROM equipes e
                LEFT JOIN clubs c ON c.id = e.id_club
                LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
                WHERE ($where)
                  AND NULLIF(e.nom_equipe, '') IS NOT NULL
                ORDER BY c.nom, comp.libelle, e.nom_equipe";
        return $this->sql_manager->execute($sql, $bindings);
    }

    /**
     * Met à jour les équipes associées à un utilisateur
     * @param int $user_id
     * @param string $team_ids Liste des IDs d'équipes séparés par des virgules
     * @throws Exception
     */
    public function updateUserTeams(int $user_id, string $team_ids): void
    {
        $new_team_ids = empty($team_ids) ? [] : array_map('intval', explode(',', $team_ids));
        $current_teams = $this->getUserTeamIds($user_id);
        $teams_to_add = array_diff($new_team_ids, $current_teams);
        $teams_to_remove = array_diff($current_teams, $new_team_ids);
        
        foreach ($teams_to_remove as $team_id) {
            $this->delete_user_team($user_id, $team_id);
        }
        
        foreach ($teams_to_add as $team_id) {
            $this->insert_user_team($user_id, $team_id);
        }
        
        $login = $this->getUserLogin($user_id);
        $this->addActivity("Mise à jour des équipes pour l'utilisateur $login");
    }

    /**
     * Récupère uniquement les IDs des équipes associées à un utilisateur
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getUserTeamIds(int $user_id): array
    {
        $sql = "SELECT team_id FROM users_teams WHERE user_id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return array_column($results, 'team_id');
    }

    /**
     * Récupère les IDs des clubs gérés par un utilisateur (users_clubs)
     * @throws Exception
     */
    public function getUserClubIds(int $user_id): array
    {
        $sql = "SELECT club_id FROM users_clubs WHERE user_id = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $user_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return array_map('intval', array_column($results, 'club_id'));
    }

    /**
     * Met à jour les clubs gérés par un utilisateur (rôle responsable de club,
     * dérivé de users_clubs).
     * @param string $club_ids Liste des IDs de clubs séparés par des virgules
     * @throws Exception
     */
    public function updateUserClubs(int $user_id, string $club_ids): void
    {
        if (!self::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent faire ça !", 403);
        }
        $new_club_ids = empty($club_ids) ? [] : array_map('intval', explode(',', $club_ids));
        $current_clubs = $this->getUserClubIds($user_id);

        foreach (array_diff($current_clubs, $new_club_ids) as $club_id) {
            $this->sql_manager->execute(
                "DELETE FROM users_clubs WHERE user_id = ? AND club_id = ?",
                array(
                    array('type' => 'i', 'value' => $user_id),
                    array('type' => 'i', 'value' => $club_id),
                ));
        }

        foreach (array_diff($new_club_ids, $current_clubs) as $club_id) {
            $this->sql_manager->execute(
                "INSERT INTO users_clubs SET user_id = ?, club_id = ?",
                array(
                    array('type' => 'i', 'value' => $user_id),
                    array('type' => 'i', 'value' => $club_id),
                ));
        }

        $login = $this->getUserLogin($user_id);
        $this->addActivity("Mise à jour des clubs gérés pour l'utilisateur $login");
    }

    /**
     * Permet à un administrateur d'agir en tant qu'un autre utilisateur
     * @param int $target_user_id ID de l'utilisateur cible
     * @return bool
     * @throws Exception
     */
    public function switch_to_user(int $target_user_id): bool
    {
        @session_start();
        
        if (!self::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent utiliser cette fonctionnalité");
        }
        
        if (self::is_acting_as()) {
            throw new Exception("Vous êtes déjà en mode 'Agir en tant que'. Revenez d'abord à votre compte admin.");
        }
        
        $target_user = $this->get_by_id($target_user_id);
        if (!$target_user) {
            throw new Exception("Utilisateur cible introuvable");
        }
        
        $this->saveOriginalSessionRoles();

        $sql = "SELECT  ut.team_id AS id_equipe,
                        ca.login,
                        ca.id AS id_user,
                        ca.is_admin
                FROM comptes_acces ca
                LEFT JOIN users_teams ut ON ca.id = ut.user_id
                WHERE ca.id = ?
                LIMIT 1";
        $bindings = array(
            array('type' => 'i', 'value' => $target_user_id),
        );
        $results = $this->sql_manager->execute($sql, $bindings);

        if (count($results) === 0) {
            throw new Exception("Impossible de récupérer les informations de l'utilisateur cible");
        }

        $data = $results[0];
        $this->setSessionRoles((int)$data['id_user'], $data['login'], !empty($data['is_admin']), $data['id_equipe']);
        $_SESSION['acting_as'] = true;

        $this->activity->add("Admin a basculé vers le compte: " . $data['login'], $_SESSION['original_admin_id']);

        return true;
    }

    /**
     * Sauvegarde en session l'identité et les rôles du compte d'origine
     * avant une bascule « agir en tant que ».
     */
    private function saveOriginalSessionRoles(): void
    {
        $_SESSION['original_admin_id'] = $_SESSION['id_user'];
        $_SESSION['original_admin_login'] = $_SESSION['login'];
        $_SESSION['original_admin_is_admin'] = !empty($_SESSION['is_admin']);
        $_SESSION['original_admin_is_team_leader'] = !empty($_SESSION['is_team_leader']);
        $_SESSION['original_admin_is_club_leader'] = !empty($_SESSION['is_club_leader']);
        $_SESSION['original_admin_equipe'] = $_SESSION['id_equipe'] ?? null;
        $_SESSION['original_admin_club'] = $_SESSION['id_club'] ?? null;
        $_SESSION['original_admin_club_ids'] = $_SESSION['club_ids'] ?? array();
    }

    /**
     * Permet à un responsable de club d'agir en tant qu'un compte responsable
     * d'équipe de SON club (réutilise le mécanisme "agir en tant que").
     * @param int $target_user_id ID du compte responsable d'équipe cible
     * @return bool
     * @throws Exception
     */
    public function switch_to_club_team_leader(int $target_user_id): bool
    {
        @session_start();

        if (!self::isClubLeader()) {
            throw new Exception("Seul un responsable de club peut faire ça !", 403);
        }
        if (self::is_acting_as()) {
            throw new Exception("Vous agissez déjà en tant qu'un autre compte. Revenez d'abord à votre compte club.");
        }

        require_once __DIR__ . '/Club.php';
        $club = new Club();
        $clubTeamIds = array_map('intval', array_column($club->getMyClubTeams(), 'id_equipe'));
        $targetTeamIds = array_map('intval', $this->getUserTeamIds($target_user_id));
        $sharedTeamIds = array_values(array_intersect($targetTeamIds, $clubTeamIds));
        if (count($sharedTeamIds) === 0) {
            throw new Exception("Ce compte ne gère aucune équipe de votre club !", 403);
        }

        // sauvegarde de la session du responsable de club
        $this->saveOriginalSessionRoles();

        $sql = "SELECT  ca.login,
                        ca.id AS id_user,
                        ca.is_admin
                FROM comptes_acces ca
                WHERE ca.id = ?
                LIMIT 1";
        $bindings = array(array('type' => 'i', 'value' => $target_user_id));
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) === 0) {
            throw new Exception("Compte cible introuvable !");
        }
        $data = $results[0];
        // on cible une équipe du club (la première partagée), pour que toute l'UI
        // responsable opère sur une équipe du club même si le compte en gère d'autres
        $this->setSessionRoles((int)$data['id_user'], $data['login'], !empty($data['is_admin']), $sharedTeamIds[0]);
        $_SESSION['acting_as'] = true;

        $this->activity->add("Responsable de club a basculé vers le compte: " . $data['login'], $_SESSION['original_admin_id']);

        return true;
    }

    /**
     * Retourne au compte administrateur original
     * @return bool
     * @throws Exception
     */
    public function switch_back_to_admin(): bool
    {
        @session_start();
        
        if (!self::is_acting_as()) {
            throw new Exception("Vous n'êtes pas en mode 'Agir en tant que'");
        }
        
        $target_login = $_SESSION['login'];

        $_SESSION['id_user'] = $_SESSION['original_admin_id'];
        $_SESSION['login'] = $_SESSION['original_admin_login'];
        $_SESSION['is_admin'] = !empty($_SESSION['original_admin_is_admin']);
        $_SESSION['is_team_leader'] = !empty($_SESSION['original_admin_is_team_leader']);
        $_SESSION['is_club_leader'] = !empty($_SESSION['original_admin_is_club_leader']);
        $_SESSION['id_equipe'] = $_SESSION['original_admin_equipe'];
        if (!empty($_SESSION['original_admin_club'])) {
            $_SESSION['id_club'] = (int)$_SESSION['original_admin_club'];
        } else {
            unset($_SESSION['id_club']);
        }
        $_SESSION['club_ids'] = $_SESSION['original_admin_club_ids'] ?? array();

        unset($_SESSION['acting_as']);
        unset($_SESSION['original_admin_id']);
        unset($_SESSION['original_admin_login']);
        unset($_SESSION['original_admin_is_admin']);
        unset($_SESSION['original_admin_is_team_leader']);
        unset($_SESSION['original_admin_is_club_leader']);
        unset($_SESSION['original_admin_equipe']);
        unset($_SESSION['original_admin_club']);
        unset($_SESSION['original_admin_club_ids']);

        $this->activity->add("Compte d'origine restauré depuis: " . $target_login);

        return true;
    }

    /**
     * Vérifie si l'utilisateur actuel est en mode "Agir en tant que"
     * @return bool
     */
    public static function is_acting_as(): bool
    {
        @session_start();
        return isset($_SESSION['acting_as']) && $_SESSION['acting_as'] === true;
    }

    /**
     * Retourne les informations de l'administrateur original si en mode "Agir en tant que"
     * @return array|null
     */
    public static function get_original_admin(): ?array
    {
        @session_start();
        
        if (!self::is_acting_as()) {
            return null;
        }
        
        return array(
            'id_user' => $_SESSION['original_admin_id'],
            'login' => $_SESSION['original_admin_login'],
            'is_admin' => !empty($_SESSION['original_admin_is_admin']),
            'is_team_leader' => !empty($_SESSION['original_admin_is_team_leader']),
            'is_club_leader' => !empty($_SESSION['original_admin_is_club_leader']),
            'id_equipe' => $_SESSION['original_admin_equipe'],
        );
    }

    /**
     * Retourne la liste des utilisateurs disponibles pour "Agir en tant que"
     * @return array
     * @throws Exception
     */
    public function get_users_for_act_as(): array
    {
        @session_start();
        
        if (!self::isAdmin()) {
            throw new Exception("Seuls les administrateurs peuvent utiliser cette fonctionnalité");
        }
        
        $sql = "SELECT  ca.id,
                        ca.login,
                        ca.email,
                        ca.is_admin,
                        GROUP_CONCAT(e.nom_equipe SEPARATOR ', ') AS equipes
                FROM comptes_acces ca
                LEFT JOIN users_teams ut ON ut.user_id = ca.id
                LEFT JOIN equipes e ON e.id_equipe = ut.team_id
                WHERE ca.id != ?
                GROUP BY ca.id, ca.login, ca.email, ca.is_admin
                ORDER BY ca.login";
        $bindings = array(
            array('type' => 'i', 'value' => $_SESSION['id_user']),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }


}