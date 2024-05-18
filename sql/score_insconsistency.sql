SELECT code_match,
       score_equipe_dom,
       score_equipe_ext,
       (set1_dom + set2_dom + set3_dom + set4_dom + set5_dom) AS score_dom_from_sets,
       (set1_ext + set2_ext + set3_ext + set4_ext + set5_ext) AS score_ext_from_sets
FROM (SELECT m.score_equipe_dom,
             m.score_equipe_ext,
             CASE WHEN m.set_1_dom > m.set_1_ext AND m.set_1_dom >= 25 THEN 1 ELSE 0 END AS set1_dom,
             CASE WHEN m.set_2_dom > m.set_2_ext AND m.set_2_dom >= 25 THEN 1 ELSE 0 END AS set2_dom,
             CASE WHEN m.set_3_dom > m.set_3_ext AND m.set_3_dom >= 25 THEN 1 ELSE 0 END AS set3_dom,
             CASE WHEN m.set_4_dom > m.set_4_ext AND m.set_4_dom >= 25 THEN 1 ELSE 0 END AS set4_dom,
             CASE WHEN m.set_5_dom > m.set_5_ext AND m.set_5_dom >= 15 THEN 1 ELSE 0 END AS set5_dom,
             CASE WHEN m.set_1_ext > m.set_1_dom AND m.set_1_ext >= 25 THEN 1 ELSE 0 END AS set1_ext,
             CASE WHEN m.set_2_ext > m.set_2_dom AND m.set_2_ext >= 25 THEN 1 ELSE 0 END AS set2_ext,
             CASE WHEN m.set_3_ext > m.set_3_dom AND m.set_3_ext >= 25 THEN 1 ELSE 0 END AS set3_ext,
             CASE WHEN m.set_4_ext > m.set_4_dom AND m.set_4_ext >= 25 THEN 1 ELSE 0 END AS set4_ext,
             CASE WHEN m.set_5_ext > m.set_5_dom AND m.set_5_ext >= 15 THEN 1 ELSE 0 END AS set5_ext,
             m.code_match
      FROM matches m
      WHERE m.match_status NOT IN ('ARCHIVED')) sub_sql
WHERE score_equipe_dom != (set1_dom + set2_dom + set3_dom + set4_dom + set5_dom)
   OR score_equipe_ext != (set1_ext + set2_ext + set3_ext + set4_ext + set5_ext)