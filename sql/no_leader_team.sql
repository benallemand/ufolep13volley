SELECT je.id_equipe,
       e.nom_equipe AS equipe,
       c.libelle    AS competition
FROM joueur_equipe je
         JOIN equipes e ON e.id_equipe = je.id_equipe
         JOIN competitions c ON c.code_competition = e.code_competition
WHERE je.id_equipe IN (SELECT cl.id_equipe
                       FROM classements cl)
GROUP BY id_equipe
HAVING SUM(je.is_leader + 0) IS NULL