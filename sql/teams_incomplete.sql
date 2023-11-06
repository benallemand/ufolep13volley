SELECT e2.nom_equipe, c.libelle, j2.email
FROM equipes e2
         JOIN joueur_equipe je2 on e2.id_equipe = je2.id_equipe AND je2.is_leader = 1
         JOIN joueurs j2 on je2.id_joueur = j2.id
         JOIN competitions c on e2.code_competition = c.code_competition
WHERE e2.id_equipe IN (SELECT je.id_equipe
                       FROM joueur_equipe je
                                JOIN equipes e on je.id_equipe = e.id_equipe
                                JOIN joueurs j on je.id_joueur = j.id
                       GROUP BY je.id_equipe, e.code_competition
                       HAVING COUNT(*) < (CASE WHEN e.code_competition IN ('f', 'mo', 'kh', 'kf') THEN 4 ELSE 6 END))
  AND nom_equipe != 'equipe de test'