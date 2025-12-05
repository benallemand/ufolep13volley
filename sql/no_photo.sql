SELECT CONCAT(j.nom, ' ', j.prenom)                                         AS joueur,
       c.nom                                                                AS club,
       GROUP_CONCAT(DISTINCT CONCAT(e.nom_equipe, ' (', comp.libelle, ')')) AS equipe,
       (SELECT COUNT(*) FROM match_player mp WHERE mp.id_player = j.id)     AS nb_matchs
FROM joueur_equipe je
         JOIN players_view j ON j.id = je.id_joueur
         JOIN equipes e ON e.id_equipe = je.id_equipe
         JOIN competitions comp ON comp.code_competition = e.code_competition
         JOIN clubs c ON c.id = j.id_club
WHERE (j.path_photo IS NULL OR j.path_photo = '')
  AND e.id_equipe IN (SELECT id_equipe FROM classements)
  AND CURRENT_DATE >= comp.start_date
  AND j.id IN (SELECT id_player FROM match_player)
GROUP BY j.id, j.nom, j.prenom, c.nom
ORDER BY nb_matchs DESC, equipe
