SELECT a.*
FROM (SELECT m.code_match,
             m.date_reception,
             edom.nom_equipe  AS domicile,
             eext.nom_equipe  AS exterieur,
             m.match_status   AS statut,
             'date interdite' AS raison
      from matches m
               JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
               JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
      WHERE (m.date_reception IN (SELECT closed_date FROM blacklist_date))
        AND m.match_status != 'ARCHIVED'
      UNION ALL
      SELECT m.code_match,
             m.date_reception,
             edom.nom_equipe   AS domicile,
             eext.nom_equipe   AS exterieur,
             m.match_status    AS statut,
             'gymnase indispo' AS raison
      from matches m
               JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
               JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
               JOIN creneau c on edom.id_equipe = c.id_equipe
               JOIN blacklist_gymnase bg on c.id_gymnase = bg.id_gymnase
      WHERE bg.closed_date = m.date_reception
        AND m.match_status != 'ARCHIVED'
      UNION ALL
      SELECT m.code_match,
             m.date_reception,
             edom.nom_equipe           AS domicile,
             eext.nom_equipe           AS exterieur,
             m.match_status            AS statut,
             'equipe domicile indispo' AS raison
      from matches m
               JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
               JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
               JOIN blacklist_team bt on edom.id_equipe = bt.id_team
      WHERE bt.closed_date = m.date_reception
        AND m.match_status != 'ARCHIVED'
      UNION ALL
      SELECT m.code_match,
             m.date_reception,
             edom.nom_equipe            AS domicile,
             eext.nom_equipe            AS exterieur,
             m.match_status             AS statut,
             'equipe extérieur indispo' AS raison
      from matches m
               JOIN equipes edom on m.id_equipe_dom = edom.id_equipe
               JOIN equipes eext on m.id_equipe_ext = eext.id_equipe
               JOIN blacklist_team bt on eext.id_equipe = bt.id_team
      WHERE bt.closed_date = m.date_reception
        AND m.match_status != 'ARCHIVED'
      UNION ALL
      SELECT CONCAT(m_t1.code_match, ' et ', m_t2.code_match) AS code_match,
             m_t1.date_reception,
             edom.nom_equipe                                  AS domicile,
             eext.nom_equipe                                  AS exterieur,
             m_t1.match_status                                AS statut,
             'equipes qui ne peuvent pas jouer le même soir'  AS raison
      FROM matches m_t1,
           matches m_t2,
           blacklist_teams bt
               JOIN equipes edom on bt.id_team_1 = edom.id_equipe
               JOIN equipes eext on bt.id_team_2 = eext.id_equipe
      WHERE (m_t1.id_equipe_dom = bt.id_team_1 OR m_t1.id_equipe_ext = bt.id_team_1)
        AND (m_t2.id_equipe_dom = bt.id_team_2 OR m_t2.id_equipe_ext = bt.id_team_2)
        AND m_t1.date_reception = m_t2.date_reception
        AND m_t1.match_status != 'ARCHIVED'
        AND m_t2.match_status != 'ARCHIVED'
      GROUP BY date_reception
      order by code_match) a
WHERE date_reception > CURRENT_DATE