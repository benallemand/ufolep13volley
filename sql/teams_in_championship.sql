SELECT e.nom_equipe,
       ''                                   AS my_trim,
       clubs.nom                            AS club,
       e.id_equipe                          AS id,
       e.code_competition                   AS compet,
       c.division,
       GROUP_CONCAT(cr.jour SEPARATOR ',')  AS jour,
       GROUP_CONCAT(cr.heure SEPARATOR ',') AS heure,
       CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
       jresp.email,
       jresp.telephone,
       GROUP_CONCAT(gym.nom SEPARATOR ',')  AS gymnase
FROM equipes e
         JOIN joueur_equipe je ON je.id_equipe = e.id_equipe
         JOIN joueurs jresp ON jresp.id = je.id_joueur AND je.is_leader + 0 = 1
         LEFT JOIN creneau cr ON cr.id_equipe = e.id_equipe
         LEFT JOIN gymnase gym ON gym.id = cr.id_gymnase
         JOIN clubs ON clubs.id = e.id_club
         JOIN competitions comp ON comp.code_competition = e.code_competition
         JOIN classements c ON c.id_equipe = e.id_equipe AND c.code_competition = e.code_competition
WHERE ((e.code_competition = 'm' OR e.code_competition = 'f' OR e.code_competition = 'mo') AND c.division IS NOT NULL)
GROUP BY e.nom_equipe, '', clubs.nom, e.id_equipe, e.code_competition, c.division, CONCAT(jresp.prenom, ' ', jresp.nom),
         jresp.email, jresp.telephone
ORDER BY e.code_competition, c.division, e.id_equipe