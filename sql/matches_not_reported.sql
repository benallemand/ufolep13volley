SELECT m.code_match,
       c1.nom       AS club_reception,
       m.equipe_dom AS equipe_reception,
       jresp1.email AS responsable_reception,
       c2.nom       AS club_visiteur,
       m.equipe_ext AS equipe_visiteur,
       jresp2.email AS responsable_visiteur,
       m.date_reception
FROM matchs_view m
         JOIN competitions comp ON comp.code_competition = m.code_competition
         JOIN joueur_equipe jeresp1 ON jeresp1.id_equipe = m.id_equipe_dom AND jeresp1.is_leader + 0 > 0
         JOIN joueur_equipe jeresp2 ON jeresp2.id_equipe = m.id_equipe_ext AND jeresp2.is_leader + 0 > 0
         JOIN joueurs jresp1 ON jresp1.id = jeresp1.id_joueur
         JOIN joueurs jresp2 ON jresp2.id = jeresp2.id_joueur
         JOIN clubs c1 ON c1.id = jresp1.id_club
         JOIN clubs c2 ON c2.id = jresp2.id_club
WHERE (
    (m.score_equipe_dom + m.score_equipe_ext = 0)
        OR
    ((m.set_1_dom + m.set_1_ext = 0) AND (m.score_equipe_dom + m.score_equipe_ext > 0))
        OR
    ((m.set_1_dom + m.set_1_ext > 0) AND (m.score_equipe_dom + m.score_equipe_ext = 0))
    )
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') < CURDATE() - INTERVAL 10 DAY
  AND m.match_status = 'CONFIRMED'
  AND m.certif = 0
ORDER BY m.code_match