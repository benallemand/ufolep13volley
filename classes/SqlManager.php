<?php
require_once __DIR__ . '/../classes/Database.php';

class SqlManager
{
    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_activity(): array
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
        return $this->execute($sql);
    }


    /**
     * @param $sql
     * @param array $bindings
     * @return array|int|string|null
     * @throws Exception
     */
    public function execute($sql, array $bindings = array()): array|int|string|null
    {
        $db = Database::openDbConnection();
        $sql = trim($sql);
        mysqli_query($db, "SET SESSION group_concat_max_len = 1000000");
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        if (count($bindings) > 0) {
            $array_params = array($stmt, '');
            foreach ($bindings as $binding) {
                $array_params[1] .= $binding['type'];
            }
            foreach ($bindings as $binding) {
                $array_params[] = $binding['value'];
            }
            if (call_user_func_array('mysqli_stmt_bind_param', $this->make_values_referenced($array_params)) === FALSE) {
                throw new Exception("Erreur SQL : " . mysqli_error($db));
            }
        }
        if (mysqli_stmt_execute($stmt) === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        if (str_starts_with($sql, "SELECT") || str_starts_with($sql, "SHOW")) {
            $mysqli_result = mysqli_stmt_get_result($stmt);
            $results = array();
            while ($data = mysqli_fetch_assoc($mysqli_result)) {
                $results[] = $data;
            }
            if (mysqli_stmt_close($stmt) === FALSE) {
                throw new Exception("Erreur SQL : " . mysqli_error($db));
            }
            return $results;
        }
        if (str_starts_with($sql, "INSERT INTO")) {
            return mysqli_insert_id($db);
        }
        if (mysqli_stmt_close($stmt) === FALSE) {
            throw new Exception("Erreur SQL : " . mysqli_error($db));
        }
        return null;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_matches_not_reported(): array
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
                AND m.match_status = 'CONFIRMED'
                AND m.certif = 0
                ORDER BY m.code_match";
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_ids_team_requesting_next_matches(): array
    {
        $sql = "SELECT
                REPLACE(REPLACE(registry_key, '.is_remind_matches',''), 'users.','') AS team_id
                FROM registry
                WHERE registry_key LIKE 'users.%.is_remind_matches'
                AND registry_value = 'on'";
        return $this->execute($sql);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_next_matches_for_team($team_id): array
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
                AND m.match_status = 'CONFIRMED'
                GROUP BY m.code_match, m.date_reception
                ORDER BY m.date_reception";
        return $this->execute($sql);
    }

    /**
     * @param $team_id
     * @return array
     * @throws Exception
     */
    public function sql_get_email_from_team_id($team_id): array
    {
        $sql = "SELECT j.email
                FROM joueurs j
                JOIN joueur_equipe je ON
                    je.id_joueur = j.id
                    AND je.is_leader+0 > 0
                    WHERE je.id_equipe = $team_id";
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_players_without_licence_number(): array
    {
        $sql = file_get_contents(__DIR__ . '/../sql/players_without_licence_number.sql');
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_team_leaders_without_email(): array
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
        return $this->execute($sql);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_pending_reports(): array
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
                AND m.match_status = 'CONFIRMED'
                AND m.sheet_received = 0
                ORDER BY m.code_match";
        return $this->execute($sql);
    }

    private function make_values_referenced($arr): array
    {
        $refs = array();
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function sql_get_team_recaps(): array
    {
        $sql = "SELECT 
                    e.nom_equipe AS team_name,
                    IF(je.id_equipe IS NOT NULL, 
                        CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'), 
                        CONCAT('Pas de responsable ! Infos club: ', c2.prenom_responsable, ' ', c2.nom_responsable, ' (tel: ', c2.tel1_responsable, ', mail: ', c2.email_responsable, ')')) AS team_leader,
                    c2.email_responsable AS club_email,
                    c3.libelle AS championship_name,
                    c.division AS division,
                    GROUP_CONCAT(CONCAT(c4.jour, '<span/>', c4.heure, '<span/>', g.nom) SEPARATOR '<br/>') AS creneaux
                FROM classements c 
                JOIN equipes e on c.id_equipe = e.id_equipe
                JOIN clubs c2 on e.id_club = c2.id
                JOIN competitions c3 on c.code_competition = c3.code_competition
                LEFT JOIN joueur_equipe je on e.id_equipe = je.id_equipe AND je.is_leader = true
                LEFT JOIN joueurs j on je.id_joueur = j.id
                LEFT JOIN creneau c4 on e.id_equipe = c4.id_equipe
                LEFT JOIN gymnase g on c4.id_gymnase = g.id
                WHERE c3.libelle LIKE '%Championnat%'
                GROUP BY e.nom_equipe, 
                         IF(je.id_equipe IS NOT NULL, 
                             CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'), 
                             CONCAT('Pas de responsable ! Infos club: ', c2.prenom_responsable, ' ', c2.nom_responsable, ' (tel: ', c2.tel1_responsable, ', mail: ', c2.email_responsable, ')')), c2.email_responsable, c3.libelle, c.division
                ORDER BY championship_name, division, team_name";
        return $this->execute($sql);
    }

    /**
     * @throws Exception
     */
    public function get_teams_with_missing_licences(): array
    {
        $sql = "SELECT GROUP_CONCAT(DISTINCT CONCAT(j.prenom, ' ', j.nom)) AS joueurs,
                       c.nom AS club,
                       c.email_responsable AS responsable,
                       e.nom_equipe AS equipe,
                       jr.email
                FROM players_view j
                         JOIN joueur_equipe je ON je.id_joueur = j.id
                         JOIN match_player mp ON mp.id_player = je.id_joueur
                         JOIN matches m ON m.id_match = mp.id_match
                         JOIN equipes e
                              ON e.id_equipe = je.id_equipe AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
                         JOIN joueur_equipe jer ON jer.id_equipe = e.id_equipe AND jer.is_leader + 0 > 0
                         JOIN players_view jr ON jr.id = jer.id_joueur
                         JOIN clubs c ON c.id = e.id_club
                WHERE j.est_actif = 0
                    AND m.match_status = 'CONFIRMED'
                AND m.code_competition IN (SELECT code_competition FROM competitions WHERE start_date <= CURRENT_DATE)
                GROUP BY c.nom, c.email_responsable, e.nom_equipe, jr.email";
        return $this->execute($sql);
    }
}
