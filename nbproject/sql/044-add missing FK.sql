SET FOREIGN_KEY_CHECKS = 0;
SET default_storage_engine = InnoDB;

-- DROP obsolete import FK
ALTER TABLE clubs DROP FOREIGN KEY fk_clubs_association;
ALTER TABLE clubs DROP COLUMN id_association;

-- Set right Engine before creating FK
ALTER TABLE activity ENGINE = InnoDB;
ALTER TABLE classements ENGINE = InnoDB;
ALTER TABLE clubs ENGINE = InnoDB;
ALTER TABLE commission ENGINE = InnoDB;
ALTER TABLE competitions ENGINE = InnoDB;
ALTER TABLE comptes_acces ENGINE = InnoDB;
ALTER TABLE creneau ENGINE = InnoDB;
ALTER TABLE dates_limite ENGINE = InnoDB;
ALTER TABLE equipes ENGINE = InnoDB;
ALTER TABLE gymnase ENGINE = InnoDB;
ALTER TABLE joueur_equipe ENGINE = InnoDB;
ALTER TABLE joueurs ENGINE = InnoDB;
ALTER TABLE journees ENGINE = InnoDB;
ALTER TABLE matches ENGINE = InnoDB;
ALTER TABLE photos ENGINE = InnoDB;
ALTER TABLE profiles ENGINE = InnoDB;
ALTER TABLE registry ENGINE = InnoDB;
ALTER TABLE users_profiles ENGINE = InnoDB;

-- modify FK types in order to be compatible with linked primary key's type
ALTER TABLE equipes MODIFY COLUMN id_club SMALLINT(10);
ALTER TABLE creneau MODIFY COLUMN id_equipe SMALLINT(10);
ALTER TABLE creneau MODIFY COLUMN id_gymnase SMALLINT(10);
ALTER TABLE joueur_equipe MODIFY COLUMN id_joueur SMALLINT(10);
ALTER TABLE joueur_equipe MODIFY COLUMN id_equipe SMALLINT(10);
ALTER TABLE activity MODIFY COLUMN user_id SMALLINT(10);
ALTER TABLE comptes_acces MODIFY COLUMN id_equipe SMALLINT(10);
ALTER TABLE equipes MODIFY COLUMN id_photo SMALLINT(10);
ALTER TABLE joueurs MODIFY COLUMN id_club SMALLINT(10);

-- DROP FK before recreating it
ALTER TABLE matches DROP FOREIGN KEY fk_matches_equipesdom;
ALTER TABLE matches DROP FOREIGN KEY fk_matches_equipesext;
ALTER TABLE equipes DROP FOREIGN KEY fk_equipes_clubs;
ALTER TABLE classements DROP FOREIGN KEY fk_classements_equipes;
ALTER TABLE creneau DROP FOREIGN KEY fk_creneau_gymnase;
ALTER TABLE creneau DROP FOREIGN KEY fk_creneau_equipes;
ALTER TABLE joueur_equipe DROP FOREIGN KEY fk_joueur_equipe_joueur;
ALTER TABLE joueur_equipe DROP FOREIGN KEY fk_joueur_equipe_equipe;
ALTER TABLE users_profiles DROP FOREIGN KEY fk_users_profiles_user;
ALTER TABLE users_profiles DROP FOREIGN KEY fk_users_profiles_profile;
-- add FK
ALTER TABLE matches ADD CONSTRAINT fk_matches_equipesdom FOREIGN KEY (id_equipe_dom) REFERENCES equipes (id_equipe);
ALTER TABLE matches ADD CONSTRAINT fk_matches_equipesext FOREIGN KEY (id_equipe_ext) REFERENCES equipes (id_equipe);
ALTER TABLE equipes ADD CONSTRAINT fk_equipes_clubs FOREIGN KEY (id_club) REFERENCES clubs (id);
ALTER TABLE classements ADD CONSTRAINT fk_classements_equipes FOREIGN KEY (id_equipe) REFERENCES equipes (id_equipe);
ALTER TABLE creneau ADD CONSTRAINT fk_creneau_gymnase FOREIGN KEY (id_gymnase) REFERENCES gymnase (id);
ALTER TABLE creneau ADD CONSTRAINT fk_creneau_equipes FOREIGN KEY (id_equipe) REFERENCES equipes (id_equipe);
ALTER TABLE joueur_equipe ADD CONSTRAINT fk_joueur_equipe_joueur FOREIGN KEY (id_joueur) REFERENCES joueurs (id);
ALTER TABLE joueur_equipe ADD CONSTRAINT fk_joueur_equipe_equipe FOREIGN KEY (id_equipe) REFERENCES equipes (id_equipe);
ALTER TABLE users_profiles ADD CONSTRAINT fk_users_profiles_user FOREIGN KEY (user_id) REFERENCES comptes_acces (id);
ALTER TABLE users_profiles ADD CONSTRAINT fk_users_profiles_profile FOREIGN KEY (profile_id) REFERENCES profiles (id);

-- add PK
ALTER TABLE journees DROP PRIMARY KEY;
ALTER TABLE journees ADD PRIMARY KEY (id);

-- add index
DROP INDEX id ON activity;
CREATE INDEX id ON activity (id);
DROP INDEX id ON classements;
CREATE INDEX id ON classements (id);
DROP INDEX id ON clubs;
CREATE INDEX id ON clubs (id);
DROP INDEX id_commission ON commission;
CREATE INDEX id_commission ON commission (id_commission);
DROP INDEX id ON competitions;
CREATE INDEX id ON competitions (id);
DROP INDEX id ON comptes_acces;
CREATE INDEX id ON comptes_acces (id);
DROP INDEX id ON creneau;
CREATE INDEX id ON creneau (id);
DROP INDEX id_date ON dates_limite;
CREATE INDEX id_date ON dates_limite (id_date);
DROP INDEX id_equipe ON equipes;
CREATE INDEX id_equipe ON equipes (id_equipe);
DROP INDEX id ON gymnase;
CREATE INDEX id ON gymnase (id);
DROP INDEX id ON joueur_equipe;
CREATE INDEX id ON joueur_equipe (id);
DROP INDEX id ON joueurs;
CREATE INDEX id ON joueurs (id);
DROP INDEX id_match ON matches;
CREATE INDEX id_match ON matches (id_match);
DROP INDEX id ON photos;
CREATE INDEX id ON photos (id);
DROP INDEX id ON profiles;
CREATE INDEX id ON profiles (id);
DROP INDEX id ON registry;
CREATE INDEX id ON registry (id);
DROP INDEX id ON users_profiles;
CREATE INDEX id ON users_profiles (id);


SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

