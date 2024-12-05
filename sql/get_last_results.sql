SELECT m.libelle_competition                              AS competition,
       IF(m.code_competition = 'f' OR m.code_competition = 'm' OR m.code_competition = 'mo',
          CONCAT('Division ', m.division, ' - ', j.nommage),
          CONCAT('Poule ', m.division, ' - ', j.nommage)) AS division_journee,
       m.code_competition,
       m.division,
       m.id_equipe_dom                                    AS id_dom,
       m.equipe_dom                                       AS equipe_domicile,
       m.score_equipe_dom,
       m.score_equipe_ext,
       m.id_equipe_ext                                    AS id_ext,
       m.equipe_ext                                       AS equipe_exterieur,
       CONCAT(m.set_1_dom, '-', set_1_ext)                AS set1,
       CONCAT(m.set_2_dom, '-', set_2_ext)                AS set2,
       CONCAT(m.set_3_dom, '-', set_3_ext)                AS set3,
       CONCAT(m.set_4_dom, '-', set_4_ext)                AS set4,
       CONCAT(m.set_5_dom, '-', set_5_ext)                AS set5,
       STR_TO_DATE(m.date_reception, '%d/%m/%Y')          AS date_reception
FROM matchs_view m
         JOIN journees j ON j.id = m.id_journee
WHERE (m.score_equipe_dom != 0 OR m.score_equipe_ext != 0)
  AND m.match_status = 'CONFIRMED'
  AND m.date_reception IS NOT NULL
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') <= CURDATE()
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') >= DATE_ADD(CURDATE(), INTERVAL -10 DAY)
ORDER BY competition, m.division, j.nommage, STR_TO_DATE(m.date_reception, '%d/%m/%Y') DESC