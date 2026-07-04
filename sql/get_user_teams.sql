SELECT DISTINCT t.*
FROM users_teams ut
         JOIN teams_view t on ut.team_id = t.id_equipe
WHERE ut.user_id = ?
