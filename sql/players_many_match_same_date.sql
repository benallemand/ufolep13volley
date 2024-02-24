SELECT GROUP_CONCAT(m.code_match)                AS code_match,
       j.prenom,
       j.nom,
       e.nom_equipe,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS jour
FROM equipes e
         JOIN matches m on e.id_equipe = m.id_equipe_dom OR e.id_equipe = m.id_equipe_ext
         JOIN joueur_equipe je ON e.id_equipe = je.id_equipe
         JOIN joueurs j ON je.id_joueur = j.id
GROUP BY j.prenom, j.nom, e.nom_equipe, m.date_reception
HAVING COUNT(m.code_match) > 1
ORDER BY date_reception