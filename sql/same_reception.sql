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
  -- Les 2 matchs concernent les mêmes équipes (dans n'importe quel sens)
  AND LEAST(last_match.id_equipe_dom, last_match.id_equipe_ext) =
      LEAST(previous_match.id_equipe_dom, previous_match.id_equipe_ext)
  AND GREATEST(last_match.id_equipe_dom, last_match.id_equipe_ext) =
      GREATEST(previous_match.id_equipe_dom, previous_match.id_equipe_ext)
  -- Mais la même équipe recevait les 2 fois (problème!)
  AND last_match.id_equipe_dom = previous_match.id_equipe_dom
  -- last_match est le plus récent entre ces 2 équipes (passé OU futur)
  AND last_match.date_reception = (SELECT MAX(date_reception)
                                   FROM matchs_view
                                   WHERE LEAST(id_equipe_dom, id_equipe_ext) =
                                         LEAST(last_match.id_equipe_dom, last_match.id_equipe_ext)
                                     AND GREATEST(id_equipe_dom, id_equipe_ext) =
                                         GREATEST(last_match.id_equipe_dom, last_match.id_equipe_ext))
  -- previous_match est le 2ème plus récent entre ces 2 équipes
  AND previous_match.date_reception = (SELECT MAX(date_reception)
                                       FROM matchs_view
                                       WHERE LEAST(id_equipe_dom, id_equipe_ext) =
                                             LEAST(last_match.id_equipe_dom, last_match.id_equipe_ext)
                                         AND GREATEST(id_equipe_dom, id_equipe_ext) =
                                             GREATEST(last_match.id_equipe_dom, last_match.id_equipe_ext)
                                         AND date_reception < last_match.date_reception)
  AND STR_TO_DATE(previous_match.date_reception, '%d/%m/%Y') > DATE_SUB(NOW(), INTERVAL 9 MONTH)
  AND last_match.id_equipe_ext IN (SELECT id_equipe FROM creneau)
ORDER BY last_match.equipe_dom, prev_date