<?php
/**
 * Issue #249 — setup E2E : un club, un compte responsable de club et une
 * compétition dédiée ('zz') avec fenêtre d'inscription ouverte.
 * Ne s'exécute qu'en environnement de test (APP_ENV=test).
 */
require_once __DIR__ . '/../../classes/SqlManager.php';

$isTestEnv = getenv('APP_ENV') === 'test';
if (!$isTestEnv) {
    http_response_code(403);
    die(json_encode(['error' => "APP_ENV != test : helper interdit"]));
}
header('Content-Type: application/json');

try {
    $sql = new SqlManager();

    // idempotent : nettoyage complet avant création (voir registrations_teardown.php)
    require __DIR__ . '/registrations_cleanup.inc.php';

    $id_competition = $sql->execute(
        "INSERT INTO competitions SET
            code_competition = 'zz',
            libelle = 'E2E Inscriptions',
            id_compet_maitre = 'zz',
            start_date = CURRENT_DATE + INTERVAL 60 DAY,
            start_register_date = CURRENT_DATE - INTERVAL 5 DAY,
            limit_register_date = CURRENT_DATE + INTERVAL 5 DAY");

    $id_club = $sql->execute("INSERT INTO clubs SET nom = 'E2E Reg Club'");

    $login = 'e2e_reg_club@ufolep.test';
    $password = 'e2e_pwd';
    $id_user = $sql->execute(
        "INSERT INTO comptes_acces SET
            login = '$login',
            email = '$login',
            password_hash = MD5(CONCAT('$login', '$password'))");
    $sql->execute("INSERT INTO users_clubs SET user_id = $id_user, club_id = $id_club");

    // une équipe existante du club, avec responsable et créneau, pour le
    // parcours « réengager une équipe » (pré-remplissage)
    $id_team = $sql->execute(
        "INSERT INTO equipes SET code_competition = 'zz', nom_equipe = 'E2E Reg Team Old', id_club = $id_club");
    $id_gym = $sql->execute("INSERT INTO gymnase SET nom = 'E2E Reg Gym', nb_terrain = 2");
    $sql->execute(
        "INSERT INTO creneau SET id_gymnase = $id_gym, jour = 'Mardi', heure = '20:30', id_equipe = $id_team, usage_priority = 1");
    $id_player = $sql->execute(
        "INSERT INTO joueurs SET nom = 'REGLEADER', prenom = 'Marie', email = 'e2e_reg_marie@ufolep.test', telephone = '0611111111', id_club = $id_club");
    $sql->execute("INSERT INTO joueur_equipe SET id_joueur = $id_player, id_equipe = $id_team, is_leader = 1");

    echo json_encode([
        'login' => $login,
        'password' => $password,
        'id_competition' => (int)$id_competition,
        'id_club' => (int)$id_club,
        'id_team' => (int)$id_team,
    ]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
