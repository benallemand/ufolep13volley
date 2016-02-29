-- TODO run in PROD
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE matches DROP FOREIGN KEY fk_matches_creneau;
ALTER TABLE matches DROP COLUMN id_creneau;
ALTER TABLE matches ADD COLUMN id_creneau SMALLINT(10) NOT NULL;
ALTER TABLE matches ADD CONSTRAINT fk_matches_creneau FOREIGN KEY (id_creneau) REFERENCES creneau (id);
UPDATE matches m
  JOIN creneau cr ON
                    cr.id_equipe = m.id_equipe_dom AND
                    cr.jour = ELT(WEEKDAY(m.date_reception) + 2,
                                  'Dimanche',
                                  'Lundi',
                                  'Mardi',
                                  'Mercredi',
                                  'Jeudi',
                                  'Vendredi',
                                  'Samedi')
SET m.id_creneau = cr.id, m.heure_reception = ''
WHERE m.certif + 0 > 0;
SET FOREIGN_KEY_CHECKS = 1;
-- ALTER TABLE matches DROP COLUMN heure_reception;