-- Classement UFOLEP (barème actuel) d'une division.
--   Victoire = 3 pts | Défaite (non forfait) = 1 pt | Défaite par forfait = 0 pt
--   Pénalités manuelles (classements.penalite) déduites des points.
-- Départage : points -> différence de sets -> rank_start (la confrontation directe
-- est appliquée ensuite côté PHP dans Rank::getRank).
--
-- Calcul direct depuis la table `matches` (et non la vue `ranks_view`, qui joint
-- l'énorme `matchs_view`) : ~10 ms au lieu de ~1,6 s, résultat strictement identique.
-- Paramètres (dans l'ordre) : code_competition, division, code_competition, division
WITH m AS (
    SELECT
        id_equipe_dom,
        id_equipe_ext,
        ((set_1_dom >= 25 AND set_1_dom >= set_1_ext + 2)
            + (set_2_dom >= 25 AND set_2_dom >= set_2_ext + 2)
            + (set_3_dom >= 25 AND set_3_dom >= set_3_ext + 2)
            + (set_4_dom >= 25 AND set_4_dom >= set_4_ext + 2)
            + (set_5_dom >= 15 AND set_5_dom >= set_5_ext + 2)) AS sd,
        ((set_1_ext >= 25 AND set_1_ext >= set_1_dom + 2)
            + (set_2_ext >= 25 AND set_2_ext >= set_2_dom + 2)
            + (set_3_ext >= 25 AND set_3_ext >= set_3_dom + 2)
            + (set_4_ext >= 25 AND set_4_ext >= set_4_dom + 2)
            + (set_5_ext >= 15 AND set_5_ext >= set_5_dom + 2)) AS se,
        (set_1_dom = 0 AND set_1_ext = 25 AND set_2_dom = 0 AND set_2_ext = 25
            AND set_3_dom = 0 AND set_3_ext = 25 AND is_sign_match_dom <> 0 AND is_sign_match_ext <> 0) AS ff_dom,
        (set_1_dom = 25 AND set_1_ext = 0 AND set_2_dom = 25 AND set_2_ext = 0
            AND set_3_dom = 25 AND set_3_ext = 0 AND is_sign_match_dom <> 0 AND is_sign_match_ext <> 0) AS ff_ext
    FROM matches
    WHERE code_competition = ? AND division = ? AND match_status <> 'ARCHIVED'
),
mm AS (
    SELECT * FROM m WHERE sd = 3 OR se = 3
),
perf AS (
    SELECT id_equipe_dom AS id_equipe, sd AS own_sets, se AS opp_sets, ff_dom AS ff_against FROM mm
    UNION ALL
    SELECT id_equipe_ext, se, sd, ff_ext FROM mm
),
agg AS (
    SELECT id_equipe,
           COUNT(*) AS joues,
           SUM(own_sets = 3) AS gagnes,
           SUM(opp_sets = 3) AS perdus,
           SUM(own_sets) AS sets_pour,
           SUM(opp_sets) AS sets_contre,
           SUM(CASE WHEN ff_against THEN 1 ELSE 0 END) AS matches_lost_by_forfeit_count,
           SUM(CASE WHEN own_sets = 3 THEN 3 WHEN opp_sets = 3 AND ff_against = 0 THEN 1 ELSE 0 END) AS raw_points
    FROM perf
    GROUP BY id_equipe
)
SELECT
    c.code_competition,
    c.division,
    RANK() OVER (
        ORDER BY COALESCE(a.raw_points,0) - c.penalite DESC,
                 COALESCE(a.sets_pour,0) - COALESCE(a.sets_contre,0) DESC,
                 c.rank_start
        ) AS rang,
    c.id_equipe,
    e.nom_equipe AS equipe,
    COALESCE(a.raw_points,0) - c.penalite AS points,
    COALESCE(a.joues,0) AS joues,
    COALESCE(a.gagnes,0) AS gagnes,
    COALESCE(a.perdus,0) AS perdus,
    COALESCE(a.sets_pour,0) AS sets_pour,
    COALESCE(a.sets_contre,0) AS sets_contre,
    COALESCE(a.sets_pour,0) - COALESCE(a.sets_contre,0) AS diff,
    c.penalite AS penalites,
    COALESCE(a.matches_lost_by_forfeit_count,0) AS matches_lost_by_forfeit_count,
    c.report_count
FROM classements c
JOIN equipes e ON e.id_equipe = c.id_equipe
LEFT JOIN agg a ON a.id_equipe = c.id_equipe
WHERE c.code_competition = ? AND c.division = ?
ORDER BY rang
