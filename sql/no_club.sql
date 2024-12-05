SELECT DISTINCT j.prenom,
                j.nom,
                CONCAT(j.departement_affiliation, '_', j.num_licence) AS num_licence,
                e.nom_equipe                                          AS nom_equipe
FROM joueurs j
         JOIN joueur_equipe je ON je.id_joueur = j.id
         JOIN equipes e ON e.id_equipe = je.id_equipe
WHERE j.id_club = 0
ORDER BY j.id