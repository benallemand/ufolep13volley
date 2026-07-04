SELECT DISTINCT t.*
FROM comptes_acces u
         JOIN comptes_acces ca ON ca.email = u.email
         JOIN users_teams ut on ca.id = ut.user_id
         JOIN teams_view t on ut.team_id = t.id_equipe
WHERE u.id = ?
