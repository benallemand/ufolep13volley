SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS imp_competition;
DROP TABLE IF EXISTS imp_division;
DROP TABLE IF EXISTS imp_journee;
DROP TABLE IF EXISTS imp_association;
DROP TABLE IF EXISTS imp_gymnase;
DROP TABLE IF EXISTS imp_association_gymnase;
DROP TABLE IF EXISTS imp_equipe;
DROP TABLE IF EXISTS imp_utilisateur;
DROP TABLE IF EXISTS imp_activite;
DROP TABLE IF EXISTS imp_equipe_division;
DROP TABLE IF EXISTS imp_joueur;
DROP TABLE IF EXISTS imp_classement;
DROP TABLE IF EXISTS imp_joueur_equipe;
DROP TABLE IF EXISTS imp_import_fichier;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE imp_competition (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL UNIQUE
);

CREATE TABLE imp_division (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL,
    id_competition smallint(10) NOT NULL,
    FOREIGN KEY (id_competition) REFERENCES imp_competition(id)
);

CREATE TABLE imp_journee (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL,
    id_competition smallint(10) NOT NULL,
    FOREIGN KEY (id_competition) REFERENCES imp_competition(id)
);

CREATE TABLE imp_association (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL UNIQUE,
    numero_affiliation_ufolep VARCHAR(20) NOT NULL UNIQUE,
    nom_responsable VARCHAR(200) NOT NULL,
    prenom_responsable VARCHAR(200),
    tel1_responsable VARCHAR(20) NOT NULL,
    tel2_responsable VARCHAR(20),
    email_responsable VARCHAR(200) NOT NULL
);

CREATE TABLE imp_gymnase (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL UNIQUE,
    adresse VARCHAR(500) NOT NULL UNIQUE,
    gps VARCHAR(200)
);

CREATE TABLE imp_association_gymnase (
    id_association smallint(10) NOT NULL,
    id_gymnase smallint(10) NOT NULL,
    nb_terrains smallint(10) NOT NULL,
    contraintes VARCHAR(1000),
    jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi') NOT NULL,
    heure VARCHAR(5) NOT NULL,
    index_gymnase_association ENUM('1','2','3') NOT NULL,
    FOREIGN KEY (id_association) REFERENCES imp_association(id),
    FOREIGN KEY (id_gymnase) REFERENCES imp_gymnase(id)
);

CREATE TABLE imp_equipe (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    id_association smallint(10) NOT NULL,
    id_competition smallint(10) NOT NULL,
    nom VARCHAR(200) NOT NULL UNIQUE,
    index_gymnase ENUM('1','2','3') NOT NULL,
    nom_responsable VARCHAR(200) NOT NULL,
    prenom_responsable VARCHAR(200),
    tel1_responsable VARCHAR(20) NOT NULL,
    tel2_responsable VARCHAR(20),
    email_responsable VARCHAR(200) NOT NULL,
    FOREIGN KEY (id_association) REFERENCES imp_association(id),
    FOREIGN KEY (id_competition) REFERENCES imp_competition(id)
);

CREATE TABLE imp_utilisateur (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(200) NOT NULL UNIQUE,
    email VARCHAR(200),
    password VARCHAR(15),
    id_equipe smallint(10) NOT NULL,
    profil ENUM('STANDARD', 'ADMINISTRATEUR', 'RESPONSABLE_EQUIPE'),
    FOREIGN KEY (id_equipe) REFERENCES imp_equipe(id)
);

CREATE TABLE imp_activite (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    commentaire VARCHAR(400),
    date_activite DATETIME,
    id_utilisateur smallint(10) NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES imp_utilisateur(id)
);

CREATE TABLE imp_equipe_division (
    id_equipe smallint(10) NOT NULL,
    id_division smallint(10) NOT NULL,
    FOREIGN KEY (id_equipe) REFERENCES imp_equipe(id),
    FOREIGN KEY (id_division) REFERENCES imp_division(id)
);

CREATE TABLE imp_joueur (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    prenom varchar(50) NOT NULL,
    nom varchar(50) NOT NULL,
    num_licence varchar(50),
    sexe ENUM('M', 'F'),
    departement_affiliation smallint(11) DEFAULT '13',
    est_actif ENUM('O', 'N'),
    show_photo ENUM('O', 'N'),
    id_association smallint(11) NOT NULL,
    FOREIGN KEY (id_association) REFERENCES imp_association(id)
);

