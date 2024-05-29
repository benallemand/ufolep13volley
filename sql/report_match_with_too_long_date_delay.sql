SELECT REVERSE(SUBSTRING_INDEX(REVERSE(accept.comment), ' ', 1))                                   AS code_match,
       SUBSTRING_INDEX(
               SUBSTRING_INDEX(accept.comment, ' pour le match', 1),
               'Report accepté par ',
               -1)                                                                                 AS equipe_suspecte,
       DATE_FORMAT(accept.activity_date, '%d/%m/%Y')                                               AS date_acceptation,
       DATE_FORMAT(reponse.activity_date, '%d/%m/%Y')                                              AS date_transmise,
       CASE
           WHEN reponse.activity_date IS NULL THEN
               DATEDIFF(current_date, accept.activity_date)
           ELSE
               DATEDIFF(reponse.activity_date, accept.activity_date)
           END                                                                                     AS delai_jours,
       CONCAT(m.equipe_dom, ' (', m.score_equipe_dom, '-', m.score_equipe_ext, ') ', m.equipe_ext) AS score
FROM activity accept
         LEFT JOIN activity reponse ON REVERSE(SUBSTRING_INDEX(REVERSE(reponse.comment), ' ', 1)) =
                                       REVERSE(SUBSTRING_INDEX(REVERSE(accept.comment), ' ', 1))
    AND reponse.comment LIKE 'Date de report transmise par%'
         JOIN matchs_view m ON m.code_match = REVERSE(SUBSTRING_INDEX(REVERSE(accept.comment), ' ', 1))
WHERE accept.comment LIKE 'Report accepté par%'
  AND CASE
          WHEN reponse.activity_date IS NULL THEN
              DATEDIFF(current_date, accept.activity_date)
          ELSE
              DATEDIFF(reponse.activity_date, accept.activity_date)
          END > 10
  AND REVERSE(SUBSTRING_INDEX(REVERSE(accept.comment), ' ', 1)) IN
      (SELECT code_match FROM matches WHERE match_status NOT IN ('ARCHIVED'))
ORDER BY delai_jours DESC