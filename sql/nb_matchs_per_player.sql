SELECT c.nom                                                              as club,
       GROUP_CONCAT(DISTINCT e.nom_equipe)                                as equipes,
       CONCAT(j.prenom, ' ', j.nom)                                       AS joueur,
       COUNT(DISTINCT mp.id_match)                                        AS nb_matchs_joues,
       GROUP_CONCAT(DISTINCT m.code_match ORDER BY m.date_reception DESC) AS derniers_matchs
FROM match_player mp
         JOIN matches m ON mp.id_match = m.id_match
         JOIN joueurs j ON j.id = mp.id_player
         JOIN joueur_equipe je ON je.id_joueur = j.id AND je.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
         JOIN equipes e ON je.id_equipe = e.id_equipe
         JOIN clubs c ON c.id = j.id_club
GROUP BY CONCAT(j.prenom, ' ', j.nom)
ORDER BY nb_matchs_joues,
         club,
         joueur