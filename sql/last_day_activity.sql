SELECT DATE_FORMAT(a.activity_date, '%d/%m/%Y') AS date,
       e.nom_equipe,
       c.libelle                                AS competition,
       a.comment                                AS description,
       ca.login                                 AS utilisateur,
       ca.email                                 AS email_utilisateur
FROM activity a
         LEFT JOIN comptes_acces ca ON ca.id = a.user_id
         LEFT JOIN equipes e ON e.id_equipe = ca.id_equipe
         LEFT JOIN competitions c ON c.code_competition = e.code_competition
WHERE a.activity_date > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY a.id DESC