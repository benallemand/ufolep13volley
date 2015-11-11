UPDATE commission SET photo = CONCAT('players_pics/', LOWER(REPLACE(REPLACE(nom, '-', ''), ' ', '')), LOWER(REPLACE(REPLACE(prenom, '-', ''), ' ', '')), '.jpg');
