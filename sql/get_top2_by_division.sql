-- Deux premiers de chaque division sur une période (palmarès).
-- Les jetons POINTS et ORDER (entre accolades) sont remplacés par HallOfFame selon le barème de la
-- compétition et de la période (issue #347) : fragments fixes du code, jamais
-- une valeur du client. Paramètres : code, début, fin, code, début, fin.
SELECT rang,
       code_competition,
       division,
       id_equipe,
       equipe,
       club,
       points,
       joues,
       gagnes,
       perdus,
       sets_pour,
       sets_contre,
       diff
FROM (SELECT @rang := IF(@div = CONCAT(code_competition, division), @rang + 1, 1) AS rang,
             @div := CONCAT(code_competition, division)                           AS div_key,
             z.*
      FROM (SELECT code_competition,
                   division,
                   id_equipe,
                   equipe,
                   club,
                   {POINTS}                                                                        AS points,
                   COUNT(*)                                                                        AS joues,
                   SUM(IF(score_pour = 3, 1, 0))                                                   AS gagnes,
                   SUM(IF(score_contre = 3, 1, 0))                                                 AS perdus,
                   SUM(score_pour)                                                                 AS sets_pour,
                   SUM(score_contre)                                                               AS sets_contre,
                   SUM(score_pour) - SUM(score_contre)                                             AS diff,
                   SUM(pts_pour)                                                                   AS pts_pour,
                   SUM(pts_contre)                                                                 AS pts_contre
            FROM (
                     SELECT m.code_competition,
                            m.division,
                            m.id_equipe_dom    AS id_equipe,
                            m.equipe_dom       AS equipe,
                            cl.nom             AS club,
                            m.score_equipe_dom AS score_pour,
                            m.score_equipe_ext AS score_contre,
                            m.forfait_dom      AS forfait,
                            COALESCE(m.set_1_dom, 0) + COALESCE(m.set_2_dom, 0) + COALESCE(m.set_3_dom, 0)
                                + COALESCE(m.set_4_dom, 0) + COALESCE(m.set_5_dom, 0) AS pts_pour,
                            COALESCE(m.set_1_ext, 0) + COALESCE(m.set_2_ext, 0) + COALESCE(m.set_3_ext, 0)
                                + COALESCE(m.set_4_ext, 0) + COALESCE(m.set_5_ext, 0) AS pts_contre
                     FROM matchs_view m
                              LEFT JOIN equipes e ON e.id_equipe = m.id_equipe_dom
                              LEFT JOIN clubs cl ON cl.id = e.id_club
                     WHERE m.certif = 1
                       AND m.code_competition = ?
                       AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN ? AND ?
                       AND (m.score_equipe_dom + m.score_equipe_ext) > 0

                     UNION ALL

                     SELECT m.code_competition,
                            m.division,
                            m.id_equipe_ext    AS id_equipe,
                            m.equipe_ext       AS equipe,
                            cl.nom             AS club,
                            m.score_equipe_ext AS score_pour,
                            m.score_equipe_dom AS score_contre,
                            m.forfait_ext      AS forfait,
                            COALESCE(m.set_1_ext, 0) + COALESCE(m.set_2_ext, 0) + COALESCE(m.set_3_ext, 0)
                                + COALESCE(m.set_4_ext, 0) + COALESCE(m.set_5_ext, 0) AS pts_pour,
                            COALESCE(m.set_1_dom, 0) + COALESCE(m.set_2_dom, 0) + COALESCE(m.set_3_dom, 0)
                                + COALESCE(m.set_4_dom, 0) + COALESCE(m.set_5_dom, 0) AS pts_contre
                     FROM matchs_view m
                              LEFT JOIN equipes e ON e.id_equipe = m.id_equipe_ext
                              LEFT JOIN clubs cl ON cl.id = e.id_club
                     WHERE m.certif = 1
                       AND m.code_competition = ?
                       AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN ? AND ?
                       AND (m.score_equipe_dom + m.score_equipe_ext) > 0
                 ) all_matches
            GROUP BY code_competition, division, id_equipe, equipe, club
            ORDER BY code_competition, division, {ORDER}) z,
           (SELECT @rang := 0, @div := '') r) ranked
WHERE rang <= 2
ORDER BY code_competition, division, rang
