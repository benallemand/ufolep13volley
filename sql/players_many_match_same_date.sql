-- Soirs où des joueurs sont attendus dans plusieurs matchs, dont au moins un
-- de coupe : une équipe de coupe y perdrait ses joueurs. Un joueur déjà
-- inscrit sur la feuille d'un des matchs n'est plus compté.
--
-- Issue #397 :
--   - un joueur se reconnaît à son identifiant, plus à son nom ;
--   - la coupe se lit sur le code de compétition, plus par `LIKE '%C%'` sur
--     le code du match ;
--   - un membre non jouant (#325) n'est attendu dans aucun match.
WITH par_joueur AS (SELECT j.id,
                           GROUP_CONCAT(DISTINCT CONCAT(e.nom_equipe, ' (', e.code_competition, ')')
                                        ORDER BY e.nom_equipe SEPARATOR ', ') AS equipes,
                           m.date_reception
                    FROM equipes e
                             JOIN matches m ON e.id_equipe = m.id_equipe_dom OR e.id_equipe = m.id_equipe_ext
                             JOIN joueur_equipe je ON e.id_equipe = je.id_equipe
                             JOIN joueurs j ON je.id_joueur = j.id
                    WHERE j.id NOT IN (SELECT id_player FROM match_player WHERE id_match = m.id_match)
                      AND je.est_jouant + 0 > 0
                      AND m.match_status IN ('NOT_CONFIRMED', 'CONFIRMED')
                      AND m.date_reception > CURRENT_DATE
                    GROUP BY j.id, m.date_reception
                    HAVING COUNT(DISTINCT m.id_match) > 1
                       AND MAX(m.code_competition IN ('c', 'cf', 'kh', 'kf')) = 1)
SELECT COUNT(*)                                  AS nb_joueurs,
       equipes,
       DATE_FORMAT(date_reception, '%d/%m/%Y')   AS jour
FROM par_joueur
GROUP BY equipes, date_reception
ORDER BY nb_joueurs DESC, date_reception
