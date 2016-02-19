SET FOREIGN_KEY_CHECKS=0;
ALTER TABLE matches DROP FOREIGN KEY fk_matches_journees;
ALTER TABLE matches DROP COLUMN id_journee;
ALTER TABLE matches ADD COLUMN id_journee smallint(10) NOT NULL;
ALTER TABLE matches ADD CONSTRAINT fk_matches_journees FOREIGN KEY (id_journee) REFERENCES journees(id);
SET FOREIGN_KEY_CHECKS=1;
UPDATE matches m
  JOIN journees j ON m.code_competition = j.code_competition AND m.journee = j.numero
SET m.id_journee = j.id;