DELETE FROM equipes 
WHERE 
(id_equipe NOT IN (SELECT c.id_equipe FROM classements c)
AND id_equipe NOT IN (156, 198, 199, 201, 202, 203));

DELETE FROM details_equipes 
WHERE id_equipe NOT IN (SELECT e.id_equipe FROM equipes e);