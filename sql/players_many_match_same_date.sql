SELECT COUNT(CONCAT(sub_sql.prenom, ' ', sub_sql.nom)) AS nb_joueurs,
       sub_sql.equipes,
       sub_sql.jour
FROM (SELECT GROUP_CONCAT(CONCAT(e.nom_equipe, ' (', e.code_competition, ')') ORDER BY e.nom_equipe) AS equipes,
             j.prenom,
             j.nom,
             DATE_FORMAT(m.date_reception, '%d/%m/%Y')                         AS jour
      FROM equipes e
               JOIN matches m on e.id_equipe = m.id_equipe_dom OR e.id_equipe = m.id_equipe_ext
               JOIN joueur_equipe je ON e.id_equipe = je.id_equipe
               JOIN joueurs j ON je.id_joueur = j.id
               JOIN clubs c ON c.id = j.id_club
      WHERE j.id NOT IN (SELECT id_player FROM match_player WHERE id_match = m.id_match)
        AND m.match_status IN ('NOT_CONFIRMED', 'CONFIRMED')
        AND m.date_reception > current_date
      GROUP BY j.prenom, j.nom, DATE_FORMAT(m.date_reception, '%d/%m/%Y')
      HAVING COUNT(m.code_match) > 1
         AND ((GROUP_CONCAT(m.code_match) LIKE '%KH%' OR GROUP_CONCAT(m.code_match) LIKE '%KF%')
          OR (GROUP_CONCAT(m.code_match) LIKE '%C%' OR GROUP_CONCAT(m.code_match) LIKE '%CF%')
          )
      ORDER BY date_reception DESC) sub_sql
GROUP BY sub_sql.equipes, sub_sql.jour
ORDER BY nb_joueurs DESC