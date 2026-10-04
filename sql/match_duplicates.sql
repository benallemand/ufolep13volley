-- `indicator_id` ouvre l'écran de correction filtré sur ces lignes (#312, #409).
SELECT GROUP_CONCAT(m.id_match) AS indicator_id,
       e1.nom_equipe AS domicile,
       e2.nom_equipe AS exterieur,
       GROUP_CONCAT(m.code_match SEPARATOR ', ') AS matchs,
       COUNT(*) AS nombre
FROM matches m
         JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
WHERE m.code_competition != 'mo'
  AND m.match_status != 'ARCHIVED'
GROUP BY m.id_equipe_dom, m.id_equipe_ext, m.code_competition
HAVING COUNT(*) > 1