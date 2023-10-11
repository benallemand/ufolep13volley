SELECT DISTINCT cl.nom             AS club,
                e.nom_equipe       AS ancien_nom,
                e.code_competition AS competition,
                j.email            AS responsable
FROM classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         JOIN clubs cl ON cl.id = e.id_club
         JOIN joueur_equipe je ON e.id_equipe = je.id_equipe AND je.is_leader = 1
         JOIN joueurs j ON je.id_joueur = j.id
WHERE 1 = 1
  AND c.id_equipe NOT IN (SELECT old_team_id FROM register WHERE old_team_id IS NOT NULL)
  AND c.code_competition IN ('m', 'f', 'mo')
ORDER BY club, ancien_nom, competition