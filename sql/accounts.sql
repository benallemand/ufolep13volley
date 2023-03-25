SELECT e.nom_equipe,
       ca.email,
       ca.login,
       p.name AS profil
FROM equipes e
         JOIN comptes_acces ca ON ca.id_equipe = e.id_equipe
         LEFT JOIN users_profiles up ON up.user_id = ca.id
         LEFT JOIN profiles p ON p.id = up.profile_id
ORDER BY e.nom_equipe
