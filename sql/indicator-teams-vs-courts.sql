SELECT c.nom                                                 AS club_nom,
       COUNT(DISTINCT r.id)                                  AS nombre_equipes_inscrites,
       -- Nombre max d'équipes autorisées (terrains_par_semaine x 2)
       (COALESCE(
                (SELECT SUM(creneaux_par_gymnase * nb_terrain)
                 FROM (SELECT id_gymnase,
                              MAX(creneaux_par_gymnase) as creneaux_par_gymnase,
                              MAX(nb_terrain)           as nb_terrain
                       FROM (SELECT r1.id_court_1                                                as id_gymnase,
                                    COUNT(DISTINCT CONCAT(r1.day_court_1, '-', r1.hour_court_1)) as creneaux_par_gymnase,
                                    MAX(g1.nb_terrain)                                           as nb_terrain
                             FROM register r1
                                      INNER JOIN gymnase g1 ON r1.id_court_1 = g1.id
                             WHERE r1.id_club = c.id
                               AND r1.id_court_1 IS NOT NULL
                               AND r1.day_court_1 IS NOT NULL
                               AND r1.hour_court_1 IS NOT NULL
                             GROUP BY r1.id_court_1

                             UNION

                             SELECT r2.id_court_2                                                as id_gymnase,
                                    COUNT(DISTINCT CONCAT(r2.day_court_2, '-', r2.hour_court_2)) as creneaux_par_gymnase,
                                    MAX(g2.nb_terrain)                                           as nb_terrain
                             FROM register r2
                                      INNER JOIN gymnase g2 ON r2.id_court_2 = g2.id
                             WHERE r2.id_club = c.id
                               AND r2.id_court_2 IS NOT NULL
                               AND r2.day_court_2 IS NOT NULL
                               AND r2.hour_court_2 IS NOT NULL
                             GROUP BY r2.id_court_2) all_gymnases
                       GROUP BY id_gymnase) gymnases_stats), 0
        ) * 2)                                                AS nombre_max_equipes_autorisees,
       -- Nombre de gymnases distincts utilisés (correction du calcul)
       (SELECT COUNT(DISTINCT id_gymnase)
        FROM (SELECT r1.id_court_1 as id_gymnase
              FROM register r1
              WHERE r1.id_club = c.id
                AND r1.id_court_1 IS NOT NULL
              UNION
              SELECT r2.id_court_2 as id_gymnase
              FROM register r2
              WHERE r2.id_club = c.id
                AND r2.id_court_2 IS NOT NULL) all_gymnases) AS nombre_gymnases_utilises,
       -- Calcul des terrains par semaine
       COALESCE(
               (SELECT SUM(creneaux_par_gymnase * nb_terrain)
                FROM (SELECT id_gymnase,
                             MAX(creneaux_par_gymnase) as creneaux_par_gymnase,
                             MAX(nb_terrain)           as nb_terrain
                      FROM (SELECT r1.id_court_1                                                as id_gymnase,
                                   COUNT(DISTINCT CONCAT(r1.day_court_1, '-', r1.hour_court_1)) as creneaux_par_gymnase,
                                   MAX(g1.nb_terrain)                                           as nb_terrain
                            FROM register r1
                                     INNER JOIN gymnase g1 ON r1.id_court_1 = g1.id
                            WHERE r1.id_club = c.id
                              AND r1.id_court_1 IS NOT NULL
                              AND r1.day_court_1 IS NOT NULL
                              AND r1.hour_court_1 IS NOT NULL
                            GROUP BY r1.id_court_1

                            UNION

                            SELECT r2.id_court_2                                                as id_gymnase,
                                   COUNT(DISTINCT CONCAT(r2.day_court_2, '-', r2.hour_court_2)) as creneaux_par_gymnase,
                                   MAX(g2.nb_terrain)                                           as nb_terrain
                            FROM register r2
                                     INNER JOIN gymnase g2 ON r2.id_court_2 = g2.id
                            WHERE r2.id_club = c.id
                              AND r2.id_court_2 IS NOT NULL
                              AND r2.day_court_2 IS NOT NULL
                              AND r2.hour_court_2 IS NOT NULL
                            GROUP BY r2.id_court_2) all_gymnases
                      GROUP BY id_gymnase) gymnases_stats), 0
       )                                                     AS terrains_par_semaine,
       -- Détail des gymnases
       (SELECT GROUP_CONCAT(
                       CONCAT(nom_gymnase, ': ', creneaux_par_gymnase, ' créneaux × ', nb_terrain, ' terrains = ',
                              creneaux_par_gymnase * nb_terrain)
                       SEPARATOR ' | '
               )
        FROM (SELECT id_gymnase,
                     MAX(nom_gymnase)          as nom_gymnase,
                     MAX(creneaux_par_gymnase) as creneaux_par_gymnase,
                     MAX(nb_terrain)           as nb_terrain
              FROM (SELECT r1.id_court_1                                                as id_gymnase,
                           MAX(g1.nom)                                                  as nom_gymnase,
                           COUNT(DISTINCT CONCAT(r1.day_court_1, '-', r1.hour_court_1)) as creneaux_par_gymnase,
                           MAX(g1.nb_terrain)                                           as nb_terrain
                    FROM register r1
                             INNER JOIN gymnase g1 ON r1.id_court_1 = g1.id
                    WHERE r1.id_club = c.id
                      AND r1.id_court_1 IS NOT NULL
                      AND r1.day_court_1 IS NOT NULL
                      AND r1.hour_court_1 IS NOT NULL
                    GROUP BY r1.id_court_1

                    UNION

                    SELECT r2.id_court_2                                                as id_gymnase,
                           MAX(g2.nom)                                                  as nom_gymnase,
                           COUNT(DISTINCT CONCAT(r2.day_court_2, '-', r2.hour_court_2)) as creneaux_par_gymnase,
                           MAX(g2.nb_terrain)                                           as nb_terrain
                    FROM register r2
                             INNER JOIN gymnase g2 ON r2.id_court_2 = g2.id
                    WHERE r2.id_club = c.id
                      AND r2.id_court_2 IS NOT NULL
                      AND r2.day_court_2 IS NOT NULL
                      AND r2.hour_court_2 IS NOT NULL
                    GROUP BY r2.id_court_2) all_detail
              GROUP BY id_gymnase
              ORDER BY nom_gymnase) detail_gymnases)         AS detail_gymnases
FROM clubs c
         INNER JOIN register r ON c.id = r.id_club
GROUP BY c.id, c.nom
HAVING COUNT(DISTINCT r.id) > 0
ORDER BY terrains_par_semaine DESC, c.nom