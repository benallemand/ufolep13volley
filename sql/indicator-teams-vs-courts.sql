-- `indicator_id` ouvre l'écran de correction filtré sur ces lignes (#312, #409).
-- Inscriptions - Terrains vs Equipes (alerte) : les clubs qui inscrivent plus
-- d'équipes que leurs terrains ne peuvent en recevoir.
--
-- Capacité d'un club : pour chacun de ses gymnases, créneaux distincts
-- (jour + heure) × terrains du gymnase, sommés, puis × 2 : un terrain sur un
-- créneau reçoit deux équipes, une semaine sur deux.
-- Une demande refusée ne compte ni comme équipe, ni comme créneau.
WITH creneaux AS (SELECT r.id_club, r.id_court_1 AS id_gymnase, r.day_court_1 AS jour, r.hour_court_1 AS heure
                  FROM register r
                  WHERE r.status <> 'REFUSED'
                    AND r.id_court_1 IS NOT NULL
                    AND r.day_court_1 IS NOT NULL
                    AND r.hour_court_1 IS NOT NULL
                  UNION
                  SELECT r.id_club, r.id_court_2, r.day_court_2, r.hour_court_2
                  FROM register r
                  WHERE r.status <> 'REFUSED'
                    AND r.id_court_2 IS NOT NULL
                    AND r.day_court_2 IS NOT NULL
                    AND r.hour_court_2 IS NOT NULL),
     gymnases AS (SELECT cr.id_club, g.nom AS nom_gymnase, COUNT(*) AS creneaux, MAX(g.nb_terrain) AS nb_terrain
                  FROM creneaux cr
                           JOIN gymnase g ON g.id = cr.id_gymnase
                  GROUP BY cr.id_club, g.id, g.nom),
     capacites AS (SELECT id_club,
                          COUNT(*)                   AS nombre_gymnases_utilises,
                          SUM(creneaux * nb_terrain) AS terrains_par_semaine,
                          GROUP_CONCAT(CONCAT(nom_gymnase, ': ', creneaux, ' créneaux × ', nb_terrain, ' terrains = ',
                                              creneaux * nb_terrain)
                                       ORDER BY nom_gymnase SEPARATOR ' | ') AS detail_gymnases
                   FROM gymnases
                   GROUP BY id_club),
     inscrites AS (SELECT id_club, COUNT(*) AS nombre_equipes_inscrites, GROUP_CONCAT(id) AS demandes
                   FROM register
                   WHERE status <> 'REFUSED'
                   GROUP BY id_club)
SELECT i.demandes                                                                    AS indicator_id,
       c.nom                                                                         AS club_nom,
       i.nombre_equipes_inscrites,
       COALESCE(k.terrains_par_semaine, 0) * 2                                       AS nombre_max_equipes_autorisees,
       i.nombre_equipes_inscrites - COALESCE(k.terrains_par_semaine, 0) * 2          AS equipes_en_trop,
       COALESCE(k.nombre_gymnases_utilises, 0)                                       AS nombre_gymnases_utilises,
       COALESCE(k.terrains_par_semaine, 0)                                           AS terrains_par_semaine,
       k.detail_gymnases
FROM inscrites i
         JOIN clubs c ON c.id = i.id_club
         LEFT JOIN capacites k ON k.id_club = i.id_club
WHERE i.nombre_equipes_inscrites > COALESCE(k.terrains_par_semaine, 0) * 2
ORDER BY equipes_en_trop DESC, c.nom
