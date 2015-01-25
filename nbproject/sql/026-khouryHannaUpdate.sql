-- Init
DELETE FROM classements WHERE code_competition='kh';
DELETE FROM journees WHERE code_competition = 'kh';
DELETE FROM matches WHERE code_competition='kh';
DELETE FROM journees WHERE code_competition = 'kf';
DELETE FROM matches WHERE code_competition='kf';



























-- Journees
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kh','1',1,'Journée 1','Du 2 au 6 Février 2015');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kh','1',2,'Journée 2','Du 9 au 13 Février 2015');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kh','1',3,'Journée 3','du 16 au 20 Février 2015');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kh','1',4,'Journée 4','Du 9 au 13 Mars 2015');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kh','1',5,'Journée 5','Du 16 au 20 Mars 2015');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kf','1',6,'1/8 de Finale','');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kf','1',7,'1/4 de Finale','');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kf','1',8,'1/2 Finale','');
INSERT INTO journees (code_competition,division,numero,nommage,libelle) VALUES ('kf','1',9,'Finale','');























-- D1
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','209',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','216',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','215',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','223',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','227',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','1','212',0,0,0,0,0,0,0,0,0,0,0,0);


INSERT matches SET code_match='KH001', code_competition='kh', division='1', journee=1, id_equipe_dom=209, id_equipe_ext=212, heure_reception='20h00', date_reception='2015-02-6', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH002', code_competition='kh', division='1', journee=1, id_equipe_dom=216, id_equipe_ext=227, heure_reception='20h45', date_reception='2015-02-2', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH003', code_competition='kh', division='1', journee=1, id_equipe_dom=215, id_equipe_ext=223, heure_reception='20h00', date_reception='2015-02-2', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;

INSERT matches SET code_match='KH004', code_competition='kh', division='1', journee=2, id_equipe_dom=223, id_equipe_ext=212, heure_reception='20h00', date_reception='2015-02-12', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH005', code_competition='kh', division='1', journee=2, id_equipe_dom=227, id_equipe_ext=215, heure_reception='20h00', date_reception='2015-02-12', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH006', code_competition='kh', division='1', journee=2, id_equipe_dom=209, id_equipe_ext=216, heure_reception='20h00', date_reception='2015-02-13', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;

INSERT matches SET code_match='KH007', code_competition='kh', division='1', journee=3, id_equipe_dom=216, id_equipe_ext=212, heure_reception='20h45', date_reception='2015-02-16', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH008', code_competition='kh', division='1', journee=3, id_equipe_dom=215, id_equipe_ext=209, heure_reception='20h00', date_reception='2015-02-16', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH009', code_competition='kh', division='1', journee=3, id_equipe_dom=223, id_equipe_ext=227, heure_reception='20h00', date_reception='2015-02-19', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;

INSERT matches SET code_match='KH010', code_competition='kh', division='1', journee=4, id_equipe_dom=227, id_equipe_ext=212, heure_reception='21h15', date_reception='2015-03-10', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH011', code_competition='kh', division='1', journee=4, id_equipe_dom=209, id_equipe_ext=223, heure_reception='20h00', date_reception='2015-03-13', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH012', code_competition='kh', division='1', journee=4, id_equipe_dom=216, id_equipe_ext=215, heure_reception='20h45', date_reception='2015-03-9', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;

INSERT matches SET code_match='KH013', code_competition='kh', division='1', journee=5, id_equipe_dom=215, id_equipe_ext=212, heure_reception='20h00', date_reception='2015-03-16', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH014', code_competition='kh', division='1', journee=5, id_equipe_dom=216, id_equipe_ext=223, heure_reception='20h45', date_reception='2015-03-16', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH015', code_competition='kh', division='1', journee=5, id_equipe_dom=227, id_equipe_ext=209, heure_reception='20h00', date_reception='2015-03-20', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;





-- D2
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','2','228',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','2','217',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','2','208',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','2','220',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','2','225',0,0,0,0,0,0,0,0,0,0,0,0);




INSERT matches SET code_match='KH016', code_competition='kh', division='2', journee=1, id_equipe_dom=217, id_equipe_ext=217, heure_reception='20h30', date_reception='2015-02-6', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH017', code_competition='kh', division='2', journee=1, id_equipe_dom=208, id_equipe_ext=220, heure_reception='20h00', date_reception='2015-02-5', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH018', code_competition='kh', division='2', journee=2, id_equipe_dom=225, id_equipe_ext=208, heure_reception='20h30', date_reception='2015-02-13', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH019', code_competition='kh', division='2', journee=2, id_equipe_dom=217, id_equipe_ext=228, heure_reception='20h45', date_reception='2015-02-11', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH020', code_competition='kh', division='2', journee=3, id_equipe_dom=208, id_equipe_ext=228, heure_reception='20h00', date_reception='2015-02-19', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH021', code_competition='kh', division='2', journee=3, id_equipe_dom=220, id_equipe_ext=225, heure_reception='20h15', date_reception='2015-02-20', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH022', code_competition='kh', division='2', journee=4, id_equipe_dom=228, id_equipe_ext=220, heure_reception='20h00', date_reception='2015-03-13', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH023', code_competition='kh', division='2', journee=4, id_equipe_dom=217, id_equipe_ext=208, heure_reception='20h45', date_reception='2015-03-11', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH024', code_competition='kh', division='2', journee=5, id_equipe_dom=220, id_equipe_ext=217, heure_reception='20h15', date_reception='2015-03-20', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH025', code_competition='kh', division='2', journee=5, id_equipe_dom=228, id_equipe_ext=225, heure_reception='20h00', date_reception='2015-03-19', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;





