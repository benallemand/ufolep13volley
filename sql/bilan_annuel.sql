-- =============================================================================
-- BILAN ANNUEL — moteur de donnees  (saison 2025-2026 : sept. 2025 -> juin 2026)
-- =============================================================================
-- Fournit tous les chiffres du "Bilan d'activites" officiel (cf. modele PDF).
-- Fichier en lecture seule, a executer requete par requete.
--
-- POUR CHANGER DE SAISON, remplacer partout :
--   * bornes de dates (matchs)   '2025-09-01' et '2026-06-30'
--   * periode championnat         '2025-2026'          (requetes 4a / detail)
--   * periode coupe               '2026'               (requete 4b)
--       -> convention : periode de coupe = annee civile des finales (2e annee
--          de la saison). Championnats en 'YYYY-YYYY+1'.
--
-- DEFINITIONS (choisies pour etre RECONSTRUCTIBLES sur une saison passee) :
--   * "participant" = a joue au moins un match dans la saison.
--     -> clubs et licencies sont derives des MATCHS (matches / match_player),
--        pas des rosters (classements/joueur_equipe) qui sont un instantane de
--        la saison courante uniquement.
--   * Les matchs passes sont conserves en base (statut ARCHIVED, plus supprimes)
--     -> le filtre saison se fait UNIQUEMENT par date, jamais par match_status.
--   * Un match est "joue" s'il a un score saisi ou un forfait.
--
-- MAPPING format : 6x6 = Masculin + Coupe Isoardi ; 4x4 = Feminin + Mixte +
--   Coupe Khoury Hanna. Coupes = phase de poule + phases finales regroupees
--   (Isoardi = c + cf ; Khoury Hanna = kh + kf).
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1) MATCHS JOUES PAR COMPETITION  (+ total)   [MANIFESTATIONS, MATCHS]
--    Coupes regroupees poule + finales, libelles au format du bilan officiel.
-- -----------------------------------------------------------------------------
SELECT CASE code
           WHEN 'm' THEN 'Championnat Masculin 6x6'
           WHEN 'f' THEN 'Championnat Feminin 4x4'
           WHEN 'mo' THEN 'Championnat Mixte 4x4'
           WHEN 'c' THEN 'Coupe Departementale Isoardi 6x6'
           WHEN 'kh' THEN 'Coupe Departementale Khoury Hanna 4x4'
           ELSE '*** TOTAL ***' END AS competition,
       COUNT(*)                     AS nb_matchs
FROM (SELECT CASE
                 WHEN m.code_competition = 'cf' THEN 'c'
                 WHEN m.code_competition = 'kf' THEN 'kh'
                 ELSE m.code_competition END AS code
      FROM matchs_view m
      WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN '2025-09-01' AND '2026-06-30'
        AND ((m.score_equipe_dom + m.score_equipe_ext) > 0 OR m.forfait_dom = 1 OR m.forfait_ext = 1)) x
GROUP BY code WITH ROLLUP
ORDER BY GROUPING(code), nb_matchs DESC;


-- -----------------------------------------------------------------------------
-- 2) NOMBRE DE CLUBS PARTICIPANT
--    Clubs ayant au moins une equipe ayant joue un match de championnat.
-- -----------------------------------------------------------------------------
SELECT COUNT(DISTINCT e.id_club) AS nb_clubs_participants
FROM matchs_view m
         JOIN equipes e ON e.id_equipe IN (m.id_equipe_dom, m.id_equipe_ext)
WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN '2025-09-01' AND '2026-06-30'
  AND m.code_competition IN ('m', 'f', 'mo');


-- -----------------------------------------------------------------------------
-- 3) NOMBRE DE LICENCIES PARTICIPANT
--    Joueurs ayant figure sur au moins une feuille de match de la saison.
-- -----------------------------------------------------------------------------
SELECT COUNT(DISTINCT mp.id_player) AS nb_licencies_participants
FROM match_player mp
         JOIN matchs_view m ON m.id_match = mp.id_match
