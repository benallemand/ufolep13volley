SELECT SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
       SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
       c.code_competition                           AS competition,
       c.division                                   AS division,
       e.nom_equipe                                 AS equipe
FROM matchs_view m
         JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
         JOIN classements c on e.id_equipe = c.id_equipe AND c.code_competition = m.code_competition
WHERE m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED')
  AND m.id_equipe_ext IN (SELECT id_equipe FROM creneau)
GROUP BY c.code_competition, c.division, e.nom_equipe
HAVING ABS(domicile - exterieur) > 1
ORDER BY competition, division