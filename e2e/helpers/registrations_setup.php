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

    echo json_encode([
        'login' => $login,
        'password' => $password,
        'id_competition' => (int)$id_competition,
        'id_club' => (int)$id_club,
    ]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
