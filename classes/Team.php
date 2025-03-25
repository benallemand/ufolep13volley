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
        CONCAT(e.nom_equipe, ' (', c.nom, ') - ', GROUP_CONCAT(DISTINCT CONCAT(comp.libelle, IFNULL(CONCAT('(', cl.division, ')'), '')) ORDER BY comp.libelle)) AS team_full_name,
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
        GROUP_CONCAT(DISTINCT CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        e.id_photo,
        p.path_photo,
        e.is_cup_registered,
        IF(cl.id IS NULL, 0, 1) AS is_active_team
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=IFNULL(cl.code_competition, e.code_competition)
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueur_equipe jesupp ON jesupp.id_equipe=e.id_equipe AND jesupp.is_vice_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN joueurs jsupp ON jsupp.id=jesupp.id_joueur
        LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
        LEFT JOIN gymnase g ON g.id=cr.id_gymnase
        WHERE $query
        GROUP BY id_equipe, nom_equipe
        ORDER BY nom_equipe";
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
    public function getTeam($id): mixed
    {
        $results = $this->getTeams("e.id_equipe = $id");
        if (count($results) < 1) {
            throw new Exception("Erreur pendant la récupération des données de l'équipe !");
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
        LEFT JOIN classements cla ON cla.code_competition=e.code_competition AND cla.id_equipe=e.id_equipe
        JOIN competitions comp ON comp.code_competition=IFNULL(cla.code_competition, e.code_competition)
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
            GROUP_CONCAT(DISTINCT CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR '\n') AS gymnasiums_list,
            e.web_site,
            p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=IFNULL(cl.code_competition, e.code_competition)
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
        GROUP_CONCAT(DISTINCT CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
        e.web_site,
        p.path_photo
        FROM equipes e 
        LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
        LEFT JOIN photos p ON p.id = e.id_photo
        JOIN clubs c ON c.id=e.id_club
        JOIN competitions comp ON comp.code_competition=IFNULL(cl.code_competition, e.code_competition)
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
    public function saveTeam(
        $web_site = null,
        $id_club = null,
        $id_equipe = null,
        $is_cup_registered = null,
        $dirtyFields = null,
        $code_competition = null,
        $nom_equipe = null)
    {
        $bindings = array();
        $inputs = array();
        if (!is_null($web_site)) {
            $inputs['web_site'] = $web_site;
        }
        if (!is_null($id_club)) {
            $inputs['id_club'] = $id_club;
        }
        if (!is_null($id_equipe)) {
            $inputs['id_equipe'] = $id_equipe;
        }
        if (!is_null($is_cup_registered)) {
            $inputs['is_cup_registered'] = $is_cup_registered;
        }
        if (!is_null($dirtyFields)) {
            $inputs['dirtyFields'] = $dirtyFields;
        }
        if (!is_null($code_competition)) {
            $inputs['code_competition'] = $code_competition;
        }
        if (!is_null($nom_equipe)) {
            $inputs['nom_equipe'] = $nom_equipe;
        }
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
                case 'is_cup_registered':
                    $val = ($value === 'on' || $value === 1) ? 1 : 0;
                    $bindings[] = array('type' => 'i', 'value' => $val);
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
        $uploaddir = 'teams_pics/';
        $uploadfile = "$uploaddir$idTeam.jpg";
        $idPhoto = $this->photo->insertPhoto($uploadfile);
        $this->linkTeamToPhoto($idTeam, $idPhoto);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../' . $uploadfile)) {
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
            throw new Exception("Un administrateur ne peut pas faire ça !");
        }
        if (!UserManager::isTeamLeader()) {
            throw new Exception("Seul un responsable d'équipe peut faire ça !");
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
                    GROUP_CONCAT(DISTINCT CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')', IF(cr.has_time_constraint > 0, ' (CONTRAINTE HORAIRE FORTE)', '')) SEPARATOR ', ') AS gymnasiums_list,
                    e.web_site,
                    p.path_photo
                FROM equipes e 
                LEFT JOIN classements cl ON cl.id_equipe = e.id_equipe
                LEFT JOIN photos p ON p.id = e.id_photo
                JOIN clubs c ON c.id=e.id_club
                JOIN competitions comp ON comp.code_competition=IFNULL(cl.code_competition, e.code_competition)
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

    /**
     * @throws Exception
     */
    public function get_by_name(string $code_competition, string $new_team_name, int $id_club)
    {
        $sql = $this->getSql("e.code_competition = ?
                                    AND e.nom_equipe = ?
                                    AND e.id_club = ?");
        $bindings = array(
            array('type' => 's', 'value' => $code_competition),
            array('type' => 's', 'value' => $new_team_name),
            array('type' => 'i', 'value' => $id_club),
        );
        $results = $this->sql_manager->execute($sql, $bindings);
        return $results[0];
    }

    /**
     * @throws Exception
     */
    public function download_calendar($id=null)
    {
        require_once __DIR__ . '/MatchMgr.php';
        $match = new MatchMgr();
        if (empty($id)) {
            @session_start();
            if (!empty($_SESSION['id_equipe'])) {
                $id = $_SESSION['id_equipe'];
            } else {
                throw new Exception("Utilisateur non connecté ou non associé à une équipe !");
            }
        }
        $matches = $match->get_matches("(id_equipe_dom = $id OR id_equipe_ext = $id) AND match_status NOT IN ('ARCHIVED')");
        if (count($matches) == 0) {
            throw new Exception("Il n'y a pas de match pour cette compétition/division !");
        }
        $delimiter = ";";
        $filename = "matchs.csv";
        // Create a file pointer
        $f = fopen('php://memory', 'w');
        //add BOM to fix UTF-8 in Excel
        fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        // Set column headers
        function filter_hidden_csv_fields($var): bool
        {
            return in_array($var, array(
                'code_match',
                'libelle_competition',
                'division',
                'journee',
                'equipe_dom',
                'equipe_ext',
                'heure_reception',
                'gymnasium',
                'date_reception',
                'note',
                'report_status',
                'match_status',
                'email_dom',
                'email_ext',
            ));
        }
        $fields = array_keys(array_filter($matches[0], 'filter_hidden_csv_fields', ARRAY_FILTER_USE_KEY));
        fputcsv($f, $fields, $delimiter);
        // Output each row of the data, format line as csv and write to file pointer
        foreach ($matches as $match_item) {
            fputcsv($f, array_values(array_filter($match_item, 'filter_hidden_csv_fields', ARRAY_FILTER_USE_KEY)), $delimiter);
        }
        // Move back to beginning of file
        fseek($f, 0);
        // Set headers to download file rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        //output all remaining data on a file pointer
        fpassthru($f);
        exit;
    }

    /**
     * @param $id_team
     * @param $email
     * @return array
     * @throws Exception
     */
    public function load_register($id_team, $email): array
    {
        $team = $this->getRegisterTeamInfo($id_team);
        if(strtolower($team['leader_email']) !== strtolower(trim($email))) {
            throw new Exception("l'email saisi ne correspond pas à l'email de contact de l'équipe !");
        }
        return $team;
    }

    /**
     * @throws Exception
     */
    private function getRegisterTeamInfo($id_team): array|int|string|null
    {
        $bindings = array();
        $bindings[] = array('type' => 'i', 'value' => $id_team);
        $results = $this->sql_manager->execute("
        SELECT
            jresp.nom AS leader_name,
            jresp.prenom AS leader_first_name,
            jresp.email AS leader_email,
            jresp.telephone AS leader_phone,
            g1.id AS id_court_1,
            cr1.jour AS day_court_1,
            cr1.heure AS hour_court_1,
            g2.id AS id_court_2,
            cr2.jour AS day_court_2,
            cr2.heure AS hour_court_2
        FROM equipes e 
        JOIN clubs c ON c.id=e.id_club
        LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e.id_equipe AND jeresp.is_leader+0 > 0
        LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
        LEFT JOIN creneau cr1 ON cr1.id_equipe = e.id_equipe AND cr1.usage_priority=1
        LEFT JOIN creneau cr2 ON cr2.id_equipe = e.id_equipe AND cr2.usage_priority=2
        LEFT JOIN gymnase g1 ON g1.id=cr1.id_gymnase
        LEFT JOIN gymnase g2 ON g2.id=cr2.id_gymnase
        WHERE e.id_equipe = ?
        ORDER BY nom_equipe", $bindings);
        if(count($results) !== 1) {
            throw new Exception("Pas d'informations trouvées pour cette équipe !");
        }
        return $results[0];
    }


}
