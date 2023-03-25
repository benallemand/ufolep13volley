SELECT e.nom_equipe,
       COUNT(c1.id_equipe)                AS nb_equipes_meme_creneau,
       g.nb_terrain,
       g.nb_terrain - COUNT(c1.id_equipe) AS ratio
FROM equipes e
         LEFT JOIN creneau c on e.id_equipe = c.id_equipe
         LEFT JOIN creneau c1 on
            c1.id_gymnase = c.id_gymnase
        AND c1.jour = c.jour
        AND c1.id_equipe != c.id_equipe
        AND c1.id_equipe IN (SELECT id_equipe FROM classements)
         LEFT JOIN gymnase g on c.id_gymnase = g.id
         LEFT JOIN equipes e2 ON e2.id_equipe = c1.id_equipe
WHERE e.id_equipe IN (SELECT id_equipe FROM classements)
GROUP BY e.nom_equipe
ORDER BY ratio