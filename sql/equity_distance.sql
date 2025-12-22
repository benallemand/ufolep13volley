WITH equipe_gymnase AS (SELECT c.id_equipe,
                               g.id                                                    AS id_gymnase,
                               g.nom                                                   AS nom_gymnase,
                               g.gps,
                               CAST(SUBSTRING_INDEX(g.gps, ',', 1) AS DECIMAL(10, 7))  AS lat,
                               CAST(SUBSTRING_INDEX(g.gps, ',', -1) AS DECIMAL(10, 7)) AS lng
                        FROM creneau c
                                 JOIN gymnase g ON c.id_gymnase = g.id
                        WHERE c.usage_priority = 1
                        GROUP BY c.id_equipe),
     match_distances AS (SELECT m.id_match,
                                m.code_competition,
                                cl.division,
                                e.id_equipe,
                                e.nom_equipe,
                                cb.nom  AS nom_club,
                                CASE
                                    WHEN m.id_equipe_dom = e.id_equipe THEN 0
                                    ELSE
                                        6371 * 2 * ASIN(SQRT(
                                                POWER(SIN(RADIANS(eg_match.lat - eg_equipe.lat) / 2), 2) +
                                                COS(RADIANS(eg_equipe.lat)) * COS(RADIANS(eg_match.lat)) *
                                                POWER(SIN(RADIANS(eg_match.lng - eg_equipe.lng) / 2), 2)
                                                        ))
                                    END AS distance_km
                         FROM matchs_view m
                                  JOIN equipes e ON m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe
                                  JOIN classements cl
                                       ON e.id_equipe = cl.id_equipe AND cl.code_competition = m.code_competition
                                  LEFT JOIN clubs cb ON e.id_club = cb.id
                                  LEFT JOIN equipe_gymnase eg_equipe ON eg_equipe.id_equipe = e.id_equipe
                                  LEFT JOIN equipe_gymnase eg_match ON eg_match.id_equipe = m.id_equipe_dom
                         WHERE m.match_status IN ('CONFIRMED', 'NOT_CONFIRMED'))
SELECT code_competition                                              AS competition,
       division,
       nom_club                                                      AS club,
       nom_equipe                                                    AS equipe,
       COUNT(*)                                                      AS nb_matchs,
       SUM(CASE WHEN distance_km = 0 THEN 1 ELSE 0 END)              AS matchs_domicile,
       SUM(CASE WHEN distance_km > 0 THEN 1 ELSE 0 END)              AS matchs_exterieur,
       ROUND(SUM(distance_km), 1)                                    AS total_km,
       ROUND(AVG(CASE WHEN distance_km > 0 THEN distance_km END), 1) AS moyenne_km_deplacement
FROM match_distances
GROUP BY code_competition, division, nom_club, nom_equipe
ORDER BY competition, division, total_km DESC