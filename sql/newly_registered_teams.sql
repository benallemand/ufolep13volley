SELECT DISTINCT cl.nom         AS club,
                r.new_team_name AS equipe,
                c.libelle      AS competition,
                r.leader_email AS responsable
FROM register r
         join competitions c on r.id_competition = c.id
         JOIN clubs cl ON cl.id = r.id_club
WHERE c.code_competition IN ('m', 'f', 'mo')
  AND r.old_team_id IS NULL
ORDER BY competition, club, equipe