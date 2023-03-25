SELECT e1.nom_equipe, e2.nom_equipe, m.code_match, COUNT(*)
FROM matches m
         JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
WHERE m.code_competition != 'mo'
  AND m.match_status != 'ARCHIVED'
GROUP BY m.id_equipe_dom, m.id_equipe_ext, m.code_competition
HAVING COUNT(*) > 1