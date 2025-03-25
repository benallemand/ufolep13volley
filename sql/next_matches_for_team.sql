SELECT m.equipe_dom                         AS equipe_domicile,
       m.equipe_ext                         AS equipe_exterieur,
       m.code_match                         as code_match,
       m.date_reception                     AS date,
       cr.heure                             AS heure,
       CONCAT(jresp.prenom, ' ', jresp.nom) AS responsable,
       jresp.telephone,
       jresp.email,
       GROUP_CONCAT(
               DISTINCT CONCAT(CONCAT(g.ville, ' - ', g.nom, ' - ', g.adresse, ' - ', g.gps), ' (', cr.jour, ' à ', cr.heure,
                      ')')
               SEPARATOR ', ')
                                            AS creneaux
FROM matchs_view m
         LEFT JOIN creneau cr ON cr.id_equipe = m.id_equipe_dom AND
                                 cr.jour = ELT(WEEKDAY(STR_TO_DATE(m.date_reception, '%d/%m/%Y')) + 2,
                                               'Dimanche',
                                               'Lundi',
                                               'Mardi',
                                               'Mercredi',
                                               'Jeudi',
                                               'Vendredi',
                                               'Samedi')
         LEFT JOIN gymnase g ON g.id = cr.id_gymnase
         LEFT JOIN joueur_equipe jeresp ON jeresp.id_equipe = m.id_equipe_dom AND jeresp.is_leader + 0 > 0
         LEFT JOIN joueurs jresp ON jresp.id = jeresp.id_joueur
WHERE (m.id_equipe_dom = ? OR id_equipe_ext = ?)
  AND (
    STR_TO_DATE(m.date_reception, '%d/%m/%Y') >= CURDATE()
        AND
    STR_TO_DATE(m.date_reception, '%d/%m/%Y') < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    )
  AND m.score_equipe_dom = 0
  AND m.score_equipe_ext = 0
  AND m.match_status = 'CONFIRMED'
GROUP BY m.code_match, STR_TO_DATE(m.date_reception, '%d/%m/%Y')
ORDER BY STR_TO_DATE(m.date_reception, '%d/%m/%Y')