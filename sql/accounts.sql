SELECT e.nom_equipe,
       ca.email,
       ca.login
FROM equipes e
    JOIN users_teams ut ON ut.team_id = e.id_equipe
         JOIN comptes_acces ca ON ca.id = ut.user_id
ORDER BY e.nom_equipe
