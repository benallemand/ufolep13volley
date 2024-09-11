SELECT j.email
FROM joueurs j
         JOIN joueur_equipe je ON
    je.id_joueur = j.id
        AND je.is_leader + 0 > 0
WHERE je.id_equipe = ?