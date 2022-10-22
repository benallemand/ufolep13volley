<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/Photo.php';
require_once __DIR__ . '/UserManager.php';

class Team extends Generic
{
    private Photo $photo;

    public function __construct()
    {
        parent::__construct();
        $this->photo = new Photo();
        $this->table_name = 'equipes';
        $this->id_name = 'id_equipe';
    }

    public function getSql($query = "1=1"): string
    {
        return "SELECT 
        e.code_competition, 
        comp.libelle AS libelle_competition, 
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        TO_BASE64(CONCAT(jresp.prenom, ' ', jresp.nom)) AS responsable_base64,
        jresp.telephone AS telephone_1,
        TO_BASE64(jresp.telephone) AS telephone_1_base64,
        jsupp.telephone AS telephone_2,
        TO_BASE64(jsupp.telephone) AS telephone_2_base64,
        jresp.email,
        TO_BASE64(jresp.email) AS email_base64,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE $query
        GROUP BY team_full_name, comp.libelle, c.nom, nom_equipe
        ORDER BY comp.libelle, c.nom, nom_equipe";
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getTeams($query = "1=1"): array
    {
        $sql = $this->getSql($query);
        return $this->sql_manager->execute($sql);
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function getTeam($id)
    {
        $results = $this->getTeams("e.id_equipe = $id");
        if (count($results) < 1) {
            throw new Exception("Error while retrieving team data");
        }
        return $results[0];
    }

    /**
     * @param $id_team
     * @return string
     * @throws Exception
     */
    public function getTeamSheet($id_team)
    {
        if ($id_team === NULL) {
            throw new Exception("Id d'équipe est vide !");
        }
        if (!$this->isTeamSheetAllowedForUser($id_team)) {
            throw new Exception("Vous n'avez pas la permission de télécharger cette fiche équipe !");
        }
        $sql = "SELECT 
        c.nom AS club,
        e.code_competition, 
        comp.libelle AS championnat,
        cla.division,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS leader,
        jresp.telephone AS portable,
        jresp.email AS courriel,
        GROUP_CONCAT(CONCAT(LEFT(cr.jour, 2), ' ', cr.heure, ' ', g.nom) SEPARATOR '\n') AS gymnasiums_list,
        e.nom_equipe AS equipe,
        DATE_FORMAT(NOW(), '%d/%m/%Y') AS date_visa_ctsd
        FROM equipes e
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN classements cla ON cla.code_competition=e.code_competition AND cla.id_equipe=e.id_equipe
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe = ?
        GROUP BY equipe";
        $bindings = array(
            array('type' => 'i', 'value' => $id_team),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        if (count($results) === 0) {
            throw new Exception("Fiche équipe non trouvée !");
        }
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function rename_registered_teams()
    {
        if (!UserManager::isAdmin()) {
            throw new Exception("Seul un admin peut faire cette action !");
        }
        $sql = "UPDATE equipes e
                JOIN register r on e.id_equipe = r.old_team_id
                SET e.nom_equipe = r.new_team_name";
        $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function create_team($code_competition, $team_name, $id_club)
    {
        $sql = "INSERT INTO equipes 
                SET code_competition = ?,
                    nom_equipe = ?,
                    id_club = ?";
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $team_name),
            array('type' => 'i', 'value' => $id_club),
        );
        return $this->sql_manager->execute($sql, $bindings);
    }

    public function getQuickDetails($idEquipe)
    {
        $sql = "SELECT 
            e.code_competition, 
            comp.libelle AS libelle_competition, 
            e.nom_equipe, 
            CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
            e.id_club,
            c.nom AS club,
            e.id_equipe,
            CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
            jresp.telephone AS telephone_1,
            jsupp.telephone AS telephone_2,
            jresp.email,
            GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR '\n') AS gymnasiums_list,
            e.web_site,
            p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE e.id_equipe=$idEquipe        
        GROUP BY e.code_competition, 
                 comp.libelle,
                 e.nom_equipe,
                 CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')), 
                 e.id_club, 
                 c.nom, 
                 e.id_equipe, 
                 CONCAT(jresp.prenom, ' ', jresp.nom), 
                 jresp.telephone, 
                 jsupp.telephone, 
                 jresp.email, 
                 e.web_site, 
                 p.path_photo 
        ORDER BY comp.libelle, 
                 c.nom, 
                 nom_equipe";
        $results = $this->sql_manager->execute($sql);
        return $results[0];
    }

    public function getRankTeams()
    {
        $sql = "SELECT 
        cl.code_competition, 
        cl.division,
        comp.libelle AS libelle_competition,
        e.nom_equipe, 
        CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
        e.id_club,
        c.nom AS club,
        e.id_equipe,
        CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
        jresp.telephone AS telephone_1,
        jsupp.telephone AS telephone_2,
        jresp.email,
        GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=cl.code_competition
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        GROUP BY cl.code_competition, 
                 cl.division, 
                 comp.libelle, 
                 e.nom_equipe, 
                 CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')), 
                 e.id_club, 
                 c.nom, 
                 e.id_equipe, 
                 CONCAT(jresp.prenom, ' ', jresp.nom), 
                 jresp.telephone, 
                 jsupp.telephone, 
                 jresp.email, 
                 e.web_site, 
                 p.path_photo
        ORDER BY comp.libelle, c.nom, nom_equipe";
        return $this->sql_manager->execute($sql);
    }

    public function getWebSites()
    {
        $sql = "SELECT DISTINCT c.nom AS nom_club, e.web_site 
        FROM equipes e
        JOIN clubs c ON c.id = e.id_club
        WHERE web_site != ''
        ORDER BY c.nom ";
        return $this->sql_manager->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function saveTeam($web_site = null, $id_club = null, $id_equipe = null)
    {
        $bindings = array();
        $inputs = array(
            'web_site' => $web_site,
            'id_club' => $id_club,
            'id_equipe' => $id_equipe
        );
        if (UserManager::isTeamLeader()) {
            $inputs['id_equipe'] = $_SESSION['id_equipe'];
        }
        if (empty($inputs['id_equipe'])) {
            $sql = "INSERT INTO";
        } else {
            $sql = "UPDATE";
        }
        $sql .= " equipes SET ";
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'id_equipe':
                case 'dirtyFields':
                    break;
                case 'id_club':
                    $bindings[] = array(
                        'type' => 'i',
                        'value' => $value
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
        if (!empty($inputs['id_equipe'])) {
            $bindings[] = array(
                'type' => 'i',
                'value' => $inputs['id_equipe']
            );
            $sql .= " WHERE id_equipe = ?";
        }
        $this->sql_manager->execute($sql, $bindings);
        if (!empty($inputs['id_equipe'])) {
            $this->saveTeamPhoto($inputs['id_equipe']);
        }
    }

    /**
     * @param $idTeam
     * @throws Exception
     */
    function saveTeamPhoto($idTeam)
    {
        $team = $this->getTeam($idTeam);
        if (empty($_FILES['photo']['name'])) {
            return;
        }
        $uploaddir = __DIR__ . '/../teams_pics/';
        $uploadfile = "$uploaddir$idTeam.jpg";
        $idPhoto = $this->photo->insertPhoto(substr($uploadfile, 3));
        $this->linkTeamToPhoto($idTeam, $idPhoto);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadfile)) {
            $this->addActivity("Une nouvelle photo a ete transmise pour l'équipe " . $team['team_full_name']);
        }
    }

    /**
     * @param $idTeam
     * @param $idPhoto
     * @throws Exception
     */
    public function linkTeamToPhoto($idTeam, $idPhoto)
    {
        $sql = "UPDATE equipes e SET e.id_photo = $idPhoto WHERE id_equipe = $idTeam";
        $this->sql_manager->execute($sql);
    }

    public function getIdClubFromIdTeam($idTeam)
    {
        $sql = "SELECT 
        e.id_club
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['id_club'];
    }

    public function getTeamName($idTeam)
    {
        if ($idTeam === 0) {
            return 'Non renseigné';
        }
        $sql = "SELECT 
        CONCAT(e.nom_equipe, '(',e.code_competition,')') AS team_name 
        FROM equipes e 
        WHERE e.id_equipe = $idTeam";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['team_name'];
    }

    public function getMyTeamIdClub()
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sql = "SELECT 
        e.id_club
        FROM equipes e
        WHERE e.id_equipe=$sessionIdEquipe";
        $results = $this->sql_manager->execute($sql);
        return $results[0]['id_club'];
    }

    public function getMyTeam()
    {
        if (UserManager::isAdmin()) {
            return false;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        $sessionIdEquipe = $_SESSION['id_equipe'];
        $sql = "SELECT 
                    e.code_competition, 
                    comp.libelle AS libelle_competition, 
                    e.nom_equipe, 
                    CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')) AS team_full_name,
                    e.id_club,
                    c.nom AS club,
                    e.id_equipe,
                    CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
                    TO_BASE64(CONCAT(jresp.prenom, ' ', jresp.nom)) AS responsable_base64,
                    jresp.telephone AS telephone_1,
                    TO_BASE64(jresp.telephone) AS telephone_1_base64,
                    jsupp.telephone AS telephone_2,
                    TO_BASE64(jsupp.telephone) AS telephone_2_base64,
                    jresp.email,
                    TO_BASE64(jresp.email) AS email_base64,
                    GROUP_CONCAT(CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
                    e.web_site,
                    p.path_photo
                FROM equipes e 
                LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
                LEFT JOIN photos p ON p.id = e.id_photo
                JOIN clubs c ON c.id=e.id_club
                JOIN competitions comp ON comp.code_competition=e.code_competition
                LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
                LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
                LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
                LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
                LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
                LEFT JOIN gymnase g ON g.id=cr.id_gymnase
                WHERE e.id_equipe=$sessionIdEquipe        
                GROUP BY e.code_competition, 
                         comp.libelle,
                         e.nom_equipe,
                         CONCAT(e.nom_equipe, ' (', c.nom, ') (', comp.libelle, ')', IFNULL(CONCAT('(', cl.division, ')'), '')),
                         e.id_club, 
                         c.nom, 
                         e.id_equipe, 
                         CONCAT(jresp.prenom, ' ', jresp.nom), 
                         jresp.telephone, 
                         jsupp.telephone,
                         jresp.email, 
                         e.web_site, 
                         p.path_photo
                ORDER BY comp.libelle, 
                         c.nom,
                         nom_equipe";
        return $this->sql_manager->execute($sql);
    }

    public function getTeamEmail($id)
    {
        $sql = "SELECT j.email 
        FROM joueurs j 
        JOIN joueur_equipe je ON 
                                je.id_equipe = $id 
                                AND je.id_joueur = j.id 
                                AND je.is_leader+0 > 0";
        $results = $this->sql_manager->execute($sql);
        if (count($results) != 1) {
            throw new Exception("Impossible de récupérer l'email du responsable d'équipe !");
        }
        $data = $results[0];
        return $data['email'];
    }

    public function isSameRankingTable($id_equipe)
    {
        $sessionIdEquipe = $_SESSION['id_equipe'];
        if ($sessionIdEquipe == $id_equipe) {
            return true;
        }
        $sql = "SELECT * FROM matches 
        WHERE 
              match_status = 'CONFIRMED'
              AND(id_equipe_dom=$sessionIdEquipe 
                  OR id_equipe_ext=$sessionIdEquipe)";
        $results = $this->sql_manager->execute($sql);
        foreach ($results as $result) {
            if ($result['id_equipe_dom'] == $id_equipe) {
                return true;
            }
            if ($result['id_equipe_ext'] == $id_equipe) {
                return true;
            }
        }
        return false;
    }

    public function isTeamSheetAllowedForUser($idTeam)
    {
        if (UserManager::isAdmin()) {
            return true;
        }
        if (!UserManager::isTeamLeader()) {
            return false;
        }
        return $this->isSameRankingTable($idTeam);
    }

    /**
     * @throws Exception
     */
    public function team_exists(string $code_competition, string $new_team_name, int $id_club): bool
    {
        $sql = "SELECT * 
                FROM equipes 
                WHERE code_competition = ?
                AND nom_equipe = ?
                AND id_club = ?";
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $new_team_name),
            array('type' => 'i', 'value' => $id_club),
        );
        return count($this->sql_manager->execute($sql, $bindings)) > 0;
    }

    public function get_by_name(string $code_competition, string $new_team_name, int $id_club)
    {
        $sql = "SELECT * 
                FROM equipes 
                WHERE code_competition = ?
                AND nom_equipe = ?
                AND id_club = ?";
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $new_team_name),
            array('type' => 'i', 'value' => $id_club),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return $results[0];
    }


}