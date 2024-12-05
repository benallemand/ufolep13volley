SELECT DISTINCT cl.nom             AS club,
                e.nom_equipe       AS ancien_nom,
                e.code_competition AS competition,
                c.division         AS division,
                j.email            AS responsable
FROM classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         JOIN clubs cl ON cl.id = e.id_club
         JOIN joueur_equipe je ON e.id_equipe = je.id_equipe AND je.is_leader = 1
         JOIN joueurs j ON je.id_joueur = j.id
WHERE c.will_register_again = 0
  AND c.code_competition IN ('m', 'f', 'mo')
ORDER BY competition, division, club, ancien_nom