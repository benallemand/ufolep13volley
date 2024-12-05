SELECT m.code_match                              AS code,
       c.libelle                                 AS competition,
       m.division                                AS division_poule,
       m.equipe_dom                              AS domicile,
       m.equipe_ext                              AS exterieur,
       m.date_reception AS 'date'
FROM matchs_view m
         LEFT JOIN competitions c ON c.code_competition = m.code_competition
WHERE (
    (m.score_equipe_dom + m.score_equipe_ext = 0)
        OR
    ((m.set_1_dom + m.set_1_ext = 0) AND (m.score_equipe_dom + m.score_equipe_ext > 0))
        OR
    ((m.set_1_dom + m.set_1_ext > 0) AND (m.score_equipe_dom + m.score_equipe_ext = 0))
    )
  AND m.match_status = 'CONFIRMED'
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') < CURDATE() - INTERVAL 5 DAY