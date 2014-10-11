DELETE FROM equipes 
WHERE 
(id_equipe NOT IN (SELECT c.id_equipe FROM classements c)
AND id_equipe NOT IN (156, 198, 199, 201, 202, 203));