WHERE STR_TO_DATE(m.date_reception, '%d/%m/%Y') BETWEEN '2025-09-01' AND '2026-06-30';


-- -----------------------------------------------------------------------------
-- 4a) EQUIPES RECOMPENSEES EN CHAMPIONNAT  (+ total)   [CHAMPIONNAT DEPT.]
--     1er et 2e par division, a mi-saison ET en fin de saison (phase Dept.),
--     pour les 3 championnats.
-- -----------------------------------------------------------------------------
SELECT IF(GROUPING(league) = 1, '*** TOTAL ***', league) AS championnat,
       SUM(title LIKE 'Championne mi-saison%')            AS mi_saison_1er,
       SUM(title LIKE 'Vice%mi-saison%')                  AS mi_saison_2e,
       SUM(title LIKE 'Championne Dept.%')                AS fin_saison_1er,
       SUM(title LIKE 'Vice%Dept.%')                      AS fin_saison_2e,
       COUNT(*)                                           AS total_recompenses
FROM hall_of_fame
WHERE period = '2025-2026'
GROUP BY league WITH ROLLUP
ORDER BY GROUPING(league), total_recompenses DESC;


-- -----------------------------------------------------------------------------
-- 4b) COUPES DECERNEES (liste des vainqueurs ; le nb de coupes = nb de lignes)
--     Inclut le Trophee du fair-play. Pour l'exclure : AND league LIKE 'Coupe%'.
-- -----------------------------------------------------------------------------
SELECT league AS recompense,
       team_name AS vainqueur
FROM hall_of_fame
WHERE period = '2026'
  AND title = 'Vainqueur'
ORDER BY league;


-- =============================================================================
-- DETAILS COMPLEMENTAIRES (hors bilan officiel, par club) -- INSTANTANE saison
-- courante uniquement (bases sur classements/joueur_equipe, non reconstructible
-- une fois la saison remplacee).
-- =============================================================================

-- D1) Nombre d'equipes par championnat et par club  (+ total)
SELECT IF(GROUPING(cl.id) = 1, '*** TOTAL ***', MAX(cl.nom))                   AS club,
       COUNT(DISTINCT CASE WHEN c.code_competition = 'm' THEN c.id_equipe END)  AS eq_masculin,
       COUNT(DISTINCT CASE WHEN c.code_competition = 'f' THEN c.id_equipe END)  AS eq_feminin,
       COUNT(DISTINCT CASE WHEN c.code_competition = 'mo' THEN c.id_equipe END) AS eq_mixte,
       COUNT(DISTINCT c.id_equipe)                                              AS total_equipes
FROM classements c
         JOIN equipes e ON e.id_equipe = c.id_equipe
         JOIN clubs cl ON cl.id = e.id_club
WHERE c.code_competition IN ('m', 'f', 'mo')
GROUP BY cl.id WITH ROLLUP
ORDER BY GROUPING(cl.id), total_equipes DESC;

-- D2) Nombre de licencies (inscrits sur roster) M/F par club  (+ total)
SELECT IF(GROUPING(cl.id) = 1, '*** TOTAL ***', MAX(cl.nom)) AS club,
       COUNT(DISTINCT CASE WHEN j.sexe = 'M' THEN j.id END)  AS licencies_h,
       COUNT(DISTINCT CASE WHEN j.sexe = 'F' THEN j.id END)  AS licencies_f,
       COUNT(DISTINCT j.id)                                  AS total_licencies
FROM joueurs j
         JOIN clubs cl ON cl.id = j.id_club
WHERE EXISTS (SELECT 1
              FROM joueur_equipe je
                       JOIN classements c ON c.id_equipe = je.id_equipe
              WHERE je.id_joueur = j.id)
GROUP BY cl.id WITH ROLLUP
ORDER BY GROUPING(cl.id), total_licencies DESC;
