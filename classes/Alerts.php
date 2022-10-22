<?php
require_once __DIR__ . '/Generic.php';

class Alerts extends Generic
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getAlerts()
    {
        $results = array();
        if (UserManager::isAdmin()) {
            return $results;
        }
        if (!UserManager::isTeamLeader()) {
            return $results;
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sessionLogin = $_SESSION['login'];
        if (!$this->hasEnoughPlayers($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas assez de joueurs dans l'équipe",
                'criticity' => 'error',
                'expected_action' => 'showHelpAddPlayer'
            );
        }
        if (!$this->hasEnoughWomen($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas assez de filles dans l'équipe",
                'criticity' => 'error',
                'expected_action' => 'showHelpAddPlayer'
            );
        }
        if (!$this->hasEnoughMen($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas assez de garçons dans l'équipe",
                'criticity' => 'error',
                'expected_action' => 'showHelpAddPlayer'
            );
        }
        if (!$this->hasLeader($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Responsable d'équipe non défini",
                'criticity' => 'error',
                'expected_action' => 'showHelpSelectLeader'
            );
        }
        if (!$this->hasViceLeader($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Responsable suppléant d'équipe non défini",
                'criticity' => 'warning',
                'expected_action' => 'showHelpSelectViceLeader'
            );
        }
        if (!$this->hasCaptain($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Capitaine d'équipe non défini",
                'criticity' => 'error',
                'expected_action' => 'showHelpSelectCaptain'
            );
        }
        if (!$this->hasTimeSlot($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas de gymnase de réception",
                'criticity' => 'info',
                'expected_action' => 'showHelpSelectTimeSlot'
            );
        }
        if (!$this->hasAnyPhone($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas de numéro de téléphone",
                'criticity' => 'error',
                'expected_action' => 'showHelpAddPhoneNumber'
            );
        }
        if (!$this->hasAnyEmail($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Pas d'email",
                'criticity' => 'error',
                'expected_action' => 'showHelpAddEmail'
            );
        }
        if ($this->hasInactivePlayers($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Joueurs inactifs",
                'criticity' => 'info',
                'expected_action' => 'showHelpInactivePlayers'
            );
        }
        if ($this->hasNotLicencedPlayers($sessionIdEquipe)) {
            $results[] = array(
                'owner' => $sessionLogin,
                'issue' => "Joueurs sans licence",
                'criticity' => 'error',
                'expected_action' => 'showHelpPlayersWithoutLicenceNumber'
            );
        }
        return $results;
    }

    public function hasNotLicencedPlayers($sessionIdEquipe)
    {
        $sql = "SELECT 
        COUNT(*) AS cnt 
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND (j.num_licence = '' OR j.num_licence IS NULL)";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasEnoughPlayers($sessionIdEquipe)
    {
        $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        WHERE je.id_equipe = $sessionIdEquipe";
        $results = $this->sql_manager->execute($sql);
        $minCount = 0;
        switch ($results[0]['code_competition']) {
            case 'm':
            case 'c':
            case 'cf':
                $minCount = 6;
                break;
            case 'mo':
            case 'f':
            case 't':
            case 'ff':
            case 'kh':
            case 'kf':
                $minCount = 4;
                break;
            default:
                break;
        }
        if (intval($results[0]['cnt']) < $minCount) {
            return false;
        }
        return true;
    }

    public function hasInactivePlayers($sessionIdEquipe)
    {
        $sql = "SELECT 
        COUNT(*) AS cnt 
        FROM joueur_equipe je 
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.est_actif+0 = 0";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasEnoughWomen($sessionIdEquipe)
    {
        $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.sexe = 'F'";
        $results = $this->sql_manager->execute($sql);
        $minCount = 0;
        switch ($results[0]['code_competition']) {
            case 'm':
            case 'c':
            case 'cf':
                $minCount = 0;
                break;
            case 'f':
            case 't':
            case 'ff':
                $minCount = 4;
                break;
            case 'kh':
            case 'kf':
                $minCount = 2;
                break;
            case 'mo':
                $minCount = 1;
                break;
            default:
                break;
        }
        if (intval($results[0]['cnt']) < $minCount) {
            return false;
        }
        return true;
    }

    public function hasEnoughMen($sessionIdEquipe)
    {
        $sql = "SELECT 
        COUNT(*) AS cnt, 
        e.code_competition
        FROM joueur_equipe je 
        JOIN equipes e ON e.id_equipe = je.id_equipe
        JOIN joueurs j ON j.id = je.id_joueur
        WHERE 
        je.id_equipe = $sessionIdEquipe
        AND j.sexe = 'M'";
        $results = $this->sql_manager->execute($sql);
        $minCount = 0;
        switch ($results[0]['code_competition']) {
            case 'm':
            case 'c':
            case 'cf':
            case 'f':
            case 't':
            case 'ff':
            case 'kh':
            case 'kf':
                $minCount = 0;
                break;
            case 'mo':
                $minCount = 1;
                break;
            default:
                break;
        }
        if (intval($results[0]['cnt']) < $minCount) {
            return false;
        }
        return true;
    }

    public function hasLeader($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_leader+0 > 0";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasViceLeader($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_vice_leader+0 > 0";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasCaptain($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe WHERE id_equipe = $sessionIdEquipe AND is_captain+0 > 0";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasTimeSlot($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM creneau WHERE id_equipe = $sessionIdEquipe";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasAnyPhone($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe je
        JOIN joueurs j ON j.id=je.id_joueur AND (
            (j.telephone IS NOT NULL AND j.telephone != '')
            OR 
            (j.telephone2 IS NOT NULL AND j.telephone2 != '')
            )
        WHERE je.id_equipe = $sessionIdEquipe 
        AND (je.is_leader+0 > 0 OR je.is_vice_leader+0 > 0)";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }

    public function hasAnyEmail($sessionIdEquipe)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM joueur_equipe je
        JOIN joueurs j ON j.id=je.id_joueur AND (
            (j.email IS NOT NULL AND j.email != '')
            OR 
            (j.email2 IS NOT NULL AND j.email2 != '')
        )
        WHERE je.id_equipe = $sessionIdEquipe 
        AND (je.is_leader+0 > 0 OR je.is_vice_leader+0 > 0)";
        $results = $this->sql_manager->execute($sql);
        if (intval($results[0]['cnt']) === 0) {
            return false;
        }
        return true;
    }


}