SELECT DISTINCT t.*
FROm comptes_acces u
    JOIN comptes_acces ca ON ca.email = u.email
         JOIN users_profiles up on ca.id = up.user_id
         JOIN profiles p on up.profile_id = p.id
         JOIN users_teams ut on ca.id = ut.user_id
         JOIN teams_view t on ut.team_id = t.id_equipe
         JOIN classements c on t.id_equipe = c.id_equipe
WHERE p.name = 'RESPONSABLE_EQUIPE'
  AND u.id = ?