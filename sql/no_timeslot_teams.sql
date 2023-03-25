SELECT e.nom_equipe AS equipe,
       c.libelle    AS competition
FROM equipes e
         JOIN competitions c ON c.code_competition = e.code_competition
WHERE e.id_equipe IN (SELECT cl.id_equipe
                      FROM classements cl)
  AND e.id_equipe NOT IN (SELECT id_equipe FROM creneau)