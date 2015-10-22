alter table classements modify column points tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column points tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column joues tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column gagnes tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column perdus tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column sets_pour tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column sets_contre tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column difference tinyint(2) NOT NULL DEFAULT 0;
alter table classements modify column coeff_sets decimal(5,4) NOT NULL DEFAULT 0;
alter table classements modify column points_pour smallint(5) NOT NULL DEFAULT 0;
alter table classements modify column points_contre smallint(5) NOT NULL DEFAULT 0;
alter table classements modify column coeff_points decimal(5,4) NOT NULL DEFAULT 0;

delete from classements;

insert into classements(code_competition, division, id_equipe)
select 'm', right(id.nom, 1), e.id_equipe
from imp_division id
join imp_equipe_division ied on ied.id_division = id.id
join equipes e on e.id_imp_equipe = ied.id_equipe
where id_competition = 1;

insert into classements(code_competition, division, id_equipe)
select 'f', '1', e.id_equipe
from equipes e
where e.code_competition = 'f' and e.nom_equipe in ('ECSM 1', 'Fos 1', 'KOALETTES 1', 'Gardanne 1F', 'AHF', 'EGUILLES-VENTABREN', 'EYGUIERES F');

insert into classements(code_competition, division, id_equipe)
select 'f', '2', e.id_equipe
from equipes e
where e.code_competition = 'f' and e.nom_equipe in ('KAT''SEYES', 'ROGNAC VOLLEY F', 'Aubagne 1', 'Marseille Volley', 'ISTRES F', 'Pink PERIER', 'ASPTT Marseille F');

insert into classements(code_competition, division, id_equipe)
select 'f', '3', e.id_equipe
from equipes e
where e.code_competition = 'f' and e.nom_equipe in ('Gardanne 2', 'ECSM 2', 'Fos 2', 'Trets F', 'Gardanne 3', 'VCSM2015', 'Les Zachariettes', 'VCME F');

insert into classements(code_competition, division, id_equipe)
select 'mo', '1', e.id_equipe
from equipes e
where e.code_competition = 'mo' and e.nom_equipe in ('AIX AERO 1 Mi', 'AIX AERO 2 Mi', 'PERIER Chats maigres', 'Eyguières Mi', 'Entressen 1', 'Entressen 2');

insert into classements(code_competition, division, id_equipe)
select 'mo', '2', e.id_equipe
from equipes e
where e.code_competition = 'mo' and e.nom_equipe in ('Pelissanne', 'Gardanne', 'FUVEAU3', 'PERIER Ces fous', 'ASPTT Marseille Mi', 'Marignane Mi');
