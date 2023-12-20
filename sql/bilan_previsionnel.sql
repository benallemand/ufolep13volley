SELECT COUNT(DISTINCT c3.id)        AS nb_clubs,
       COUNT(DISTINCT c.id_equipe)  AS nb_6x6,
       COUNT(DISTINCT c2.id_equipe) AS nb_4x4
FROM classements c,
     classements c2,
     clubs c3
         JOIN register r on c3.id = r.id_club
WHERE c.code_competition IN ('m')
  AND c2.code_competition IN ('f', 'mo')