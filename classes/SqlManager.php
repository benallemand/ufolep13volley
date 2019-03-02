<?php
require_once __DIR__ . '/../classes/Database.php';

class SqlManager
{
    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_accounts()
    {
        $sql = "SELECT
                e.nom_equipe,
                c.libelle AS competition,
                ca.login,
                ca.password,
                ca.email,
                ca.id
                FROM comptes_acces ca
                JOIN equipes e ON e.id_equipe=ca.id_equipe
                JOIN competitions c ON c.code_competition=e.code_competition
                WHERE ca.is_email_sent = 'N'";
        return $this->getResults($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_activity()
    {
        $sql = "SELECT
                DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date,
                e.nom_equipe,
                c.libelle AS competition,
                a.comment AS description,
                ca.login AS utilisateur,
                ca.email AS email_utilisateur
                FROM activity a
                LEFT JOIN comptes_acces ca ON ca.id=a.user_id
                LEFT JOIN equipes e ON e.id_equipe=ca.id_equipe
                LEFT JOIN competitions c ON c.code_competition=e.code_competition
                WHERE a.activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY a.id DESC";
        return $this->getResults($sql);
    }

    /**
     * @param $sql
     * @return array
     * @throws Exception
     */
    public function getResults($sql)
    {
        $db = Database::openDbConnection();
        $req = mysqli_query($db, $sql);
        if ($req === false) {
            throw new Exception("Error during SQL request: " . mysqli_error($db));
        }
        $results = array();
        while ($data = mysqli_fetch_assoc($req)) {
            $results[] = $data;
        }
        return $results;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_matches_not_reported()
    {
        $sql = "SELECT
                m.code_match,
                c1.nom AS club_reception,
                CONCAT(e1.nom_equipe, ' (', comp.libelle, ')') AS equipe_reception,
                jresp1.email AS responsable_reception,
                c2.nom AS club_visiteur,
                CONCAT(e2.nom_equipe, ' (', comp.libelle, ')') AS equipe_visiteur,
                jresp2.email AS responsable_visiteur,
                DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date_reception
                FROM matches m
                JOIN competitions comp ON comp.code_competition = m.code_competition
                JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
                JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
                JOIN joueur_equipe jeresp1 ON jeresp1.id_equipe = e1.id_equipe AND jeresp1.is_leader+0 > 0
                JOIN joueur_equipe jeresp2 ON jeresp2.id_equipe = e2.id_equipe AND jeresp2.is_leader+0 > 0
                JOIN joueurs jresp1 ON jresp1.id = jeresp1.id_joueur
                JOIN joueurs jresp2 ON jresp2.id = jeresp2.id_joueur
                JOIN clubs c1 ON c1.id = jresp1.id_club
                JOIN clubs c2 ON c2.id = jresp2.id_club
                WHERE
                (
                (m.score_equipe_dom+m.score_equipe_ext+0=0)
                OR
                ((m.set_1_dom+m.set_1_ext=0) AND (m.score_equipe_dom+m.score_equipe_ext>0))
                OR
                ((m.set_1_dom+m.set_1_ext>0) AND (m.score_equipe_dom+m.score_equipe_ext+0=0))
                )
                AND m.date_reception < CURDATE() - INTERVAL 10 DAY
                AND m.match_status != 'ARCHIVED'
                ORDER BY m.code_match ASC";
        return $this->getResults($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_ids_team_requesting_next_matches()
    {
        $sql = "SELECT
                REPLACE(REPLACE(registry_key, '.is_remind_matches',''), 'users.','') AS team_id
                FROM registry
                WHERE registry_key LIKE 'users.%.is_remind_matches'
                AND registry_value = 'on'";
        return $this->getResults($sql);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_next_matches_for_team($team_id)
    {
        $sql = "SELECT
                e1.nom_equipe AS equipe_domicile,
                e2.nom_equipe AS equipe_exterieur,
                m.code_match as code_match,
                DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date,
                cr.heure AS heure,
                CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
                jresp.telephone,
                jresp.email,
                GROUP_CONCAT(
                    CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (',cr.jour, ' à ', cr.heure,')')
                    SEPARATOR ', ')
                    AS creneaux
                FROM matches m
                JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
                JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
                LEFT JOIN creneau cr ON cr.id_equipe = e1.id_equipe AND cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                              'Dimanche',
                                              'Lundi',
                                              'Mardi',
                                              'Mercredi',
                                              'Jeudi',
                                              'Vendredi',
                                              'Samedi')
                LEFT JOIN gymnase g ON g.id = cr.id_gymnase
                LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe=e1.id_equipe AND jeresp.is_leader+0 > 0
                LEFT JOIN joueurs jresp ON jresp.id=jeresp.id_joueur
                WHERE
                 (m.id_equipe_dom = $team_id OR id_equipe_ext = $team_id)
                 AND
                (
                m.date_reception >= CURDATE()
                AND
                m.date_reception < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                )
                AND m.score_equipe_dom = 0 AND m.score_equipe_ext = 0
                AND m.match_status != 'ARCHIVED'
                GROUP BY m.code_match
                ORDER BY date_reception ASC";
        return $this->getResults($sql);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_email_from_team_id($team_id)
    {
        $sql = "SELECT j.email
                FROM joueurs j
                JOIN joueur_equipe je ON
                    je.id_joueur = j.id
                    AND je.is_leader+0 > 0
                    WHERE je.id_equipe = $team_id";
        return $this->getResults($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_players_without_licence_number()
    {
        $sql = "SELECT
                GROUP_CONCAT(CONCAT(j.nom, ' ', j.prenom) SEPARATOR ', ') AS joueurs,
                c.nom AS club,
                CONCAT(e.nom_equipe, ' (', comp.libelle, ')') AS equipe,
                jresp.email AS responsable
                FROM joueur_equipe je
                JOIN joueurs j ON j.id = je.id_joueur
                JOIN equipes e ON e.id_equipe = je.id_equipe
                JOIN joueur_equipe jeresp ON jeresp.id_equipe = e.id_equipe AND jeresp.is_leader+0 > 0
                JOIN joueurs jresp ON jresp.id = jeresp.id_joueur
                JOIN competitions comp ON comp.code_competition = e.code_competition
                JOIN clubs c ON c.id = j.id_club
                WHERE (j.num_licence = '' OR j.num_licence IS NULL)
                AND e.id_equipe IN (SELECT id_equipe FROM classements)
                GROUP BY jresp.email
                ORDER BY equipe ASC";
        return $this->getResults($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_team_leaders_without_email()
    {
        $sql = "SELECT DISTINCT
                  j.prenom,
                  j.nom,
                  c.libelle AS competition,
                  e.nom_equipe AS equipe
                FROM classements cl
                  JOIN joueur_equipe je ON je.id_equipe = cl.id_equipe
                  JOIN joueurs j ON j.id = je.id_joueur
                  JOIN equipes e ON e.id_equipe = je.id_equipe
                  JOIN competitions c ON c.code_competition = e.code_competition
                WHERE je.is_leader + 0 > 0
                      AND j.email = ''
                      AND e.id_equipe IN (SELECT id_equipe FROM classements)";
        return $this->getResults($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_pending_reports()
    {
        $sql = "SELECT
                m.code_match AS match_reference,
                e1.nom_equipe AS team_home,
                jresp1.email AS email_home,
                e2.nom_equipe AS team_guest,
                jresp2.email AS email_guest,
                DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS original_match_date
                FROM matches m
                JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
                JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
                JOIN joueur_equipe jeresp1 ON jeresp1.id_equipe = e1.id_equipe AND jeresp1.is_leader+0 > 0
                JOIN joueur_equipe jeresp2 ON jeresp2.id_equipe = e2.id_equipe AND jeresp2.is_leader+0 > 0
                JOIN joueurs jresp1 ON jresp1.id = jeresp1.id_joueur
                JOIN joueurs jresp2 ON jresp2.id = jeresp2.id_joueur
                WHERE 
                m.report_status IN ('ASKED_BY_DOM', 'ASKED_BY_EXT')
                AND m.match_status != 'ARCHIVED'
                ORDER BY m.code_match ASC";
        return $this->getResults($sql);
    }
}