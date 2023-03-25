SELECT GROUP_CONCAT(j.email) AS emails,
       c.libelle             AS competition
FROM joueur_equipe je
         JOIN joueurs j ON j.id = je.id_joueur
         JOIN equipes e ON e.id_equipe = je.id_equipe
         JOIN classements c2 on e.id_equipe = c2.id_equipe
         JOIN competitions c ON c.code_competition = c2.code_competition
WHERE je.is_leader + 0 > 0
  AND j.email IS NOT NULL
GROUP BY c.libelle