CREATE TABLE imp_classement (
    id smallint(10) PRIMARY KEY AUTO_INCREMENT,
    id_competition smallint(11) NOT NULL,
    id_division smallint(11) NOT NULL,
    id_equipe smallint(11) NOT NULL,
    points tinyint(2) NOT NULL DEFAULT '0',
    joues tinyint(2) NOT NULL DEFAULT '0',
    gagnes tinyint(2) NOT NULL DEFAULT '0',
    perdus tinyint(2) NOT NULL DEFAULT '0',
    sets_pour tinyint(2) NOT NULL DEFAULT '0',
    sets_contre tinyint(2) NOT NULL DEFAULT '0',
    difference tinyint(2) NOT NULL DEFAULT '0',
    coeff_sets decimal(5,4) NOT NULL DEFAULT '0',
    points_pour smallint(5) NOT NULL DEFAULT '0',
    points_contre smallint(5) NOT NULL DEFAULT '0',
    coeff_points decimal(5,4) NOT NULL DEFAULT '0',
    penalite tinyint(1) NOT NULL DEFAULT '0',
    FOREIGN KEY (id_competition) REFERENCES imp_competition(id),
    FOREIGN KEY (id_division) REFERENCES imp_division(id),
    FOREIGN KEY (id_equipe) REFERENCES imp_equipe(id)
);

CREATE TABLE imp_joueur_equipe (
    id_joueur smallint(10) NOT NULL,
    id_equipe smallint(10) NOT NULL,
    is_leader ENUM('O', 'N'),
    is_vice_leader ENUM('O', 'N'),
    is_captain ENUM('O', 'N'),
    FOREIGN KEY (id_joueur) REFERENCES imp_joueur(id),
    FOREIGN KEY (id_equipe) REFERENCES imp_equipe(id)
);

CREATE TABLE imp_import_fichier (
    col1 VARCHAR(500)
);
--ALTER TABLE imp_import_fichier ADD COLUMN col1 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col2 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col3 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col4 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col5 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col6 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col7 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col8 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col9 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col10 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col11 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col12 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col13 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col14 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col15 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col16 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col17 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col18 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col19 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col20 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col21 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col22 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col23 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col24 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col25 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col26 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col27 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col28 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col29 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col30 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col31 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col32 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col33 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col34 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col35 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col36 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col37 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col38 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col39 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col40 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col41 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col42 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col43 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col44 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col45 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col46 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col47 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col48 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col49 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col50 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col51 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col52 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col53 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col54 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col55 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col56 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col57 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col58 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col59 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col60 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col61 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col62 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col63 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col64 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col65 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col66 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col67 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col68 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col69 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col70 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col71 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col72 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col73 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col74 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col75 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col76 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col77 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col78 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col79 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col80 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col81 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col82 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col83 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col84 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col85 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col86 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col87 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col88 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col89 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col90 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col91 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col92 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col93 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col94 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col95 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col96 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col97 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col98 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col99 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col100 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col101 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col102 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col103 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col104 VARCHAR(500);
ALTER TABLE imp_import_fichier ADD COLUMN col105 VARCHAR(500);

LOAD DATA 
LOCAL INFILE 'C:\\Users\\Ben\\Desktop\\inscriptions.tsv' 
INTO TABLE imp_import_fichier 
FIELDS TERMINATED BY '\t' 
LINES TERMINATED BY '\r\n';

DELETE FROM imp_import_fichier WHERE col1 = 'Horodateur';

