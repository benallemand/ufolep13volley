CREATE TABLE photos (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    path_photo varchar(500) NOT NULL
);

ALTER TABLE joueurs ADD id_photo smallint(10);

INSERT INTO photos (path_photo) 
SELECT 
CONCAT('players_pics/', LOWER(REPLACE(REPLACE(j.nom, ' ', ''), '-', '')), LOWER(REPLACE(REPLACE(j.prenom, ' ', ''), '-', '')), '.jpg')
FROM joueurs j;

UPDATE joueurs j, photos p SET j.id_photo = p.id
WHERE 
CONCAT('players_pics/', LOWER(REPLACE(REPLACE(j.nom, ' ', ''), '-', '')), LOWER(REPLACE(REPLACE(j.prenom, ' ', ''), '-', '')), '.jpg') = p.path_photo