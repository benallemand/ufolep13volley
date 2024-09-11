SELECT e.nom_equipe                                                                           AS team_name,
       IF(je.id_equipe IS NOT NULL,
          CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'),
          CONCAT('Pas de responsable ! Infos club: ', c2.prenom_responsable, ' ', c2.nom_responsable, ' (tel: ',
                 c2.tel1_responsable, ', mail: ', c2.email_responsable, ')'))                 AS team_leader,
       c2.email_responsable                                                                   AS club_email,
       c3.libelle                                                                             AS championship_name,
       c.division                                                                             AS division,
       GROUP_CONCAT(CONCAT(c4.jour, '<span/>', c4.heure, '<span/>', g.nom) SEPARATOR '<br/>') AS creneaux
FROM classements c
         JOIN equipes e on c.id_equipe = e.id_equipe
         JOIN clubs c2 on e.id_club = c2.id
         JOIN competitions c3 on c.code_competition = c3.code_competition
         LEFT JOIN joueur_equipe je on e.id_equipe = je.id_equipe AND je.is_leader = true
         LEFT JOIN joueurs j on je.id_joueur = j.id
         LEFT JOIN creneau c4 on e.id_equipe = c4.id_equipe
         LEFT JOIN gymnase g on c4.id_gymnase = g.id
WHERE c3.libelle LIKE '%Championnat%'
GROUP BY e.nom_equipe,
         IF(je.id_equipe IS NOT NULL,
            CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'),
            CONCAT('Pas de responsable ! Infos club: ', c2.prenom_responsable, ' ', c2.nom_responsable, ' (tel: ',
                   c2.tel1_responsable, ', mail: ', c2.email_responsable, ')')), c2.email_responsable, c3.libelle,
         c.division
ORDER BY championship_name, division, team_name