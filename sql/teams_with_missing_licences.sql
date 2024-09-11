SELECT GROUP_CONCAT(DISTINCT CONCAT(j.prenom, ' ', j.nom)) AS joueurs,
       c.nom                                               AS club,
       c.email_responsable                                 AS responsable,
       e.nom_equipe                                        AS equipe,
       jr.email
FROM players_view j
         JOIN joueur_equipe je ON je.id_joueur = j.id
         JOIN match_player mp ON mp.id_player = je.id_joueur
         JOIN matches m ON m.id_match = mp.id_match
         JOIN equipes e
              ON e.id_equipe = je.id_equipe AND (m.id_equipe_dom = e.id_equipe OR m.id_equipe_ext = e.id_equipe)
         JOIN joueur_equipe jer ON jer.id_equipe = e.id_equipe AND jer.is_leader + 0 > 0
         JOIN players_view jr ON jr.id = jer.id_joueur
         JOIN clubs c ON c.id = e.id_club
WHERE j.est_actif = 0
  AND m.match_status = 'CONFIRMED' AND m.certif != 1
  AND m.code_competition IN (SELECT code_competition FROM competitions WHERE start_date <= CURRENT_DATE)
GROUP BY c.nom, c.email_responsable, e.nom_equipe, jr.email