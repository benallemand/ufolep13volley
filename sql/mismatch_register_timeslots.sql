-- Issue #395 : inscriptions dont les créneaux demandés diffèrent des créneaux
-- en place. Tant que l'écart n'est pas réglé, on ne peut pas générer le
-- calendrier.
--
-- Une ligne par inscription non refusée de la campagne en cours
-- (`creation_date >= start_register_date` : `register` garde ses lignes d'une
-- saison à l'autre). Un créneau se lit « gymnase (ville) jour heure », et la
-- comparaison porte sur les trois. `ecart` dit ce qui diffère :
--   - aucun créneau en place : nouvelle équipe, ou équipe pas encore créée ;
--   - ordre de préférence inversé : mêmes créneaux, priorités échangées ;
--   - créneau modifié : tout le reste.
-- Un créneau saisi deux fois (même gymnase, jour et heure) ne compte qu'une
-- fois, des deux côtés. Une inscription sans créneau complet n'est pas
-- comparée : « Inscriptions - Infos incomplètes » la signale.
--
-- Rapprochement inscription ↔ équipe (#390) : une réinscription ne désigne que
-- son ancienne équipe ; le nom ne sert qu'à une nouvelle équipe, dans sa
-- compétition.
--
-- « Initialiser la saison » recrée les créneaux depuis les inscriptions : la
-- tuile se vide alors d'elle-même, comme quand on corrige les créneaux à la main.
WITH creneaux_distincts AS (SELECT c.id_equipe,
                                   CONCAT(g.nom, ' (', g.ville, ') ', c.jour, ' ', c.heure) AS creneau,
                                   MIN(c.usage_priority)                                    AS priorite,
                                   MIN(c.id)                                                AS id
                            FROM creneau c
                                     JOIN gymnase g ON g.id = c.id_gymnase
                            GROUP BY c.id_equipe, creneau),
     en_place AS (SELECT id_equipe,
                         GROUP_CONCAT(creneau ORDER BY priorite, id SEPARATOR ' / ') AS en_place,
                         GROUP_CONCAT(creneau ORDER BY creneau SEPARATOR ' / ')      AS en_place_trie
                  FROM creneaux_distincts
                  GROUP BY id_equipe),
     demandes AS (SELECT r.id,
                         comp.libelle    AS competition,
                         cl.nom          AS club,
                         r.new_team_name AS equipe,
                         r.id_club,
                         r.leader_email,
                         (SELECT e.id_equipe
                          FROM equipes e
                          WHERE e.id_equipe = r.old_team_id
                             OR (r.old_team_id IS NULL
                              AND e.nom_equipe = r.new_team_name
                              AND e.code_competition = comp.code_competition)
                          LIMIT 1)       AS id_equipe,
                         IF(r.id_court_1 IS NULL, NULL,
                            CONCAT(g1.nom, ' (', g1.ville, ') ', r.day_court_1, ' ', r.hour_court_1)) AS creneau_1,
                         IF(r.id_court_2 IS NULL, NULL,
                            CONCAT(g2.nom, ' (', g2.ville, ') ', r.day_court_2, ' ', r.hour_court_2)) AS creneau_2
                  FROM register r
                           JOIN competitions comp ON comp.id = r.id_competition
                           JOIN clubs cl ON cl.id = r.id_club
                           LEFT JOIN gymnase g1 ON g1.id = r.id_court_1
                           LEFT JOIN gymnase g2 ON g2.id = r.id_court_2
                  WHERE r.status <> 'REFUSED'
                    AND r.creation_date >= comp.start_register_date),
     demandes_distinctes AS (SELECT d.id,
                                    d.competition,
                                    d.club,
                                    d.equipe,
                                    d.id_club,
                                    d.leader_email,
                                    d.id_equipe,
                                    d.creneau_1,
                                    IF(d.creneau_2 = d.creneau_1, NULL, d.creneau_2) AS creneau_2
                             FROM demandes d),
     comparaison AS (SELECT d.*,
                            CONCAT_WS(' / ', d.creneau_1, d.creneau_2)      AS demande,
                            IF(d.creneau_1 IS NULL OR d.creneau_2 IS NULL,
                               COALESCE(d.creneau_1, d.creneau_2),
                               CONCAT(LEAST(d.creneau_1, d.creneau_2), ' / ',
                                      GREATEST(d.creneau_1, d.creneau_2))) AS demande_triee,
                            ep.en_place,
                            ep.en_place_trie
                     FROM demandes_distinctes d
                              LEFT JOIN en_place ep ON ep.id_equipe = d.id_equipe)
SELECT cmp.competition,
       cmp.club,
       cmp.equipe,
       CASE
           WHEN cmp.en_place IS NULL THEN 'aucun créneau en place'
           WHEN cmp.demande_triee = cmp.en_place_trie THEN 'ordre de préférence inversé'
           ELSE 'créneau modifié'
           END                         AS ecart,
       cmp.demande,
       COALESCE(cmp.en_place, 'aucun') AS en_place,
       cc.contact                      AS contact_club,
       cmp.leader_email                AS responsable_equipe
FROM comparaison cmp
         LEFT JOIN club_contacts_view cc ON cc.id_club = cmp.id_club
WHERE cmp.demande IS NOT NULL
  AND NOT (cmp.demande <=> cmp.en_place)
ORDER BY cmp.competition, cmp.club, cmp.equipe
