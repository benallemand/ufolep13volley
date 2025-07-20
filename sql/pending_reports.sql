SELECT m.code_match                              AS match_reference,
       e1.nom_equipe                             AS team_home,
       jresp1.email                              AS email_home,
       e2.nom_equipe                             AS team_guest,
       jresp2.email                              AS email_guest,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS original_match_date
FROM matchs_view m
         JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
         JOIN joueur_equipe jeresp1 ON jeresp1.id_equipe = e1.id_equipe AND jeresp1.is_leader + 0 > 0
         JOIN joueur_equipe jeresp2 ON jeresp2.id_equipe = e2.id_equipe AND jeresp2.is_leader + 0 > 0
         JOIN joueurs jresp1 ON jresp1.id = jeresp1.id_joueur
         JOIN joueurs jresp2 ON jresp2.id = jeresp2.id_joueur
WHERE m.report_status IN ('ASKED_BY_DOM', 'ASKED_BY_EXT')
  AND m.match_status = 'CONFIRMED'
  AND m.sheet_received = 0
  AND m.certif = 0
ORDER BY m.code_match