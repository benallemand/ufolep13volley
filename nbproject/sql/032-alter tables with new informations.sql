SET FOREIGN_KEY_CHECKS=0;
ALTER TABLE clubs DROP FOREIGN KEY fk_clubs_association;
ALTER TABLE equipes DROP FOREIGN KEY fk_equipes_imp_equipe;

ALTER TABLE clubs DROP COLUMN id_association;
ALTER TABLE equipes DROP COLUMN id_imp_equipe;

ALTER TABLE clubs ADD COLUMN id_association smallint(10) NOT NULL;
ALTER TABLE equipes ADD COLUMN id_imp_equipe smallint(10) NOT NULL;

ALTER TABLE clubs ADD CONSTRAINT fk_clubs_association FOREIGN KEY (id_association) REFERENCES imp_association(id);
ALTER TABLE equipes ADD CONSTRAINT fk_equipes_imp_equipe FOREIGN KEY (id_imp_equipe) REFERENCES imp_equipe(id);
SET FOREIGN_KEY_CHECKS=1;

UPDATE clubs SET id_association = 1 WHERE id = 23;
UPDATE clubs SET id_association = 2 WHERE id = 19;
UPDATE clubs SET id_association = 3 WHERE id = 1;
UPDATE clubs SET id_association = 4 WHERE id = 14;
UPDATE clubs SET id_association = 5 WHERE id = 12;
UPDATE clubs SET id_association = 6 WHERE id = 15;
UPDATE clubs SET id_association = 7 WHERE id = 29;
UPDATE clubs SET id_association = 8 WHERE id = 35;
UPDATE clubs SET id_association = 9 WHERE id = 26;
UPDATE clubs SET id_association = 10 WHERE id = 3;
UPDATE clubs SET id_association = 11 WHERE id = 11;
UPDATE clubs SET id_association = 12 WHERE id = 16;
UPDATE clubs SET id_association = 13 WHERE id = 4;
UPDATE clubs SET id_association = 14 WHERE id = 27;
UPDATE clubs SET id_association = 15 WHERE id = 17;
UPDATE clubs SET id_association = 16 WHERE id = 10;
UPDATE clubs SET id_association = 17 WHERE id = 13;
UPDATE clubs SET id_association = 18 WHERE id = 2;
UPDATE clubs SET id_association = 19 WHERE id = 24;
UPDATE clubs SET id_association = 20 WHERE id = 5;
UPDATE clubs SET id_association = 21 WHERE id = 9;
UPDATE clubs SET id_association = 22 WHERE id = 20;
UPDATE clubs SET id_association = 23 WHERE id = 7;
UPDATE clubs SET id_association = 24 WHERE id = 6;
UPDATE clubs SET id_association = 25 WHERE id = 8;
UPDATE clubs SET id_association = 26 WHERE id = 37;
UPDATE clubs SET id_association = 27 WHERE id = 33;
UPDATE clubs SET id_association = 28 WHERE id = 22;
UPDATE clubs SET id_association = 29 WHERE id = 30;
UPDATE clubs SET id_association = 30 WHERE id = 34;
UPDATE clubs SET id_association = 31 WHERE id = 36;
UPDATE clubs SET id_association = 32 WHERE id = 28;
UPDATE clubs SET id_association = 33 WHERE id = 18;

UPDATE clubs c
JOIN imp_association a ON a.id = c.id_association
SET c.nom = a.nom;

update equipes e 
join imp_equipe imp_e on lower(imp_e.nom) = lower(e.nom_equipe)
join imp_competition imp_c on imp_c.id = imp_e.id_competition
set id_imp_equipe = imp_e.id
where e.code_competition = 'm' and imp_c.nom = 'Championnat Masculin';

update equipes e 
join imp_equipe imp_e on lower(imp_e.nom) = lower(e.nom_equipe)
join imp_competition imp_c on imp_c.id = imp_e.id_competition
set id_imp_equipe = imp_e.id
where e.code_competition = 'f' and imp_c.nom = 'Championnat Féminin';

