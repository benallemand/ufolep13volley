SELECT id_gymnasium, date_reception, COUNT(*)
FROM matches m
         JOIN gymnase g on m.id_gymnasium = g.id
WHERE id_gymnasium = ?
  AND m.date_reception = STR_TO_DATE(?, '%d/%m/%Y')
  AND m.match_status != 'ARCHIVED'
GROUP BY id_gymnasium, g.nb_terrain
HAVING COUNT(*) >= g.nb_terrain