TRUNCATE TABLE matches;
DELETE FROM classements WHERE code_competition = 'f';
INSERT INTO classements (code_competition, division, id_equipe, points, joues, gagnes, perdus, sets_pour, sets_contre, difference, coeff_sets, points_pour, points_contre, coeff_points, penalite)
SELECT 'f', '1', e.id_equipe, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 FROM equipes e WHERE e.nom_equipe IN (
'Pélissanne',
'Ensuès Koalettes 1',
'ECSM 1',
'Gardanne 1',
'Istres',
'Ensuès Koalettes 2'
) AND e.code_competition='f';
INSERT INTO classements (code_competition, division, id_equipe, points, joues, gagnes, perdus, sets_pour, sets_contre, difference, coeff_sets, points_pour, points_contre, coeff_points, penalite)
SELECT 'f', '2', e.id_equipe, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 FROM equipes e WHERE e.nom_equipe IN (
'Aubagne',
'Rognac',
'Airbus Helicopter',
'Fos sur Mer',
'Gardanne 2',
'Marseille Volley'
) AND e.code_competition='f';
INSERT INTO classements (code_competition, division, id_equipe, points, joues, gagnes, perdus, sets_pour, sets_contre, difference, coeff_sets, points_pour, points_contre, coeff_points, penalite)
SELECT 'f', '3', e.id_equipe, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 FROM equipes e WHERE e.nom_equipe IN (
'Marseille Est',
'Gardanne 3',
'ECSM 2',
'Trets',
'Carnoux',
'Ventabren',
'Meyrargues',
'Eyguières'
) AND e.code_competition='f';