-- Écran d'administration « Pénalités automatiques » (issue #458) : toutes les
-- pénalités, actives et annulées, les plus récentes d'abord.
SELECT p.id,
       p.id_match,
       p.id_equipe,
       DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i')   AS appliquee_le,
       m.code_match,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y')      AS date_match,
       e.nom_equipe                                   AS equipe,
       comp.libelle                                   AS competition,
       m.division,
       CASE p.reason
           WHEN 'feuille_non_signee_48h' THEN 'feuille de match non signée à 7 jours'
           ELSE p.reason END                          AS motif,
       DATE_FORMAT(p.cancelled_at, '%d/%m/%Y %H:%i') AS annulee_le,
       IF(p.cancelled_at IS NULL, 'active', 'annulée') AS statut
FROM match_penalties p
         JOIN matches m ON m.id_match = p.id_match
         JOIN equipes e ON e.id_equipe = p.id_equipe
         JOIN competitions comp ON comp.code_competition = p.code_competition
ORDER BY p.created_at DESC, m.code_match, e.nom_equipe
