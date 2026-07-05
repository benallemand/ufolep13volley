<?php
/**
 * Issue #249 — teardown E2E : supprime toutes les données créées par
 * registrations_setup.php et par le parcours de test.
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
    require __DIR__ . '/registrations_cleanup.inc.php';
    echo json_encode(['success' => true]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}
