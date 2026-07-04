SELECT ca.id,
       ca.login,
       ca.password_hash,
       ca.email,
       ca.is_admin,
       GROUP_CONCAT(DISTINCT ut.team_id)   AS id_team,
       GROUP_CONCAT(DISTINCT e.nom_equipe) AS team_name,
       GROUP_CONCAT(DISTINCT c.nom)        AS club_name,
       GROUP_CONCAT(DISTINCT cm.nom)       AS managed_club_names
FROM comptes_acces ca
         LEFT JOIN users_teams ut ON ut.user_id = ca.id
         LEFT JOIN equipes e ON e.id_equipe = ut.team_id
         LEFT JOIN clubs c ON c.id = e.id_club
         LEFT JOIN users_clubs uc ON uc.user_id = ca.id
         LEFT JOIN clubs cm ON cm.id = uc.club_id
GROUP BY ca.id, ca.login, ca.password_hash, ca.email, ca.is_admin
