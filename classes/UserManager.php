<?php

require_once __DIR__ . '/Generic.php';

class UserManager extends Generic
{
    /**
     * @return array
     * @throws Exception
     */
    function getMyPreferences()
    {
        $db = Database::openDbConnection();
        $userDetails = $this->getCurrentUserDetails();
        $user_id = $userDetails['id_user'];
        $sql = "SELECT r.registry_value AS is_remind_matches 
                FROM registry r
                WHERE r.registry_key = 'users.$user_id.is_remind_matches'";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function modifyMyPassword()
    {
        $db = Database::openDbConnection();
        $userDetails = $this->getCurrentUserDetails();
        $id_team = $userDetails['id_equipe'];
        $password = filter_input(INPUT_POST, 'new_password');
        $passwordAgain = filter_input(INPUT_POST, 'new_password_again');
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
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            return false;
        }
        $this->addActivity("Mot de passe modifie");
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function saveMyPreferences()
    {
        $db = Database::openDbConnection();
        $userDetails = $this->getCurrentUserDetails();
        $id_user = $userDetails['id_user'];
        $inputs = array(
            'is_remind_matches' => filter_input(INPUT_POST, 'is_remind_matches')
        );
        $is_remind_matches = $inputs['is_remind_matches'];
        if ($this->isRegistryKeyPresent("users.$id_user.is_remind_matches")) {
            $sql = "UPDATE registry 
                    SET registry_value = '$is_remind_matches' 
                    WHERE registry_key = 'users.$id_user.is_remind_matches'";
        } else {
            $sql = "INSERT INTO registry 
                    SET registry_value = '$is_remind_matches', 
                        registry_key = 'users.$id_user.is_remind_matches'";
        }
        $req = mysqli_query($db, $sql);
        if ($req === FALSE) {
            return false;
        }
        $this->addActivity("Mot de passe modifie");
        return true;
    }

    /**
     * @param $key
     * @return bool
     * @throws Exception
     */
    private function isRegistryKeyPresent($key)
    {
        $db = Database::openDbConnection();
        $sql = "SELECT COUNT(*) AS cnt FROM registry WHERE registry_key = '$key'";
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }
}