-- Équipes dont les réceptions et les déplacements de la saison s'écartent de
-- plus de deux matchs, matchs archivés compris.
--
-- Bornée à la saison en cours (issue #397), qui s'ouvre le 1er juillet, comme
-- `CalendarEvents::getCurrentSeason()` : de janvier à juin, c'est celle
-- ouverte l'été précédent. Sans borne, la tuile comptait les matchs archivés
-- de la saison passée, et signalait à l'intersaison des équipes de l'an
-- dernier.
SELECT SUM(IF(m.id_equipe_dom = e.id_equipe, 1, 0)) AS domicile,
       SUM(IF(m.id_equipe_ext = e.id_equipe, 1, 0)) AS exterieur,
       c.code_competition                           AS competition,
       c.division                                   AS division,
       e.nom_equipe                                 AS equipe
FROM matches m
         JOIN equipes e on m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
         JOIN classements c on e.id_equipe = c.id_equipe AND c.code_competition = m.code_competition
WHERE m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED', 'ARCHIVED')
  AND m.date_reception >= MAKEDATE(IF(MONTH(CURRENT_DATE) <= 6, YEAR(CURRENT_DATE) - 1, YEAR(CURRENT_DATE)), 1)
                              + INTERVAL 6 MONTH
  AND m.id_equipe_ext IN (SELECT id_equipe FROM creneau)
GROUP BY c.code_competition, c.division, e.nom_equipe
HAVING ABS(domicile - exterieur) > 2
ORDER BY competition, division
