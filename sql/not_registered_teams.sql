-- Équipes de championnat classées qui n'ont pas de demande d'inscription non
-- refusée dans la campagne en cours (`creation_date >= start_register_date` :
-- `register` garde ses lignes d'une saison à l'autre).
--
-- Rapprochement inscription ↔ équipe (#390, #395) : une réinscription désigne
-- son ancienne équipe par `old_team_id` ; une nouvelle équipe, créée depuis
-- son inscription, se reconnaît à son nom dans sa compétition. Sans ce second
-- cas, une nouvelle équipe déjà placée dans une division passait pour « non
-- réengagée ».
SELECT DISTINCT cl.nom             AS club,
                e.nom_equipe       AS ancien_nom,
                e.code_competition AS competition,
                c.division,
                j.email            AS responsable,
                cc.contact         AS contact_club
FROM classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         JOIN clubs cl ON cl.id = e.id_club
         LEFT JOIN club_contacts_view cc ON cc.id_club = cl.id
         LEFT JOIN joueur_equipe je ON e.id_equipe = je.id_equipe AND je.is_leader + 0 > 0
         LEFT JOIN joueurs j ON je.id_joueur = j.id
WHERE c.will_register_again = 1
  AND c.code_competition IN ('m', 'f', 'mo')
  AND NOT EXISTS (SELECT 1
                  FROM register r
                           JOIN competitions comp ON comp.id = r.id_competition
                  WHERE r.status <> 'REFUSED'
                    AND r.creation_date >= comp.start_register_date
                    AND (r.old_team_id = e.id_equipe
                      OR (r.old_team_id IS NULL
                          AND r.new_team_name = e.nom_equipe
                          AND comp.code_competition = e.code_competition)))
ORDER BY club, ancien_nom, competition
