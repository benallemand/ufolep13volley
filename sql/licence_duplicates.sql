-- Fiches joueur qui portent le même numéro de licence (même département).
--
-- Issue #409 : une ligne par FICHE, plus par numéro. Le numéro seul ne disait
-- pas quelles fiches regarder. On montre ce qui permet de décider laquelle
-- garder : club, équipes, homologation, nombre de feuilles de match. Le plus
-- souvent, l'une est un doublon de saisie, sans équipe ni feuille.
-- `indicator_id` ouvre l'écran des joueurs filtré sur ces fiches (#312).
SELECT j.id                                                  AS indicator_id,
       CONCAT(j.departement_affiliation, '_', j.num_licence) AS licence,
       j.nom,
       j.prenom,
       c.nom                                                 AS club,
       (SELECT GROUP_CONCAT(e.nom_equipe ORDER BY e.nom_equipe SEPARATOR ', ')
        FROM joueur_equipe je
                 JOIN equipes e ON e.id_equipe = je.id_equipe
        WHERE je.id_joueur = j.id)                           AS equipes,
       DATE_FORMAT(j.date_homologation, '%d/%m/%Y')          AS homologation,
       (SELECT COUNT(*) FROM match_player mp WHERE mp.id_player = j.id) AS feuilles_de_match
FROM joueurs j
         LEFT JOIN clubs c ON c.id = j.id_club
WHERE NULLIF(TRIM(j.num_licence), '') IS NOT NULL
  AND EXISTS (SELECT 1
              FROM joueurs j2
              WHERE j2.id <> j.id
                AND j2.num_licence = j.num_licence
                AND j2.departement_affiliation <=> j.departement_affiliation)
ORDER BY j.num_licence, feuilles_de_match DESC, j.id