update equipes e set id_imp_equipe = 8 where id_equipe = 2;
update equipes e set id_imp_equipe = 17 where id_equipe = 3;
update equipes e set id_imp_equipe = 23 where id_equipe = 5;
update equipes e set id_imp_equipe = 47 where id_equipe = 7;
update equipes e set id_imp_equipe = 21 where id_equipe = 8;
update equipes e set id_imp_equipe = 24 where id_equipe = 9;
update equipes e set id_imp_equipe = 40 where id_equipe = 14;
update equipes e set id_imp_equipe = 4 where id_equipe = 20;
update equipes e set id_imp_equipe = 16 where id_equipe = 22;
update equipes e set id_imp_equipe = 66 where id_equipe = 26;
update equipes e set id_imp_equipe = 29 where id_equipe = 30;
update equipes e set id_imp_equipe = 22 where id_equipe = 36;
update equipes e set id_imp_equipe = 3 where id_equipe = 37;
update equipes e set id_imp_equipe = 13 where id_equipe = 39;
update equipes e set id_imp_equipe = 45 where id_equipe = 43;
update equipes e set id_imp_equipe = 46 where id_equipe = 47;
update equipes e set id_imp_equipe = 33 where id_equipe = 52;
update equipes e set id_imp_equipe = 86 where id_equipe = 59;
update equipes e set id_imp_equipe = 75 where id_equipe = 63;
update equipes e set id_imp_equipe = 78 where id_equipe = 66;
update equipes e set id_imp_equipe = 14 where id_equipe = 75;
update equipes e set id_imp_equipe = 65 where id_equipe = 77;
update equipes e set id_imp_equipe = 42 where id_equipe = 79;
update equipes e set id_imp_equipe = 7 where id_equipe = 80;
update equipes e set id_imp_equipe = 63 where id_equipe = 81;
update equipes e set id_imp_equipe = 71 where id_equipe = 83;
update equipes e set id_imp_equipe = 15 where id_equipe = 101;
update equipes e set id_imp_equipe = 6 where id_equipe = 102;
update equipes e set id_imp_equipe = 35 where id_equipe = 103;
update equipes e set id_imp_equipe = 76 where id_equipe = 109;
update equipes e set id_imp_equipe = 72 where id_equipe = 128;
update equipes e set id_imp_equipe = 102 where id_equipe = 129;
update equipes e set id_imp_equipe = 25 where id_equipe = 130;
update equipes e set id_imp_equipe = 41 where id_equipe = 131;
update equipes e set id_imp_equipe = 1 where id_equipe = 133;
update equipes e set id_imp_equipe = 27 where id_equipe = 134;
update equipes e set id_imp_equipe = 84 where id_equipe = 136;
update equipes e set id_imp_equipe = 81 where id_equipe = 157;
update equipes e set id_imp_equipe = 82 where id_equipe = 162;
update equipes e set id_imp_equipe = 74 where id_equipe = 197;
update equipes e set id_imp_equipe = 36 where id_equipe = 203;
update equipes e set id_imp_equipe = 9 where id_equipe = 205;
update equipes e set id_imp_equipe = 48 where id_equipe = 206;
update equipes e set id_imp_equipe = 5 where id_equipe = 207;
update equipes e set id_imp_equipe = 80 where id_equipe = 229;
DELETE FROM equipes where id_equipe in (19, 41, 198, 202, 230);
delete from equipes where id_imp_equipe in (37, 34, 26);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('m', 'Meyrargues 2', 26, 37);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('m', 'Chateauneuf 2', 12, 34);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('m', 'Istres', 22, 26);

--les filles
delete from equipes where id_equipe in (71, 231, 60, 205, 108);
delete from equipes where id_imp_equipe in (79, 83, 85, 105);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('f', 'Fos 2', 34, 105);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('f', 'Les Zachariettes', 30, 83);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('f', 'Pink PERIER', 7, 79);
INSERT INTO equipes(code_competition, nom_equipe, id_club, id_imp_equipe) VALUES ('f', 'VCSM2015', 36, 85);

UPDATE equipes e
JOIN imp_equipe ie ON ie.id = e.id_imp_equipe
SET e.nom_equipe = ie.nom;

delete from equipes where code_competition = 'mo';
insert into equipes(code_competition, nom_equipe, id_club, id_imp_equipe) 
select 'mo', ie.nom, c.id, ie.id 
from imp_equipe ie 
join clubs c on c.id_association = ie.id_association
where id_competition = 3;

delete from comptes_acces 
where 
email not in (select imp.email_responsable from imp_equipe imp)
and email not in ('benallemand@gmail.com');

update comptes_acces ca 
join imp_equipe ie on ie.email_responsable = ca.email
join equipes e on e.id_imp_equipe = ie.id
set ca.id_equipe = e.id_equipe;
