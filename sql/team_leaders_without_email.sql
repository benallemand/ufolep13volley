SELECT DISTINCT j.prenom,
                j.nom,
                c.libelle    AS competition,
                e.nom_equipe AS equipe
FROM classements cl
         JOIN joueur_equipe je ON je.id_equipe = cl.id_equipe
         JOIN joueurs j ON j.id = je.id_joueur
         JOIN equipes e ON e.id_equipe = je.id_equipe
         JOIN competitions c ON c.code_competition = e.code_competition
WHERE je.is_leader + 0 > 0
  AND j.email = ''
  AND e.id_equipe IN (SELECT id_equipe FROM classements)