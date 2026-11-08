-- Palmarès public : une ligne par podium (ligue, période, division, demi-saison).
--
-- Le podium part du PREMIER (champion, vainqueur) ; le second (vice-champion,
-- finaliste) est facultatif. Les archives d'avant 2015-2016 (ajoutées le
-- 08/11/2026) n'ont souvent pas de second : avec une jointure interne, ces
-- podiums disparaissaient de la page.
--
-- `division` : le numéro après « Division », « Poule N » pour la mi-saison
-- féminine 2009-2010 jouée en poules, vide pour une coupe.
SELECT c.period,
       CASE
           WHEN c.title LIKE '%Division %' THEN SUBSTRING_INDEX(c.title, 'Division ', -1)
           WHEN c.title LIKE '%Poule %' THEN CONCAT('Poule ', SUBSTRING_INDEX(c.title, 'Poule ', -1))
           ELSE ''
       END                                      AS division,
       IF(c.title LIKE '%mi-saison%', 1, 2)     AS demi_saison,
       c.team_name                              AS champion,
       v.team_name                              AS vice_champion,
       c.league,
       CONCAT_WS(',', c.id, v.id)               AS ids
FROM hall_of_fame c
         LEFT JOIN hall_of_fame v ON
    v.league = c.league AND
    v.period = c.period AND
    (CASE
         WHEN v.title LIKE '%Division %' THEN SUBSTRING_INDEX(v.title, 'Division ', -1)
         WHEN v.title LIKE '%Poule %' THEN CONCAT('Poule ', SUBSTRING_INDEX(v.title, 'Poule ', -1))
         ELSE ''
     END) = (CASE
                 WHEN c.title LIKE '%Division %' THEN SUBSTRING_INDEX(c.title, 'Division ', -1)
                 WHEN c.title LIKE '%Poule %' THEN CONCAT('Poule ', SUBSTRING_INDEX(c.title, 'Poule ', -1))
                 ELSE ''
             END) AND
    IF(v.title LIKE '%mi-saison%', 1, 2) = IF(c.title LIKE '%mi-saison%', 1, 2) AND
    (v.title LIKE '%Vice%' OR v.title LIKE '%Finaliste%')
WHERE c.title NOT LIKE '%Vice%'
  AND c.title NOT LIKE '%Finaliste%'
ORDER BY c.period DESC,
         c.league,
         division,
         demi_saison
