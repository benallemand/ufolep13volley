SELECT *
FROM register
WHERE id_court_1 IS NULL
   OR (id_court_1 IS NOT NULL AND (day_court_1 IS NULL or hour_court_1 IS NULL))
   OR (id_court_2 IS NOT NULL AND (day_court_2 IS NULL or hour_court_2 IS NULL))