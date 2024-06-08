SELECT e_sondee_club.nom,
       (SUM(3 * s.on_time) + SUM(3 * s.spirit) + SUM(s.referee) + SUM(s.catering) + SUM(5 * s.global)) /
       COUNT(DISTINCT m.code_match)                                      AS note_moyenne,
       COUNT(DISTINCT m.code_match)                                      AS nb_matchs_sondes,
       COUNT(DISTINCT m_joues.code_match)                                AS nb_matchs_joues,
       COUNT(DISTINCT m.code_match) / COUNT(DISTINCT m_joues.code_match) AS ratio_sondes_joues,
       ((SUM(3 * s.on_time) + SUM(3 * s.spirit) + SUM(s.referee) + SUM(s.catering) + SUM(5 * s.global)) /
        COUNT(DISTINCT m.code_match)) * COUNT(DISTINCT m.code_match) /
       COUNT(DISTINCT m_joues.code_match)                                AS note_finale
FROM survey s
         JOIN ufolep_13volley.matches m on s.id_match = m.id_match
         JOIN ufolep_13volley.comptes_acces ca on ca.id = s.user_id
         JOIN ufolep_13volley.equipes e_sondeuse on ca.id_equipe = e_sondeuse.id_equipe
         JOIN equipes e_sondee
              on e_sondee.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext) AND e_sondee.id_equipe != e_sondeuse.id_equipe
         JOIN clubs e_sondee_club ON e_sondeuse.id_club = e_sondee_club.id
         JOIN matches m_joues
              ON m_joues.id_equipe_dom = e_sondee.id_equipe OR m_joues.id_equipe_ext = e_sondee.id_equipe
WHERE s.on_time + s.spirit + s.referee + s.catering + s.global > 0
GROUP BY e_sondee_club.id
ORDER BY note_finale DESC