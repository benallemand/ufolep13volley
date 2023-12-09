SELECT e_sondee.nom_equipe,
       (SUM(s.on_time) + SUM(s.spirit) + SUM(s.referee) + SUM(s.catering) + SUM(s.global)) /
       COUNT(m.code_match) AS note,
       GROUP_CONCAT(m.code_match)
FROM survey s
         JOIN ufolep_13volley.comptes_acces ca on ca.id = s.user_id
         JOIN ufolep_13volley.equipes e_sondeuse on ca.id_equipe = e_sondeuse.id_equipe
         JOIN ufolep_13volley.matches m on s.id_match = m.id_match
         JOIN equipes e_sondee
              on e_sondee.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext) AND e_sondee.id_equipe != e_sondeuse.id_equipe
WHERE s.on_time + s.spirit + s.referee + s.catering + s.global > 0
GROUP BY e_sondee.nom_equipe
ORDER BY note DESC