SELECT e.id_equipe,
       e.nom_equipe AS equipe,
       c.libelle    AS competition
FROM equipes e
         JOIN classements cl ON cl.id_equipe = e.id_equipe
         JOIN competitions c ON c.code_competition = e.code_competition
         LEFT JOIN joueur_equipe je ON je.id_equipe = e.id_equipe AND je.is_leader = 1
WHERE je.id_joueur IS NULL
GROUP BY e.id_equipe