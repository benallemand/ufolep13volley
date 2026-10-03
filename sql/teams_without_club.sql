-- `indicator_id` : voir `no_licence.sql` (issue #312).
-- Issue #397 : le mixte était oublié (m, f et kh seulement).
SELECT e.id_equipe AS indicator_id,
       e.nom_equipe,
       comp.libelle,
       c.division
FROM equipes e
         LEFT JOIN competitions comp ON comp.code_competition = e.code_competition
         LEFT JOIN classements c ON c.id_equipe = e.id_equipe AND c.code_competition = e.code_competition
WHERE (
              e.code_competition IN ('m', 'f', 'mo', 'kh')
              AND c.division IS NOT NULL
              AND e.id_club IS NULL
          )
ORDER BY e.code_competition, c.division, e.id_equipe