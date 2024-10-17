SELECT e.nom_equipe                     AS equipe,
       c.libelle                        AS competition,
       COALESCE(ca.email,
                j_resp.email,
                j_resp.email2,
                j_resp2.email,
                j_resp2.email2,
                j_cap.email,
                j_cap.email2,
                club.email_responsable) AS contact_email,
       COUNT(j_masc.id)                 AS garcons,
       count(j_fem.id)                  AS filles,
       count(j_resp.id)                 AS reponsable_ok,
       count(j_resp2.id)                AS reponsable2_ok,
       count(j_cap.id)                  AS capitaine_ok
FROM equipes e
         JOIN comptes_acces ca ON ca.id_equipe = e.id_equipe
         JOIN users_profiles up on ca.id = up.user_id
         JOIN profiles p on up.profile_id = p.id AND p.name = 'RESPONSABLE_EQUIPE'
         JOIN clubs club ON e.id_club = club.id
         JOIN competitions c ON c.code_competition = e.code_competition
         JOIN joueur_equipe je ON e.id_equipe = je.id_equipe
         LEFT JOIN joueurs j_resp ON je.id_joueur = j_resp.id AND je.is_leader = 1
         LEFT JOIN joueurs j_resp2 ON je.id_joueur = j_resp2.id AND je.is_vice_leader = 1
         LEFT JOIN joueurs j_cap ON je.id_joueur = j_cap.id AND je.is_captain = 1
         LEFT JOIN joueurs j_masc ON je.id_joueur = j_masc.id AND j_masc.sexe = 'M'
         LEFT JOIN joueurs j_fem ON je.id_joueur = j_fem.id AND j_fem.sexe = 'F'
WHERE c.limit_register_date < CURRENT_DATE
  AND e.id_equipe IN (SELECT id_equipe FROM classements)
GROUP BY e.nom_equipe, c.code_competition
HAVING reponsable_ok != 1
    OR (
    CASE
        WHEN c.code_competition IN ('m', 'c', 'cf') THEN garcons + filles < 6
        WHEN c.code_competition IN ('f') THEN (garcons > 0 OR filles < 4)
        WHEN c.code_competition IN ('mo') THEN (garcons < 1 OR filles < 1) OR (garcons + filles < 4)
        WHEN c.code_competition IN ('kh', 'kf') THEN (garcons < 2 OR filles < 2) OR (garcons + filles < 4)
        ELSE 1 = 1
        END
    )
ORDER BY c.code_competition, nom_equipe
