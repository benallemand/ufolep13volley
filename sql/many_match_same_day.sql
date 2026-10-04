-- `indicator_id` ouvre l'écran de correction filtré sur ces lignes (#312, #409).
SELECT GROUP_CONCAT(m.id_match)               AS indicator_id,
       GROUP_CONCAT(m.code_match)             AS codes_match,
       e.nom_equipe,
       DATE_FORMAT(m.date_reception, '%Y_%u') AS annee_semaine
FROM equipes e
         JOIN matches m on e.id_equipe = m.id_equipe_dom OR e.id_equipe = m.id_equipe_ext
WHERE m.date_reception > CURRENT_DATE
GROUP BY e.id_equipe, annee_semaine
HAVING COUNT(m.code_match) > 1
ORDER BY annee_semaine