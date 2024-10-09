SELECT c2.nom                                                                       AS club,
       c2.email_responsable                                                         AS email_club,
       GROUP_CONCAT(r.leader_email)                                                 AS emails_equipes,
       GROUP_CONCAT(CONCAT(r.new_team_name, ' (', c.libelle, ')') SEPARATOR '<br>') AS competitions,
       SUM(IF(c.libelle = 'Championnat Masculin', 10, 5))                           AS cout
FROM register r
         JOIN competitions c on r.id_competition = c.id
         JOIN clubs c2 on r.id_club = c2.id
WHERE UPPER(c.libelle) LIKE ('%CHAMPIONNAT%')
  AND MONTH(r.creation_date) IN (7, 8, 9, 10, 11)
GROUP BY club
ORDER BY club