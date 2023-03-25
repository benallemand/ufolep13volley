SELECT
    gymnase.ville AS "Ville",
  gymnase.nom AS "Gymnase",
  m.date_reception AS "Date",
  COUNT(DISTINCT m.id_match) AS "Nombre de matches",
  gymnase.nb_terrain AS "Nombre de terrains",
  GROUP_CONCAT(DISTINCT m.code_match SEPARATOR ', ') AS "Liste des matches"
FROM matches m
         JOIN gymnase ON gymnase.id = m.id_gymnasium
WHERE m.match_status != 'ARCHIVED'
  AND m.date_reception > CURRENT_DATE
GROUP BY CONCAT(gymnase.nom, gymnase.ville), m.date_reception
HAVING COUNT(DISTINCT m.id_match) > gymnase.nb_terrain
ORDER BY COUNT(DISTINCT m.id_match) DESC