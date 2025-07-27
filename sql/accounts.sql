SELECT e.nom_equipe,
       ca.email,
       ca.login,
       p.name AS profil
FROM equipes e
    JOIN users_teams ut ON ut.team_id = e.id_equipe
         JOIN comptes_acces ca ON ca.id = ut.user_id
         LEFT JOIN users_profiles up ON up.user_id = ca.id
         LEFT JOIN profiles p ON p.id = up.profile_id
ORDER BY e.nom_equipe
