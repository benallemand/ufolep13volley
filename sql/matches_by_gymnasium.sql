SELECT gymnase.ville                                      AS "Ville",
       gymnase.nom                                        AS "Gymnase",
       m.date_reception                                   AS "Date",
       COUNT(DISTINCT m.id_match)                         AS "Nombre de matches",
       GROUP_CONCAT(DISTINCT m.code_match SEPARATOR ', ') AS "Liste des matches"
FROM matches m
         JOIN creneau ON creneau.id_equipe = m.id_equipe_dom
         JOIN gymnase ON gymnase.id = creneau.id_gymnase
WHERE m.match_status != 'ARCHIVED'
GROUP BY CONCAT(gymnase.nom, gymnase.ville), m.date_reception
ORDER BY COUNT(DISTINCT m.id_match) DESC