SELECT DISTINCT j.nom,
                j.prenom,
                club.nom                                                        AS club,
                GROUP_CONCAT(DISTINCT m.code_match)                             AS matchs,
                group_concat(DISTINCT CONCAT(cl.code_competition, cl.division)) AS divisions
FROM match_player mp
         JOIN matches m ON mp.id_match = m.id_match
         JOIN joueurs j on mp.id_player = j.id
         JOIN clubs club ON club.id = j.id_club
         JOIN joueur_equipe je ON j.id = je.id_joueur
         JOIN equipes e on je.id_equipe = e.id_equipe
         LEFT JOIN classements cl ON e.id_equipe = cl.id_equipe
WHERE mp.id_player NOT IN (SELECT id_joueur
                           FROM joueur_equipe
                           WHERE id_equipe IN (m.id_equipe_dom, m.id_equipe_ext))
GROUP BY j.nom, j.prenom