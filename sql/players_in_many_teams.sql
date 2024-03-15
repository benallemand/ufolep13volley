SELECT COUNT(CONCAT(sub_sql.prenom, ' ', sub_sql.nom))        AS nb_joueurs,
       sub_sql.equipes
FROM (SELECT GROUP_CONCAT(CONCAT(e.nom_equipe, ' (', e.code_competition, ')') ORDER BY e.nom_equipe) AS equipes,
             j.prenom,
             j.nom
      FROM equipes e
               JOIN joueur_equipe je ON e.id_equipe = je.id_equipe
               JOIN joueurs j ON je.id_joueur = j.id
      WHERE e.id_equipe IN (SELECT id_equipe FROM classements)
      GROUP BY j.prenom, j.nom
      HAVING COUNT(e.nom_equipe) > 1
      order by equipes) sub_sql
GROUP BY equipes
HAVING (COUNT(CONCAT(sub_sql.prenom, ' ', sub_sql.nom)) > 2
    AND (equipes LIKE '%mo%' OR equipes LIKE '%f%'))
    OR (COUNT(CONCAT(sub_sql.prenom, ' ', sub_sql.nom)) > 3
    AND (equipes LIKE '%(m)%'))
ORDER BY nb_joueurs DESC
