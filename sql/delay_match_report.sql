SELECT m.code_match                              AS code,
       c.libelle                                 AS competition,
       m.division                                AS division_poule,
       e1.nom_equipe                             AS domicile,
       e2.nom_equipe                             AS exterieur,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS 'date'
FROM matches m
         LEFT JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         LEFT JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
         LEFT JOIN competitions c ON c.code_competition = m.code_competition
WHERE (
        (m.score_equipe_dom + m.score_equipe_ext + 0 = 0)
        OR
        ((m.set_1_dom + m.set_1_ext = 0) AND (m.score_equipe_dom + m.score_equipe_ext > 0))
        OR
        ((m.set_1_dom + m.set_1_ext > 0) AND (m.score_equipe_dom + m.score_equipe_ext + 0 = 0))
    )
  AND m.match_status = 'CONFIRMED'
  AND m.date_reception < CURDATE() - INTERVAL 5 DAY