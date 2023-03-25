SELECT m.code_match,
       e.nom_equipe AS equipe_domicile,
       c.libelle    AS competition
FROM matches m
         JOIN equipes e ON e.id_equipe = m.id_equipe_dom
         JOIN competitions c ON c.code_competition = m.code_competition
         LEFT JOIN creneau cr ON
            cr.id_equipe = m.id_equipe_dom AND
            cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                          'Dimanche',
                          'Lundi',
                          'Mardi',
                          'Mercredi',
                          'Jeudi',
                          'Vendredi',
                          'Samedi')
WHERE cr.id IS NULL
  AND m.certif + 0 = 0
  AND m.match_status != 'ARCHIVED'
  AND m.date_reception > CURRENT_DATE
ORDER BY m.code_match