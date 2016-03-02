ALTER TABLE matches MODIFY COLUMN id_creneau SMALLINT(10) NULL;
UPDATE matches SET id_creneau = NULL WHERE id_creneau = 0;