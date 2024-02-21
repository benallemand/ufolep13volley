SELECT DISTINCT last_match.id_equipe_dom,
                last_match.id_equipe_ext,
                last_match.equipe_dom,
                last_match.equipe_ext,
                previous_match.code_match     AS prev_code_match,
                previous_match.date_reception AS prev_date,
                last_match.code_match         AS last_code_match,
                last_match.date_reception     AS last_date
FROM matchs_view last_match,
     matchs_view previous_match
WHERE last_match.id_match <> previous_match.id_match
  AND last_match.id_equipe_dom = previous_match.id_equipe_dom
  AND last_match.id_equipe_ext = previous_match.id_equipe_ext
  AND last_match.equipe_dom = previous_match.equipe_dom
  AND last_match.date_reception = (SELECT MAX(date_reception)
                                   FROM matchs_view
                                   WHERE id_equipe_dom = last_match.id_equipe_dom
                                     AND id_equipe_ext = last_match.id_equipe_ext
                                     AND equipe_dom = last_match.equipe_dom
                                     AND date_reception < NOW())
  AND previous_match.date_reception = (SELECT MAX(date_reception)
                                       FROM matchs_view
                                       WHERE id_equipe_dom = last_match.id_equipe_dom
                                         AND id_equipe_ext = last_match.id_equipe_ext
                                         AND equipe_dom = last_match.equipe_dom
                                         AND date_reception < last_match.date_reception)
  AND NOT EXISTS(SELECT 1
                 FROM matchs_view
                 WHERE id_equipe_dom = previous_match.id_equipe_ext
                   AND id_equipe_ext = previous_match.id_equipe_dom
                   AND date_reception BETWEEN previous_match.date_reception AND last_match.date_reception)
  AND STR_TO_DATE(previous_match.date_reception, '%d/%m/%Y') > DATE_SUB(NOW(), INTERVAL 9 MONTH)
  AND STR_TO_DATE(last_match.date_reception, '%d/%m/%Y') > DATE_SUB(NOW(), INTERVAL 9 MONTH)
  AND last_match.id_equipe_ext IN (SELECT id_equipe FROM creneau)
ORDER BY equipe_dom, prev_date