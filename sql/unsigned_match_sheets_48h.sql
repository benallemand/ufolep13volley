-- Matchs de championnat dont la feuille de match n'est pas signée des deux
-- côtés 48 h après l'horaire du match, et pas encore pénalisés (issue #345).
--
-- Horaire : date du match + heure du créneau de l'équipe qui reçoit ; sans
-- créneau connu, fin de journée (23:59). Les matchs non confirmés, certifiés
-- ou archivés sont exclus, ainsi que ceux d'avant la mise en service.
-- Paramètres (dans l'ordre) : date de mise en service (aaaa-mm-jj), motif.
SELECT m.id_match,
       m.code_match,
       m.code_competition,
       m.id_equipe_dom,
       m.id_equipe_ext,
       m.equipe_dom,
       m.equipe_ext,
       m.email_dom,
       m.email_ext,
       m.date_reception,
       m.is_sign_match_dom,
       m.is_sign_match_ext
FROM matchs_view m
WHERE m.code_competition IN ('m', 'f', 'mo')
  AND m.match_status = 'CONFIRMED'
  AND m.certif = 0
  AND NOT (m.is_sign_match_dom = 1 AND m.is_sign_match_ext = 1)
  AND STR_TO_DATE(m.date_reception, '%d/%m/%Y') >= ?
  AND STR_TO_DATE(CONCAT(m.date_reception, ' ', COALESCE(NULLIF(m.heure_reception, ''), '23:59')), '%d/%m/%Y %H:%i')
      + INTERVAL 48 HOUR < NOW()
  AND NOT EXISTS (SELECT 1 FROM match_penalties p WHERE p.id_match = m.id_match AND p.reason = ?)
ORDER BY m.date_reception