INSERT INTO imp_association(nom, numero_affiliation_ufolep, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT col2, col3, col4, col5, col7, col8, col9 
    FROM imp_import_fichier;

INSERT INTO imp_competition SET nom = 'Championnat Masculin';
INSERT INTO imp_competition SET nom = 'Championnat Féminin';
INSERT INTO imp_competition SET nom = 'Championnat Mixte';

INSERT INTO imp_gymnase (nom, adresse, gps) 
    SELECT col10, col11, col12 
    FROM imp_import_fichier 
    WHERE LENGTH(col10) > 0;
INSERT INTO imp_gymnase (nom, adresse, gps) 
    SELECT col18, col19, col20 
    FROM imp_import_fichier 
    WHERE LENGTH(col18) > 0 
    AND col18 NOT IN (SELECT nom FROM imp_gymnase);

INSERT INTO imp_association_gymnase(id_association, id_gymnase, nb_terrains, contraintes, jour, heure, index_gymnase_association)
    SELECT a.id , g.id, i.col13, i.col14, i.col15, i.col16, '1'
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_gymnase g ON g.nom = i.col10;

INSERT INTO imp_association_gymnase(id_association, id_gymnase, nb_terrains, contraintes, jour, heure, index_gymnase_association)
    SELECT a.id , g.id, i.col21, i.col22, i.col23, i.col24, '2'
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_gymnase g ON g.nom = i.col18;

INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col26, i.col27, i.col28, i.col29, i.col30, i.col31, i.col32
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Masculin'
    WHERE LENGTH(i.col26) > 0;
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col34, i.col35, i.col36, i.col37, i.col38, i.col39, i.col40
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Masculin'
    WHERE LENGTH(i.col34) > 0;
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col42, i.col43, i.col44, i.col45, i.col46, i.col47, i.col48
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Masculin'
    WHERE LENGTH(i.col42) > 0;
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col50, i.col51, i.col52, i.col53, i.col54, i.col55, i.col56
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Masculin'
    WHERE LENGTH(i.col50) > 0;

INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col57, i.col58, i.col59, i.col60, i.col61, i.col62, i.col63
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Féminin'
    WHERE LENGTH(i.col57) > 0;
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col65, i.col66, i.col67, i.col68, i.col69, i.col70, i.col71
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Féminin'
    WHERE LENGTH(i.col65) > 0;
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col73, i.col74, i.col75, i.col76, i.col77, i.col78, i.col79
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Féminin'
    WHERE LENGTH(i.col73) > 0;    

INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col88, i.col89, i.col91, i.col92, i.col93, i.col94, i.col95
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Mixte'
    WHERE LENGTH(i.col88) > 0;    
INSERT INTO imp_equipe(id_association, id_competition, nom, index_gymnase, nom_responsable, prenom_responsable, tel1_responsable, tel2_responsable, email_responsable)
    SELECT a.id, c.id, i.col97, i.col98, i.col100, i.col101, i.col102, i.col103, i.col104
    FROM imp_import_fichier i
    JOIN imp_association a ON a.nom = i.col2
    JOIN imp_competition c ON c.nom = 'Championnat Mixte'
    WHERE LENGTH(i.col97) > 0;    

INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 1', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 2', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 3', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 4', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 5', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 6', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 7', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Masculin';

INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 1', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Féminin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 2', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Féminin';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 3', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Féminin';

INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 1', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Mixte';
INSERT INTO imp_division (nom, id_competition)
    SELECT 'Division 2', c.id
    FROM imp_competition c
    WHERE c.nom = 'Championnat Mixte';

INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 1' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('VCME 1', 'CBVB 1', 'Velaux1', 'Pelissanne1', 'KOALA 2', 'FUVEAU 1', 'AH1', 'ASPTT Aix VB 1');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 2' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('ROQUEVAIRE 1', 'Grans AIL 1', 'Peliisanne2', 'EYGUIERES 1M', 'Marseille Volley 1', 'EGUILLES 1', 'ASPTT Aix VB 2', 'AH2');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 3' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('AIX AERO 1M', 'Meyrargues', 'EYGUIERES 2M', 'AMIS', 'KOALA KBIS ( anciens K1)', 'AH3', 'Trets1', 'ASC PERIER');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 4' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('BOUC BEL AIR', 'AIX AERO 2M', 'Aubagne 2', 'FUVEAU 2', 'Ventabren 2_ASV', 'ROGNAC VOLLEY M', 'ROQUEVAIRE 2');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 5' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('Grans AIL 2', 'MALLEMORT VOLLEY 1', 'CBVB 2', 'Ventabren1_ASV', 'Fos', 'KOALA 3', 'meyrargues 2');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 6' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('Saint Cannat', 'Gardanne 1M', 'Marseille Volley 2', 'Marseille Volley 3', 'Chateauneuf Volley Ball 1', 'VCME 2', 'Saint Zacharie');
INSERT INTO imp_equipe_division (id_equipe, id_division)
    SELECT e.id, d.id
    FROM imp_equipe e
    JOIN imp_division d ON d.nom = 'Division 7' AND d.id_competition = e.id_competition
    WHERE e.nom IN ('Chateauneuf Volley Ball 2', 'Trets 2', 'Marseille Volley 4', 'EGUILLES 2', 'ASPTT Aix VB 3', 'PERIER Citron', 'Istres M');
