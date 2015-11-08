alter table matches modify column score_equipe_dom tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column score_equipe_ext tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column set_1_dom tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_1_ext tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_2_dom tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_2_ext tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_3_dom tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_3_ext tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_4_dom tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_4_ext tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_5_dom tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column set_5_ext tinyint(2) NOT NULL DEFAULT 0;
alter table matches modify column gagnea5_dom tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column gagnea5_ext tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column forfait_dom tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column forfait_ext tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column certif tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column report tinyint(1) NOT NULL DEFAULT 0;
alter table matches modify column retard tinyint(1) NOT NULL DEFAULT 0;

delete from journees;

insert into journees (code_competition, division, numero, nommage, libelle) values
('f', '1', '1', 'Journee 1', 'du 2 au 6 Novembre'),
('f', '1', '2', 'Journee 2', 'du 9 au 13 Novembre'),
('f', '1', '3', 'Journee 3', 'du 16 au 20 Novembre'),
('f', '1', '4', 'Journee 4', 'du 23 au 27 Novembre'),
('f', '1', '5', 'Journee 5', 'Du 30 au 4 décembre'),
('f', '1', '6', 'Journee 6', 'Du 7 au 11 Decembre'),
('f', '1', '7', 'Journee 7', 'Du 14 au 18 Décembre');

insert into journees (code_competition, division, numero, nommage, libelle) values
('m', '1', '1', 'Journee 1', 'du 2 au 6 Novembre'),
('m', '1', '2', 'Journee 2', 'du 9 au 13 Novembre'),
('m', '1', '3', 'Journee 3', 'du 16 au 20 Novembre'),
('m', '1', '4', 'Journee 4', 'du 23 au 27 Novembre'),
('m', '1', '5', 'Journee 5', 'Du 30 au 4 décembre'),
('m', '1', '6', 'Journee 6', 'Du 7 au 11 Decembre'),
('m', '1', '7', 'Journee 7', 'Du 14 au 18 Décembre');

insert into journees (code_competition, division, numero, nommage, libelle) values
('mo', '1', '1', 'Journee 1', 'du 2 au 6 Novembre'),
('mo', '1', '2', 'Journee 2', 'du 9 au 13 Novembre'),
('mo', '1', '3', 'Journee 3', 'du 16 au 20 Novembre'),
('mo', '1', '4', 'Journee 4', 'du 23 au 27 Novembre'),
('mo', '1', '5', 'Journee 5', 'Du 30 au 4 décembre'),
('mo', '1', '6', 'Journee 6', 'Du 7 au 11 Decembre'),
('mo', '1', '7', 'Journee 7', 'Du 14 au 18 Décembre'),
('mo', '1', '8', 'Journee 8', 'Du 4 au 8 Janvier'),
('mo', '1', '9', 'Journee 9', 'Du 11 au 15 Janvier'),
('mo', '1', '10', 'Journee 10', 'Du 18 au 22 Janvier'),
('mo', '1', '11', 'Journee 11', 'Du 25 au 29 Janvier');
