SELECT *
FROM matchs_view m
         JOIN journees j ON j.id = m.id_journee
WHERE WEEK(STR_TO_DATE(m.date_reception, '%d/%m/%Y')) = WEEK(CURDATE())
  AND m.match_status = 'CONFIRMED'
ORDER BY m.division, j.nommage, STR_TO_DATE(m.date_reception, '%d/%m/%Y') DESC