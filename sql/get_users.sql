SELECT ca.id,
       ca.login,
       ca.password_hash,
       ca.email,
       GROUP_CONCAT(DISTINCT ut.team_id)   AS id_team,
       GROUP_CONCAT(DISTINCT e.nom_equipe) AS team_name,
       GROUP_CONCAT(DISTINCT c.nom)        AS club_name,
       up.profile_id                       AS id_profile,
       p.name                              AS profile
FROM comptes_acces ca
         LEFT JOIN users_teams ut ON ut.user_id = ca.id
         LEFT JOIN equipes e ON e.id_equipe = ut.team_id
         LEFT JOIN clubs c ON c.id = e.id_club
         LEFT JOIN users_profiles up ON up.user_id = ca.id
         LEFT JOIN profiles p ON p.id = up.profile_id
GROUP BY ca.id, ca.login, ca.password_hash, ca.email, up.profile_id, p.name