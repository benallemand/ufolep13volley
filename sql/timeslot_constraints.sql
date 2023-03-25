SELECT c2.libelle   AS competition,
       e.nom_equipe AS equipe,
       c1.nom       AS club,
       g.nom        AS gymnase,
       g.ville,
       c.jour,
       c.heure
FROM creneau c
         JOIN gymnase g ON g.id = c.id_gymnase
         JOIN equipes e ON e.id_equipe = c.id_equipe
         JOIN clubs c1 ON c1.id = e.id_club
         JOIN competitions c2 ON c2.code_competition = e.code_competition
WHERE has_time_constraint + 0 > 0