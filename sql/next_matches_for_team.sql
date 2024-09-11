SELECT e1.nom_equipe                             AS equipe_domicile,
       e2.nom_equipe                             AS equipe_exterieur,
       m.code_match                              as code_match,
       DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS date,
       cr.heure                                  AS heure,
       CONCAT(jresp.prenom, ' ', jresp.nom)      AS responsable,
       jresp.telephone,
       jresp.email,
       GROUP_CONCAT(
               CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (', cr.jour, ' à ', cr.heure,
                      ')')
               SEPARATOR ', ')
                                                 AS creneaux
FROM matches m
         JOIN equipes e1 ON e1.id_equipe = m.id_equipe_dom
         JOIN equipes e2 ON e2.id_equipe = m.id_equipe_ext
         LEFT JOIN creneau cr ON cr.id_equipe = e1.id_equipe AND cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                                                               'Dimanche',
                                                                               'Lundi',
                                                                               'Mardi',
                                                                               'Mercredi',
                                                                               'Jeudi',
                                                                               'Vendredi',
                                                                               'Samedi')
         LEFT JOIN gymnase g ON g.id = cr.id_gymnase
         LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe = e1.id_equipe AND jeresp.is_leader + 0 > 0
         LEFT JOIN joueurs jresp ON jresp.id = jeresp.id_joueur
WHERE (m.id_equipe_dom = ? OR id_equipe_ext = ?)
  AND (
    m.date_reception >= CURDATE()
        AND
    m.date_reception < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    )
  AND m.score_equipe_dom = 0
  AND m.score_equipe_ext = 0
  AND m.match_status = 'CONFIRMED'
GROUP BY m.code_match, m.date_reception
ORDER BY m.date_reception