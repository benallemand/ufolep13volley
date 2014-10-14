DELETE FROM joueur_equipe 
WHERE 
id_joueur NOT IN (SELECT id FROM joueurs)
OR
id_equipe NOT IN (SELECT id_equipe FROM equipes)