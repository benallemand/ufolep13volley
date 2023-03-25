SELECT DISTINCT j.prenom,
                j.nom,
                CONCAT(j.departement_affiliation, '_', j.num_licence) AS num_licence,
                j.club                                                AS nom_club
FROM players_view j
         JOIN joueur_equipe je ON je.id_joueur = j.id
         JOIN clubs c ON c.id = j.id_club
         JOIN equipes e on je.id_equipe = e.id_equipe
         JOIN competitions c2 on e.code_competition = c2.code_competition
WHERE j.est_actif = 0
  AND je.id_equipe IN (SELECT id_equipe FROM classements)
  AND CURRENT_DATE >= c2.start_date
ORDER BY j.id ASC