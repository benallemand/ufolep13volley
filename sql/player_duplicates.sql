-- Joueurs qui portent le même nom et le même prénom qu'un autre (espaces et
-- casse ignorés) : doublons de saisie probables.
--
-- Issue #397 : une ligne par joueur, plus par paire — chaque paire sortait
-- deux fois (A/B puis B/A), sans identifiant. Les homonymes se suivent, groupés
-- par `homonymes`, et `indicator_id` ouvre l'écran des joueurs filtré sur eux
-- (#312), où l'on compare licences, clubs et équipes avant de fusionner.
SELECT j.id                                                    AS indicator_id,
       CONCAT(UPPER(j.nom), ' ', UPPER(j.prenom))              AS homonymes,
       j.nom,
       j.prenom,
       CONCAT(j.departement_affiliation, '_', j.num_licence)   AS num_licence,
       c.nom                                                   AS club,
       (SELECT GROUP_CONCAT(e.nom_equipe ORDER BY e.nom_equipe SEPARATOR ', ')
        FROM joueur_equipe je
                 JOIN equipes e ON e.id_equipe = je.id_equipe
        WHERE je.id_joueur = j.id)                             AS equipes
FROM joueurs j
         LEFT JOIN clubs c ON c.id = j.id_club
WHERE EXISTS (SELECT 1
              FROM joueurs j2
              WHERE j2.id <> j.id
                AND REPLACE(UPPER(j2.nom), ' ', '') = REPLACE(UPPER(j.nom), ' ', '')
                AND REPLACE(UPPER(j2.prenom), ' ', '') = REPLACE(UPPER(j.prenom), ' ', ''))
ORDER BY REPLACE(UPPER(j.nom), ' ', ''), REPLACE(UPPER(j.prenom), ' ', ''), j.id
