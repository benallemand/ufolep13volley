SELECT *
FROM matchs_view m
WHERE (m.score_equipe_dom != 0 OR m.score_equipe_ext != 0)
  AND m.match_status = 'CONFIRMED'
  AND m.date_reception IS NOT NULL
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') <= CURDATE()
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') >= DATE_ADD(CURDATE(), INTERVAL -10 DAY)
ORDER BY STR_TO_DATE(m.date_reception, '%d/%m/%Y') DESC