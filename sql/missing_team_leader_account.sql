SELECT e.nom_equipe AS equipe,
       c.libelle    AS competition
FROM equipes e
         JOIN competitions c ON c.code_competition = e.code_competition
WHERE e.id_equipe NOT IN (SELECT ca.id_equipe
                          FROM comptes_acces ca
                          WHERE ca.id IN (SELECT up.user_id
                                          FROM users_profiles up
                                          WHERE profile_id IN (SELECT p.id
                                                               FROM profiles p
                                                               WHERE p.name = 'RESPONSABLE_EQUIPE')))
  AND e.id_equipe IN (SELECT cl.id_equipe
                      FROM classements cl)