SELECT hof.period,
       IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), '') AS division,
       IF(hof.title LIKE '%mi-saison%', 1, 2)                                           AS demi_saison,
       hof_champion.team_name                                                           AS champion,
       hof_vice_champion.team_name                                                      AS vice_champion,
       hof.league,
       CONCAT(hof_champion.id, ',', hof_vice_champion.id)                               AS ids
FROM hall_of_fame hof
         JOIN hall_of_fame hof_champion ON hof_champion.league = hof.league AND
                                           hof_champion.period = hof.period AND
                                           (IF(hof_champion.title LIKE '%Division%',
                                               SUBSTRING_INDEX(hof_champion.title, 'Division ', -1),
                                               '')) = (IF(hof.title LIKE '%Division%',
                                                          SUBSTRING_INDEX(hof.title, 'Division ', -1),
                                                          '')) AND
                                           (IF(hof_champion.title LIKE '%mi-saison%', 1, 2)) =
                                           (IF(hof.title LIKE '%mi-saison%', 1, 2)) AND
                                           (hof_champion.title NOT LIKE '%Vice%' AND
                                            hof_champion.title NOT LIKE '%Finaliste%')
         JOIN hall_of_fame hof_vice_champion ON
    hof_vice_champion.league = hof.league AND
    hof_vice_champion.period = hof.period AND
    (IF(hof_vice_champion.title LIKE '%Division%', SUBSTRING_INDEX(hof_vice_champion.title, 'Division ', -1), '')) =
    (IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), '')) AND
    (IF(hof_vice_champion.title LIKE '%mi-saison%', 1, 2)) = (IF(hof.title LIKE '%mi-saison%', 1, 2)) AND
    (hof_vice_champion.title LIKE '%Vice%' OR
     hof_vice_champion.title LIKE '%Finaliste%')
GROUP BY hof.league,
         hof.period,
         IF(hof.title LIKE '%Division%', SUBSTRING_INDEX(hof.title, 'Division ', -1), ''),
         IF(hof.title LIKE '%mi-saison%', 1, 2)
ORDER BY period DESC,
         league,
         division,
         demi_saison