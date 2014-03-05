<?php

require_once 'classes/Indicator.php';
$indicatorInfosManquantes = new Indicator(
        'Infos Manquantes', "SELECT d.responsable, d.email, e.nom_equipe, c.libelle, cl.division FROM details_equipes d
        LEFT JOIN equipes e ON e.id_equipe=d.id_equipe
        LEFT JOIN competitions c ON c.code_competition=e.code_competition
        LEFT JOIN classements cl ON cl.id_equipe=e.id_equipe
        WHERE (email='' OR responsable='') AND cl.division IS NOT NULL"
);
$indicatorEquipesEngageesChampionnat = new Indicator(
        'Equipes', "SELECT e.nom_equipe,
        comp.libelle,
        c.division,
        d.jour_reception,
        d.heure_reception,
        d.responsable,
        d.email,
        d.telephone_1,
        d.gymnase
        FROM equipes e
        LEFT JOIN competitions comp ON comp.code_competition=e.code_competition
        LEFT JOIN classements c ON c.id_equipe=e.id_equipe AND c.code_competition=e.code_competition
        LEFT JOIN details_equipes d ON d.id_equipe=e.id_equipe
        WHERE ((e.code_competition='m' OR e.code_competition='f') AND c.division IS NOT NULL)
        ORDER BY e.code_competition, c.division, e.id_equipe"
);
$indicatorLicencesDupliquees = new Indicator(
        'Licences dupliquees', "SELECT num_licence, COUNT(*) AS nb_duplicats FROM joueurs 
         GROUP BY num_licence
         HAVING COUNT(*) > 1 AND num_licence != 'Encours'"
);
$indicatorMatchesNonRenseignes = new Indicator(
        'Retards', "SELECT 
        m.code_match AS code, 
        c.libelle AS competition, 
        e1.nom_equipe AS domicile, 
        e2.nom_equipe AS exterieur, 
        DATE_FORMAT(m.date_reception, '%d/%m/%Y') AS 'date'
        FROM matches m
        LEFT JOIN equipes e1 ON e1.id_equipe=m.id_equipe_dom
        LEFT JOIN equipes e2 ON e2.id_equipe=m.id_equipe_ext
        LEFT JOIN competitions c ON c.code_competition=m.code_competition
        WHERE 
        (
        (m.score_equipe_dom+m.score_equipe_ext+0=0)
        OR
        ((m.set_1_dom+m.set_1_ext=0) AND (m.score_equipe_dom+m.score_equipe_ext>0))
        )
        AND m.date_reception < CURDATE() - INTERVAL 10 DAY"
);
$results = array();

$results[] = $indicatorLicencesDupliquees->getResult();
$results[] = $indicatorMatchesNonRenseignes->getResult();
$results[] = $indicatorEquipesEngageesChampionnat->getResult();
$results[] = $indicatorInfosManquantes->getResult();
echo json_encode(array('results' => $results));
