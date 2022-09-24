<?php

/**
 * Created by PhpStorm.
 * User: ballemand
 * Date: 17/02/2017
 * Time: 10:33
 */
require_once __DIR__ . '/Generic.php';
require_once __DIR__ . '/SqlManager.php';

class TeamManager extends Generic
{
    private $sql_manager;


    public function __construct()
    {
        $this->sql_manager = new SqlManager();
    }

    private function getSql($query = null)
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
        WHERE 1=1";
        if ($query !== NULL) {
            $sql .= " AND $query";
        }
        $sql .= " GROUP BY team_full_name
                  ORDER BY comp.libelle, c.nom, nom_equipe ASC";
        return $sql;
    }

    /**
     * @param null $query
     * @return array
     * @throws Exception
     */
    public function getTeams($query = null)
    {
        $db = Database::openDbConnection();
        $sql = $this->getSql($query);
        $req = mysqli_query($db, $sql) or die('Erreur SQL !<br>' . $sql . '<br>' . mysqli_error($db));
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
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
     * @throws Exception
     */
    public function set_leader($id_team, string $id_player)
    {
        if (!isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
        }
        if (!isPlayerInTeam($id_player, $id_team)) {
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
        addActivity("L'equipe " . getTeamName($id_team) . " a un nouveau responsable : " . getPlayerFullName($id_player));
    }

    /**
     * @throws Exception
     */
    function set_captain($id_team, string $id_player)
    {
        if (!isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
        }
        if (!isPlayerInTeam($id_player, $id_team)) {
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
        addActivity("L'equipe " . getTeamName($id_team) . " a un nouveau capitaine : " . getPlayerFullName($id_player));
    }

    /**
     * @throws Exception
     */
    function set_vice_leader($id_team, string $id_player)
    {
        if (!isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
        }
        if (!isPlayerInTeam($id_player, $id_team)) {
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
        addActivity("L'equipe " . getTeamName($id_team) . " a un nouveau suppleant : " . getPlayerFullName($id_player));
    }

    /**
     * @throws Exception
     */
    public function remove_from_team($id_team, string $id_player)
    {
        if (!isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
        }
        if (!isPlayerInTeam($id_player, $id_team)) {
            throw new Exception("Ce joueur n'est pas dans l'équipe !");
        }
        $sql = "DELETE FROM joueur_equipe WHERE id_joueur = ? AND id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_player),
            array('type' => 'i', 'value' => $id_team),
        );
        $this->sql_manager->execute($sql, $bindings);
        addActivity(getPlayerFullName($id_player) . " a ete supprime de l'equipe " . getTeamName($id_team));
    }

    public function add_to_team($id_team, string $id_player)
    {
        if (!isTeamLeader()) {
            throw new Exception("Vous n'êtes pas responsable d'équipe, vous ne pouvez pas modifier l'équipe !");
        }
        if (isPlayerInTeam($id_player, $id_team)) {
            throw new Exception("Ce joueur est déjà dans dans l'équipe !");
        }
        $sql = "INSERT joueur_equipe SET id_joueur = ?, id_equipe = ?";
        $bindings = array(
            array('type' => 'i', 'value' => $id_player),
            array('type' => 'i', 'value' => $id_team),
        );
        $this->sql_manager->execute($sql, $bindings);
        addActivity("Ajout de " . getPlayerFullName($id_player) . " a l'equipe " . getTeamName($id_team));
    }

    /**
     * @param $id_team
     * @return string
     * @throws Exception
     */
    public function getTeamSheet($id_team): string
    {
        if ($id_team === NULL) {
            throw new Exception("Id d'équipe est vide !");
        }
        if (!isTeamSheetAllowedForUser($id_team)) {
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
        return json_encode($results);
    }
}