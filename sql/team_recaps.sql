-- Contact de repli d'un club : son ou ses comptes (`users_clubs`), et les
-- personnes qui les portent (`joueurs.id_compte`). Remplace les colonnes
-- `clubs.*_responsable`, retirées par l'issue #327 — le compte est la seule
-- source où l'email est à la fois obligatoire et unique.
WITH club_contact AS (SELECT uc.club_id,
                             GROUP_CONCAT(DISTINCT ca.email ORDER BY ca.email SEPARATOR ';')  AS emails,
                             GROUP_CONCAT(DISTINCT CONCAT(j.prenom, ' ', j.nom,
                                                          IFNULL(CONCAT(' (tel: ', NULLIF(j.telephone, ''), ')'), ''))
                                          SEPARATOR ', ')                                     AS personnes
                      FROM users_clubs uc
                               JOIN comptes_acces ca ON ca.id = uc.user_id
                               LEFT JOIN joueurs j ON j.id_compte = ca.id
                      GROUP BY uc.club_id)
SELECT e.nom_equipe                                                                           AS team_name,
       IF(je.id_equipe IS NOT NULL,
          CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'),
          CONCAT('Pas de responsable ! Infos club: ', IFNULL(cc.personnes, 'inconnu'),
                 ' (mail: ', IFNULL(cc.emails, 'inconnu'), ')'))                              AS team_leader,
       cc.emails                                                                              AS club_email,
       c3.libelle                                                                             AS championship_name,
       c.division                                                                             AS division,
       GROUP_CONCAT(CONCAT(c4.jour, '<span/>', c4.heure, '<span/>', g.nom) SEPARATOR '<br/>') AS creneaux
FROM classements c
         JOIN equipes e on c.id_equipe = e.id_equipe
         JOIN clubs c2 on e.id_club = c2.id
         LEFT JOIN club_contact cc on cc.club_id = c2.id
         JOIN competitions c3 on c.code_competition = c3.code_competition
         LEFT JOIN joueur_equipe je on e.id_equipe = je.id_equipe AND je.is_leader = true
         LEFT JOIN joueurs j on je.id_joueur = j.id
         LEFT JOIN creneau c4 on e.id_equipe = c4.id_equipe
         LEFT JOIN gymnase g on c4.id_gymnase = g.id
WHERE c3.libelle LIKE '%Championnat%'
GROUP BY e.nom_equipe,
         IF(je.id_equipe IS NOT NULL,
            CONCAT(j.prenom, ' ', j.nom, ' (tel: ', j.telephone, ', mail: ', j.email, ')'),
            CONCAT('Pas de responsable ! Infos club: ', IFNULL(cc.personnes, 'inconnu'),
                   ' (mail: ', IFNULL(cc.emails, 'inconnu'), ')')), cc.emails, c3.libelle,
         c.division
ORDER BY championship_name, division, team_name
