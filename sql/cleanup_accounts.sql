DELETE
FROM users_teams
WHERE team_id IN (SELECT id_equipe
                  FROM equipes
                  WHERE code_competition IN (SELECT code_competition
                                             FROM competitions
                                             WHERE id = ?
                                               AND code_competition = id_compet_maitre))