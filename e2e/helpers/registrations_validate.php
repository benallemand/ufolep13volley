<?php
/**
 * Issue #249 — helper E2E : valide (côté admin) la demande d'inscription
 * créée par le parcours de test, pour vérifier le verrouillage côté club.
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
    $sql->execute("UPDATE register SET status = 'VALIDATED', validation_date = NOW() WHERE new_team_name = 'E2E Reg Team New'");
    echo json_encode(['success' => true]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
