SELECT e.nom_equipe AS equipe,
       c.libelle    AS competition
FROM equipes e
         JOIN competitions c ON c.code_competition = e.code_competition
WHERE e.id_equipe NOT IN (SELECT ut.team_id
                          FROM users_teams ut
                                   JOIN comptes_acces ca ON ut.user_id = ca.id
                          WHERE ca.id IN (SELECT up.user_id
                                          FROM users_profiles up
                                          WHERE profile_id IN (SELECT p.id
                                                               FROM profiles p
                                                               WHERE p.name = 'RESPONSABLE_EQUIPE')))
  AND e.id_equipe IN (SELECT cl.id_equipe
                      FROM classements cl)