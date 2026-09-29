-- Pénalités automatiques appliquées (issue #345), les plus récentes d'abord.
-- Une ligne par équipe pénalisée ; l'admin annule par le bouton « -1 » du
-- classement de la division (la ligne reste ici comme historique).
SELECT DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') AS appliquee_le,
       m.code_match                                 AS match_,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y')    AS date_match,
       e.nom_equipe                                 AS equipe,
       comp.libelle                                 AS competition,
       m.division,
       CASE p.reason
           WHEN 'feuille_non_signee_48h' THEN 'feuille de match non signée à 48 h'
           ELSE p.reason END                        AS motif
FROM match_penalties p
         JOIN matches m ON m.id_match = p.id_match
         JOIN equipes e ON e.id_equipe = p.id_equipe
         JOIN competitions comp ON comp.code_competition = p.code_competition
ORDER BY p.created_at DESC, m.code_match, e.nom_equipe
