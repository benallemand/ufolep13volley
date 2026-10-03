-- Joueurs venus en renfort dans la saison en cours.
--
-- Issue #397 :
--   - un renfort se reconnaît d'abord à `match_player.id_team_reinforced`
--     (#348), qui dit aussi quelle équipe il renforce. Pour une feuille saisie
--     avant #348, on retombe sur la règle de #349 : présent sur la feuille sans
--     être membre d'aucune des deux équipes ; l'équipe renforcée est alors
--     « non précisée » ;
--   - bornée à la saison en cours (ouverte le 1er juillet, comme
--     `CalendarEvents::getCurrentSeason()`). L'appartenance aux équipes est
--     celle d'aujourd'hui : à l'intersaison, les joueurs déjà passés dans une
--     équipe de la nouvelle saison passaient pour des renforts sur tous leurs
--     matchs de l'an dernier ;
--   - un joueur sans équipe (licencié du club seulement) n'est plus écarté, et
--     les joueurs sont regroupés par identifiant, plus par nom.
SELECT CONCAT(j.prenom, ' ', j.nom)                                          AS joueur,
       club.nom                                                              AS club,
       COUNT(DISTINCT mp.id_match)                                           AS nb_renforts,
       GROUP_CONCAT(DISTINCT m.code_match ORDER BY m.date_reception)         AS matchs,
       GROUP_CONCAT(DISTINCT COALESCE(renforcee.nom_equipe, 'non précisée')) AS equipes_renforcees,
       (SELECT GROUP_CONCAT(DISTINCT CONCAT(e.nom_equipe, ' (', cl.code_competition, cl.division, ')')
                            ORDER BY e.nom_equipe SEPARATOR ', ')
        FROM joueur_equipe je
                 JOIN equipes e ON e.id_equipe = je.id_equipe
                 JOIN classements cl ON cl.id_equipe = e.id_equipe
        WHERE je.id_joueur = j.id)                                           AS ses_equipes
FROM match_player mp
         JOIN matches m ON mp.id_match = m.id_match
         JOIN joueurs j ON mp.id_player = j.id
         LEFT JOIN clubs club ON club.id = j.id_club
         LEFT JOIN equipes renforcee ON renforcee.id_equipe = mp.id_team_reinforced
WHERE m.date_reception >= MAKEDATE(IF(MONTH(CURRENT_DATE) <= 6, YEAR(CURRENT_DATE) - 1, YEAR(CURRENT_DATE)), 1)
                              + INTERVAL 6 MONTH
  AND (mp.id_team_reinforced IS NOT NULL
    OR mp.id_player NOT IN (SELECT id_joueur
                            FROM joueur_equipe
                            WHERE id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)))
GROUP BY j.id, j.prenom, j.nom, club.nom
ORDER BY nb_renforts DESC, joueur
