-- Issue #395 : inscriptions de la campagne en cours dont les créneaux sont
-- incomplets. Sans créneau complet, l'équipe ne peut pas recevoir, et le
-- calendrier ne peut pas être généré.
--
-- Une demande refusée est écartée : elle n'est plus à compléter, et la
-- refuser est justement ce qu'on fait d'une équipe « volante » (#376).
SELECT comp.libelle                                AS competition,
       cl.nom                                      AS club,
       r.new_team_name                             AS equipe,
       IF(r.status = 'VALIDATED', 'validée', 'en attente') AS statut,
       CONCAT_WS(', ',
                 IF(r.id_court_1 IS NULL, 'aucun créneau', NULL),
                 IF(r.id_court_1 IS NOT NULL AND (NULLIF(r.day_court_1, '') IS NULL OR NULLIF(r.hour_court_1, '') IS NULL),
                    'créneau 1 sans jour ou sans heure', NULL),
                 IF(r.id_court_2 IS NOT NULL AND (NULLIF(r.day_court_2, '') IS NULL OR NULLIF(r.hour_court_2, '') IS NULL),
                    'créneau 2 sans jour ou sans heure', NULL)) AS probleme,
       cc.contact                                  AS contact_club,
       r.leader_email                              AS responsable_equipe
FROM register r
         JOIN competitions comp ON comp.id = r.id_competition
         JOIN clubs cl ON cl.id = r.id_club
         LEFT JOIN club_contacts_view cc ON cc.id_club = cl.id
WHERE r.status <> 'REFUSED'
  AND r.creation_date >= comp.start_register_date
  AND (r.id_court_1 IS NULL
    OR NULLIF(r.day_court_1, '') IS NULL
    OR NULLIF(r.hour_court_1, '') IS NULL
    OR (r.id_court_2 IS NOT NULL AND (NULLIF(r.day_court_2, '') IS NULL OR NULLIF(r.hour_court_2, '') IS NULL)))
ORDER BY competition, club, equipe
