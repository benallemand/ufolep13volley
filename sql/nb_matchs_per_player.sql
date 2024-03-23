SELECT c.nom                                                              as club,
       e.nom_equipe                                                       as equipe,
       CONCAT(p.prenom, ' ', p.nom)                                       AS joueur,
       COUNT(DISTINCT m.id_match)                                         AS nb_matchs_joues,
       GROUP_CONCAT(DISTINCT m.code_match ORDER BY m.date_reception DESC) AS derniers_matchs
FROM match_player mp
         JOIN matches m ON mp.id_match = m.id_match
         JOIN players_view p ON p.id = mp.id_player
         JOIN joueur_equipe je ON je.id_joueur = p.id
         JOIN equipes e ON je.id_equipe = e.id_equipe
         JOIN clubs c ON c.id = p.id_club
GROUP BY CONCAT(p.prenom, ' ', p.nom), c.nom, e.nom_equipe
ORDER BY nb_matchs_joues,
         club,
         equipe,
         joueur