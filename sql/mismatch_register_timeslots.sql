SELECT r.new_team_name,
       r.day_court_1,
       r.day_court_2,
       c.jour,
       c.usage_priority
FROM register r
         LEFT JOIN equipes e on
    r.old_team_id = e.id_equipe
        OR (r.new_team_name = e.nom_equipe AND
            r.id_competition IN (SELECT id
                                 FROM competitions
                                 WHERE code_competition = e.code_competition))
         LEFT JOIN creneau c ON c.id_equipe = e.id_equipe
WHERE ((r.day_court_1 != c.jour AND c.usage_priority = 1)
    OR (r.day_court_2 != c.jour AND c.usage_priority = 2))