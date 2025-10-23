SELECT SUBSTRING(comment, 10, LOCATE('(', comment) - 11)                         AS joueur,
       GROUP_CONCAT(DISTINCT SUBSTRING(comment, LOCATE('equipe ', comment) + 7)) AS equipes,
       GROUP_CONCAT(DISTINCT DATE_FORMAT(activity_date, '%d/%m/%Y'))             AS dates
FROM activity,
     competitions,
     dates_limite
WHERE comment LIKE 'Ajout de %'
  AND MID(comment, LOCATE('(', comment) + 1, 8) REGEXP '[0-9]+'
  AND activity_date BETWEEN (SELECT MIN(start_date) FROM competitions) AND (SELECT MAX(date_limite) FROM dates_limite)
GROUP BY MID(comment, LOCATE('(', comment) + 1, 8),
         SUBSTRING(SUBSTRING_INDEX(comment, '(', -1), 1, LENGTH(SUBSTRING_INDEX(comment, '(', -1)) - 1)
HAVING COUNT(DISTINCT SUBSTRING(comment, LOCATE('equipe ', comment) + 7)) > 1