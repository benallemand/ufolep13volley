SELECT sub_sql2.club,
       SUM(sub_sql2.total)                             AS total_club,
       SUM(sub_sql2.total_pondere) / sub_sql2.nb_match AS total_club_pondere
FROM (SELECT sub_sql.club,
             COUNT(DISTINCT sub_sql.code_match)                      AS nb_match,
             SUM(sub_sql.total)                                      AS total,
             SUM(sub_sql.total) / COUNT(DISTINCT sub_sql.code_match) AS total_pondere
      FROM (SELECT DISTINCT m.code_match,
                            e_sondee_club.nom                         AS club,
                            e_sondee.nom_equipe                       AS equipe,
                            (s.on_time * 2 +
                             s.spirit * 3 +
                             CASE
                                 WHEN m.referee = 'HOME' AND m.id_equipe_dom = e_sondeuse.id_equipe THEN 0
                                 WHEN m.referee = 'AWAY' AND m.id_equipe_ext = e_sondeuse.id_equipe THEN 0
                                 WHEN m.referee = 'BOTH' THEN 0
                                 ELSE s.referee * 3 END +
                             CASE
                                 WHEN m.id_equipe_dom = e_sondeuse.id_equipe THEN 0
                                 ELSE s.catering * 2 END +
                             s.global * 5) / (IF(s.on_time > 0, 2, 0) +
                                              IF(s.spirit > 0, 3, 0) +
                                              CASE
                                                  WHEN m.referee = 'HOME' AND m.id_equipe_dom = e_sondeuse.id_equipe
                                                      THEN 0
                                                  WHEN m.referee = 'AWAY' AND m.id_equipe_ext = e_sondeuse.id_equipe
                                                      THEN 0
                                                  WHEN m.referee = 'BOTH' THEN 0
                                                  ELSE IF(s.referee > 0, 3, 0) END +
                                              CASE
                                                  WHEN m.id_equipe_dom = e_sondeuse.id_equipe THEN 0
                                                  ELSE IF(s.catering > 0, 2, 0) END +
                                              IF(s.global > 0, 5, 0)) AS total,
                            c.penalite
            FROM survey s
                     JOIN matches m on s.id_match = m.id_match
                     JOIN comptes_acces ca on ca.id = s.user_id
                     JOIN equipes e_sondeuse on ca.id_equipe = e_sondeuse.id_equipe
                     JOIN equipes e_sondee
                          on e_sondee.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext) AND
                             e_sondee.id_equipe != e_sondeuse.id_equipe
                     JOIN clubs e_sondee_club ON e_sondee_club.id = e_sondee.id_club
                     JOIN matches m_joues
                          ON m_joues.id_equipe_dom = e_sondee.id_equipe OR m_joues.id_equipe_ext = e_sondee.id_equipe
                     JOIN classements c ON e_sondeuse.id_equipe = c.id_equipe
            WHERE s.on_time + s.spirit + s.referee + s.catering + s.global > 0
            ORDER BY penalite, total DESC) sub_sql
      GROUP BY sub_sql.equipe
      ORDER BY total_pondere DESC) sub_sql2
GROUP BY sub_sql2.club
ORDER BY total_club_pondere DESC