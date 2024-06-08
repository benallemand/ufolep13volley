SELECT e_sondee_club.nom                                                 AS club,
       GROUP_CONCAT(DISTINCT e_sondee.nom_equipe)                        AS equipes,
       count(DISTINCT e_sondee.id_equipe)                                AS nb_equipes_sondees,
       CASE WHEN s.on_time > 0 THEN 1 ELSE 0 END +
       CASE WHEN s.spirit > 0 THEN 1 ELSE 0 END +
       CASE WHEN s.referee > 0 THEN 1 ELSE 0 END +
       CASE WHEN s.catering > 0 THEN 1 ELSE 0 END                        AS nb_survey_filled_fields,
       (SUM(3 * s.on_time) + SUM(3 * s.spirit) + SUM(s.referee) + SUM(s.catering) + SUM(5 * s.global)) /
       (CASE WHEN s.on_time > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.spirit > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.referee > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.catering > 0 THEN 1 ELSE 0 END)                      AS note_moyenne,
       COUNT(DISTINCT m.code_match)                                      AS nb_matchs_sondes,
       COUNT(DISTINCT m_joues.code_match)                                AS nb_matchs_joues,
       COUNT(DISTINCT m.code_match) / COUNT(DISTINCT m_joues.code_match) AS ratio_sondes_joues,
       (SUM(3 * s.on_time) + SUM(3 * s.spirit) + SUM(s.referee) + SUM(s.catering) + SUM(5 * s.global)) /
       (CASE WHEN s.on_time > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.spirit > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.referee > 0 THEN 1 ELSE 0 END +
        CASE WHEN s.catering > 0 THEN 1 ELSE 0 END) * COUNT(DISTINCT m.code_match) /
       COUNT(DISTINCT m_joues.code_match)                                AS note_finale
FROM survey s
         JOIN matches m on s.id_match = m.id_match
         JOIN comptes_acces ca on ca.id = s.user_id
         JOIN equipes e_sondeuse on ca.id_equipe = e_sondeuse.id_equipe
         JOIN equipes e_sondee
              on e_sondee.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext) AND e_sondee.id_equipe != e_sondeuse.id_equipe
         JOIN clubs e_sondee_club ON e_sondee_club.id = e_sondee.id_club
         JOIN matches m_joues
              ON m_joues.id_equipe_dom = e_sondee.id_equipe OR m_joues.id_equipe_ext = e_sondee.id_equipe
WHERE s.on_time + s.spirit + s.referee + s.catering + s.global > 0
GROUP BY e_sondee_club.id
ORDER BY note_finale DESC