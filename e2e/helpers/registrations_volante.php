<?php
/**
 * Issue #376 — helper E2E : dépose, pour le club de `registrations_setup.php`,
 * une demande « volante » (sans gymnase) en attente, que l'admin refusera.
 * Nettoyée par `registrations_cleanup.inc.php` (nom « E2E Reg Team… »).
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
    $rows = $sql->execute(
        "SELECT c.id AS id_competition, cl.id AS id_club
         FROM competitions c, clubs cl
         WHERE c.code_competition = 'zz' AND cl.nom = 'E2E Reg Club'");
    if (empty($rows)) {
        throw new Exception("registrations_setup.php doit être appelé avant");
    }
    $sql->execute("DELETE FROM register WHERE new_team_name = 'E2E Reg Team Volante'");
    $id = $sql->execute(
        "INSERT INTO register SET
            new_team_name = 'E2E Reg Team Volante',
            id_club = ?,
            id_competition = ?,
            leader_name = 'Volant',
            leader_first_name = 'Paul',
            leader_email = 'e2e_reg_volante@ufolep.test',
            leader_phone = '0600000001',
            remarks = 'Équipe volante, ne fera que des matchs à l''extérieur.',
            status = 'PENDING'",
        [
            ['type' => 'i', 'value' => (int)$rows[0]['id_club']],
            ['type' => 'i', 'value' => (int)$rows[0]['id_competition']],
        ]);
    echo json_encode(['id' => (int)$id]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
