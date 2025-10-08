SELECT *
FROM matchs_view m
WHERE WEEK(STR_TO_DATE(m.date_reception, '%d/%m/%Y')) = WEEK(CURDATE())
  AND m.match_status = 'CONFIRMED'
ORDER BY m.division, m.journee, STR_TO_DATE(m.date_reception, '%d/%m/%Y') DESC