-- `indicator_id` ouvre l'écran de correction filtré sur ces lignes (#312, #409).
-- Sanctions à décider (issue #475) : manquements constatés, automatiquement
-- ou par la commission, qui attendent la réunion de fin de demi-saison. Une
-- sanction appliquée ou levée sort de l'indicateur.
SELECT s.id                                        AS indicator_id,
       DATE_FORMAT(s.created_at, '%d/%m/%Y %H:%i') AS constatee_le,
       e.nom_equipe                                 AS equipe,
       comp.libelle                                 AS competition,
       cl.division,
       m.code_match                                 AS match_,
       CASE s.motif
           WHEN 'feuille_non_signee' THEN 'feuille de match non signée à 7 jours'
           WHEN 'score_non_saisi' THEN 'score non saisi à 7 jours'
           WHEN 'feuille_mal_remplie' THEN 'feuille de match mal remplie'
           WHEN 'joueur_non_licencie' THEN 'joueur sans licence valide'
           WHEN 'report_hors_regle' THEN 'report hors délai ou non communiqué'
           WHEN 'equipe_incomplete' THEN 'équipe incomplète ou absente'
           WHEN 'forfaits_repetes' THEN 'forfaits répétés'
           WHEN 'comportement' THEN 'comportement, incident'
           ELSE 'autre' END                          AS motif,
       CASE s.sanction_type
           WHEN 'points' THEN CONCAT(s.points, ' pt(s) en moins')
           WHEN 'avertissement' THEN 'avertissement'
           WHEN 'defaite_penalite' THEN 'défaite par pénalité'
           WHEN 'forfait' THEN 'match perdu par forfait'
           WHEN 'annulation_match' THEN 'annulation du match'
           WHEN 'forfait_general' THEN 'forfait général'
           ELSE s.sanction_type END                 AS sanction_envisagee
FROM sanctions s
         JOIN equipes e ON e.id_equipe = s.id_equipe
         JOIN competitions comp ON comp.code_competition = s.code_competition
         LEFT JOIN classements cl ON cl.id_equipe = s.id_equipe AND cl.code_competition = s.code_competition
         LEFT JOIN matches m ON m.id_match = s.id_match
WHERE s.status = 'CONSTATEE'
ORDER BY s.created_at DESC, e.nom_equipe
