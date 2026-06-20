-- Classement calculé selon le barème FFVB « championnat fédéral » (aperçu admin).
--   Victoire 3-0 / 3-1 = 3 pts | Victoire 3-2 = 2 pts | Défaite 2-3 = 1 pt
--   Défaite 0-3 / 1-3 = 0 pt   | Forfait = -1 pt (défaite 0-3)
--   Pénalités manuelles (classements.penalite) déduites des points.
-- Départage : points -> victoires -> quotient de sets -> quotient de points -> rank_start.
-- Calculé depuis la table `matches` (et non la vue matchs_view, trop coûteuse).
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
            AND set_3_dom = 25 AND set_3_ext = 0 AND is_sign_match_dom <> 0 AND is_sign_match_ext <> 0) AS ff_ext,
        COALESCE(set_1_dom,0)+COALESCE(set_2_dom,0)+COALESCE(set_3_dom,0)+COALESCE(set_4_dom,0)+COALESCE(set_5_dom,0) AS pd,
        COALESCE(set_1_ext,0)+COALESCE(set_2_ext,0)+COALESCE(set_3_ext,0)+COALESCE(set_4_ext,0)+COALESCE(set_5_ext,0) AS pe
    FROM matches
    WHERE code_competition = ? AND division = ? AND match_status <> 'ARCHIVED'
),
mm AS (
    SELECT * FROM m WHERE sd = 3 OR se = 3
),
perf AS (
    SELECT id_equipe_dom AS id_equipe, sd AS own_sets, se AS opp_sets, ff_dom AS ff_against, pd AS pts_for, pe AS pts_against FROM mm
    UNION ALL
    SELECT id_equipe_ext, se, sd, ff_ext, pe, pd FROM mm
),
agg AS (
    SELECT id_equipe,
           COUNT(*) AS joues,
           SUM(own_sets = 3) AS gagnes,
           SUM(opp_sets = 3) AS perdus,
           SUM(own_sets) AS sets_pour,
           SUM(opp_sets) AS sets_contre,
           SUM(pts_for) AS points_pour,
           SUM(pts_against) AS points_contre,
           SUM(CASE WHEN ff_against THEN 1 ELSE 0 END) AS matches_lost_by_forfait_count,
           SUM(CASE
                   WHEN ff_against THEN -1
                   WHEN own_sets = 3 AND opp_sets <= 1 THEN 3
                   WHEN own_sets = 3 AND opp_sets = 2 THEN 2
                   WHEN opp_sets = 3 AND own_sets = 2 THEN 1
                   ELSE 0 END) AS raw_ffvb
    FROM perf
    GROUP BY id_equipe
)
SELECT
    RANK() OVER (
        ORDER BY COALESCE(a.raw_ffvb,0) - c.penalite DESC,
                 COALESCE(a.gagnes,0) DESC,
                 a.sets_pour / NULLIF(a.sets_contre,0) DESC,
                 a.points_pour / NULLIF(a.points_contre,0) DESC,
                 c.rank_start
        ) AS rang,
    c.id_equipe,
    e.nom_equipe AS equipe,
    COALESCE(a.raw_ffvb,0) - c.penalite AS points,
    COALESCE(a.joues,0) AS joues,
    COALESCE(a.gagnes,0) AS gagnes,
    COALESCE(a.perdus,0) AS perdus,
    COALESCE(a.sets_pour,0) AS sets_pour,
    COALESCE(a.sets_contre,0) AS sets_contre,
    COALESCE(a.sets_pour,0) - COALESCE(a.sets_contre,0) AS diff,
    ROUND(a.sets_pour / NULLIF(a.sets_contre,0), 3) AS quotient_sets,
    ROUND(a.points_pour / NULLIF(a.points_contre,0), 3) AS quotient_points,
    c.penalite AS penalites,
    COALESCE(a.matches_lost_by_forfait_count,0) AS matches_lost_by_forfait_count,
    c.report_count
FROM classements c
JOIN equipes e ON e.id_equipe = c.id_equipe
LEFT JOIN agg a ON a.id_equipe = c.id_equipe
WHERE c.code_competition = ? AND c.division = ?
ORDER BY rang