-- D3
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','3','214',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','3','210',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','3','211',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','3','218',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','3','221',0,0,0,0,0,0,0,0,0,0,0,0);




INSERT matches SET code_match='KH026', code_competition='kh', division='3', journee=1, id_equipe_dom=221, id_equipe_ext=221, heure_reception='20h30', date_reception='2015-02-4', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH027', code_competition='kh', division='3', journee=1, id_equipe_dom=211, id_equipe_ext=218, heure_reception='19h45', date_reception='2015-02-2', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH028', code_competition='kh', division='3', journee=2, id_equipe_dom=221, id_equipe_ext=211, heure_reception='19h30', date_reception='2015-02-13', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH029', code_competition='kh', division='3', journee=2, id_equipe_dom=214, id_equipe_ext=210, heure_reception='20h00', date_reception='2015-02-9', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH030', code_competition='kh', division='3', journee=3, id_equipe_dom=211, id_equipe_ext=214, heure_reception='19h45', date_reception='2015-02-16', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH031', code_competition='kh', division='3', journee=3, id_equipe_dom=218, id_equipe_ext=221, heure_reception='20h45', date_reception='2015-02-18', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH032', code_competition='kh', division='3', journee=4, id_equipe_dom=214, id_equipe_ext=218, heure_reception='20h00', date_reception='2015-03-9', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH033', code_competition='kh', division='3', journee=4, id_equipe_dom=210, id_equipe_ext=211, heure_reception='20h30', date_reception='2015-03-11', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH034', code_competition='kh', division='3', journee=5, id_equipe_dom=218, id_equipe_ext=210, heure_reception='20h45', date_reception='2015-03-18', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH035', code_competition='kh', division='3', journee=5, id_equipe_dom=221, id_equipe_ext=214, heure_reception='19h30', date_reception='2015-03-20', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;





-- D4
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','4','219',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','4','222',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','4','226',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','4','224',0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO classements(code_competition, division, id_equipe,points,joues,gagnes,perdus,sets_pour,sets_contre,difference,coeff_sets,points_pour,points_contre,coeff_points,penalite) VALUES ('kh','4','213',0,0,0,0,0,0,0,0,0,0,0,0);




INSERT matches SET code_match='KH036', code_competition='kh', division='4', journee=1, id_equipe_dom=222, id_equipe_ext=222, heure_reception='20h00', date_reception='2015-02-4', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH037', code_competition='kh', division='4', journee=1, id_equipe_dom=226, id_equipe_ext=224, heure_reception='20h00', date_reception='2015-02-6', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH038', code_competition='kh', division='4', journee=2, id_equipe_dom=213, id_equipe_ext=226, heure_reception='20h00', date_reception='2015-02-11', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH039', code_competition='kh', division='4', journee=2, id_equipe_dom=219, id_equipe_ext=222, heure_reception='20h30', date_reception='2015-02-11', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH040', code_competition='kh', division='4', journee=3, id_equipe_dom=219, id_equipe_ext=226, heure_reception='20h00', date_reception='2015-02-19', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH041', code_competition='kh', division='4', journee=3, id_equipe_dom=213, id_equipe_ext=224, heure_reception='20h00', date_reception='2015-02-18', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH042', code_competition='kh', division='4', journee=4, id_equipe_dom=224, id_equipe_ext=219, heure_reception='20h00', date_reception='2015-03-12', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH043', code_competition='kh', division='4', journee=4, id_equipe_dom=226, id_equipe_ext=222, heure_reception='20h00', date_reception='2015-03-12', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;


INSERT matches SET code_match='KH044', code_competition='kh', division='4', journee=5, id_equipe_dom=224, id_equipe_ext=222, heure_reception='20h00', date_reception='2015-03-19', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;
INSERT matches SET code_match='KH045', code_competition='kh', division='4', journee=5, id_equipe_dom=219, id_equipe_ext=213, heure_reception='20h30', date_reception='2015-03-18', score_equipe_dom=0, score_equipe_ext=0, set_1_dom=0, set_1_ext=0, set_2_dom=0, set_2_ext=0, set_3_dom=0, set_3_ext=0, set_4_dom=0, set_4_ext=0, set_5_dom=0, set_5_ext=0, gagnea5_dom=0, gagnea5_ext=0, forfait_dom=0, forfait_ext=0, certif=0, report=0, retard=0;