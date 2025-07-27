WITH first_responsable AS (SELECT ca.id,
                                  ut.team_id                                                 AS id_equipe,
                                  ROW_NUMBER() OVER (PARTITION BY ut.team_id ORDER BY ca.id) AS rn
                           FROM comptes_acces ca
                                    JOIN users_teams ut ON ca.id = ut.user_id
                                    JOIN users_profiles up ON ca.id = up.user_id
                                    JOIN profiles p ON up.profile_id = p.id
                           WHERE p.name = 'RESPONSABLE_EQUIPE')
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
       COUNT(j_fem.id)                  AS filles,
       COUNT(j_resp.id)                 AS reponsable_ok,
       COUNT(j_resp2.id)                AS reponsable2_ok,
       COUNT(j_cap.id)                  AS capitaine_ok
FROM equipes e
         JOIN first_responsable fr ON fr.id_equipe = e.id_equipe AND fr.rn = 1
         JOIN comptes_acces ca ON fr.id = ca.id
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
        ELSE true
        END
    )
ORDER BY c.code_competition, nom_equipe