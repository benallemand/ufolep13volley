SELECT DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date,
       GROUP_CONCAT(DISTINCT e.nom_equipe ORDER BY e.nom_equipe SEPARATOR ', ') AS nom_equipe,
       GROUP_CONCAT(DISTINCT c.libelle ORDER BY c.libelle SEPARATOR ', ')       AS competition,
       a.comment                                AS description,
       ca.login                                 AS utilisateur,
       ca.email                                 AS email_utilisateur
FROM activity a
         LEFT JOIN comptes_acces ca ON ca.id = a.user_id
         LEFT JOIN users_teams ut ON ca.id = ut.user_id
         LEFT JOIN equipes e ON ut.team_id = e.id_equipe
         LEFT JOIN competitions c ON c.code_competition = e.code_competition
WHERE a.activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY a.id, a.activity_date, a.comment, ca.login, ca.email
ORDER BY a.id DESC
