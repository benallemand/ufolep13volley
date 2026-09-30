SELECT ca.id,
       ca.login,
       ca.email,
       ca.is_admin,
       GROUP_CONCAT(DISTINCT ut.team_id)   AS id_team,
       GROUP_CONCAT(DISTINCT e.nom_equipe) AS team_name,
       GROUP_CONCAT(DISTINCT c.nom)        AS club_name,
       GROUP_CONCAT(DISTINCT cm.nom)       AS managed_club_names,
       -- la personne derrière le compte (issue #331) : au plus une, uq_joueurs_compte
       MAX(jp.id)                          AS id_person,
       MAX(CONCAT(UPPER(jp.nom), ' ', jp.prenom)) AS person_name
FROM comptes_acces ca
         LEFT JOIN users_teams ut ON ut.user_id = ca.id
         LEFT JOIN equipes e ON e.id_equipe = ut.team_id
         LEFT JOIN clubs c ON c.id = e.id_club
         LEFT JOIN users_clubs uc ON uc.user_id = ca.id
         LEFT JOIN clubs cm ON cm.id = uc.club_id
         LEFT JOIN joueurs jp ON jp.id_compte = ca.id
GROUP BY ca.id, ca.login, ca.email, ca.is_admin
