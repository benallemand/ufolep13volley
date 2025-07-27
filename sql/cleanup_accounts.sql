DELETE
FROM comptes_acces
WHERE id IN (SELECT user_id
             FROM users_profiles
             WHERE profile_id IN (SELECT id
                                  FROM profiles
                                  WHERE name IN ('RESPONSABLE_EQUIPE')))
  AND id IN (SELECT user_id
             FROM users_teams
             WHERE team_id IN (SELECT id_equipe
                               FROM equipes
                               WHERE code_competition IN (SELECT code_competition
                                                          FROM competitions
                                                          WHERE id = ?
                                                            AND code_competition = id_compet_maitre)))