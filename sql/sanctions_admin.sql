-- Écran d'administration « Sanctions » (issue #475) : toutes les sanctions,
-- les manquements à décider d'abord, puis les plus récents.
SELECT s.id,
       s.code_competition,
       s.id_equipe,
       s.id_match,
       s.motif,
       s.sanction_type,
       s.points,
       s.comment,
       s.source,
       s.status,
       CASE s.status
           WHEN 'CONSTATEE' THEN 'à décider'
           WHEN 'APPLIQUEE' THEN 'appliquée'
           ELSE 'levée' END                           AS statut,
       IF(s.source = 'AUTO', 'automatique', 'commission') AS origine,
       DATE_FORMAT(s.created_at, '%d/%m/%Y %H:%i')    AS constatee_le,
       DATE_FORMAT(s.decided_at, '%d/%m/%Y %H:%i')    AS decidee_le,
       s.decision_comment,
       e.nom_equipe                                   AS equipe,
       comp.libelle                                   AS competition,
       cl.division,
       m.code_match,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y')      AS date_match
FROM sanctions s
         JOIN equipes e ON e.id_equipe = s.id_equipe
         JOIN competitions comp ON comp.code_competition = s.code_competition
         LEFT JOIN classements cl ON cl.id_equipe = s.id_equipe AND cl.code_competition = s.code_competition
         LEFT JOIN matches m ON m.id_match = s.id_match
ORDER BY s.status = 'CONSTATEE' DESC, s.created_at DESC, e.nom_equipe
