SELECT @r := @r + 1 AS rang,
       z.*
FROM (SELECT e.id_equipe,
             e.nom_equipe                                                                   AS equipe,
             c.code_competition,
             c.division,
             (SELECT rv.rang 
              FROM ranks_view rv 
              WHERE rv.code_competition = c.code_competition 
                AND rv.division = c.division 
                AND rv.id_equipe = e.id_equipe)                                             AS rang_poule,
             (SUM(IF(e.id_equipe = m.id_equipe_dom, 1, 0)) +
              SUM(IF(e.id_equipe = m.id_equipe_ext, 1, 0)))                                 AS nb_matchs,
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0))
                 - c.penalite                                                               AS points,
             (SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 3, 0)) +
              SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 3, 0)) +
              SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3 AND m.forfait_dom = 0, 1, 0)) +
              SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3 AND m.forfait_ext = 0, 1, 0))
                 -
              c.penalite) / (SUM(IF(e.id_equipe = m.id_equipe_dom, 1, 0)) +
                             SUM(IF(e.id_equipe = m.id_equipe_ext, 1, 0)))                  AS points_ponderes,
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))        AS joues,
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_dom = 3, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_ext = 3, 1, 0))        AS gagnes,
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.score_equipe_ext = 3, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.score_equipe_dom = 3, 1, 0))        AS perdus,
             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom,
                    m.score_equipe_ext))                                                    AS sets_pour,
             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext,
                    m.score_equipe_dom))                                                    AS sets_contre,
             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) -
             SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext,
                    m.score_equipe_dom))                                                    AS diff_sets,
             (SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_dom, m.score_equipe_ext)) -
              SUM(IF(e.id_equipe = m.id_equipe_dom, m.score_equipe_ext,
                     m.score_equipe_dom))) / (SUM(IF(e.id_equipe = m.id_equipe_dom, 1, 0)) +
                                              SUM(IF(e.id_equipe = m.id_equipe_ext, 1, 0))) AS diff_sets_ponderes,
             SUM(IF(m.id_equipe_dom = e.id_equipe, m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom + m.set_5_dom,
                    m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext +
                    m.set_5_ext))                                                           AS points_pour,
             SUM(IF(m.id_equipe_dom = e.id_equipe, m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext + m.set_5_ext,
                    m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom +
                    m.set_5_dom))                                                           AS points_contre,
             SUM(IF(m.id_equipe_dom = e.id_equipe, m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom + m.set_5_dom,
                    m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext + m.set_5_ext)) - SUM(IF(
                     m.id_equipe_dom = e.id_equipe, m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext + m.set_5_ext,
                     m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom +
                     m.set_5_dom))                                                          AS diff_points,
             (SUM(IF(m.id_equipe_dom = e.id_equipe, m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom + m.set_5_dom,
                     m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext + m.set_5_ext)) - SUM(IF(
                     m.id_equipe_dom = e.id_equipe, m.set_1_ext + m.set_2_ext + m.set_3_ext + m.set_4_ext + m.set_5_ext,
                     m.set_1_dom + m.set_2_dom + m.set_3_dom + m.set_4_dom + m.set_5_dom))) /
             (SUM(IF(e.id_equipe = m.id_equipe_dom, 1, 0)) +
              SUM(IF(e.id_equipe = m.id_equipe_ext, 1, 0)))                                 AS diff_points_ponderes,
             c.penalite                                                                     AS penalites,
             SUM(IF(e.id_equipe = m.id_equipe_dom AND m.forfait_dom = 1, 1, 0)) +
             SUM(IF(e.id_equipe = m.id_equipe_ext AND m.forfait_ext = 1, 1, 0))             AS matches_lost_by_forfeit_count,
             c.report_count
      FROM classements c
               JOIN equipes e ON e.id_equipe = c.id_equipe
               LEFT JOIN matchs_view m ON
          m.code_competition = c.code_competition
              AND m.division = c.division
              AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
              AND m.match_status != 'ARCHIVED'
      WHERE c.code_competition = ?
      GROUP BY e.id_equipe, '%name', e.nom_equipe, c.penalite, c.report_count
      ORDER BY points_ponderes DESC, diff_sets_ponderes DESC, diff_points_ponderes DESC, c.rank_start) z,
     (SELECT @r := 0) y