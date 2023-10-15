SELECT c.libelle                              AS competition,
       COALESCE(r.division, '?')   AS division,
       COALESCE(r.rank_start, '?') AS rang,
       r.new_team_name                        AS équipe
FROM register r
         JOIN competitions c ON r.id_competition = c.id
UNION ALL
SELECT c.libelle     as competition,
       cl.division,
       cl.rank_start AS rang,
       '?'          AS équipe
from classements cl
         JOIN competitions c ON c.code_competition = cl.code_competition
WHERE cl.rank_start NOT IN (SELECT r.rank_start
                            FROM register r
                            where r.id_competition = c.id
                              and r.division = cl.division)
  and cl.code_competition IN ('m', 'f', 'mo')
ORDER BY competition, division, rang, équipe