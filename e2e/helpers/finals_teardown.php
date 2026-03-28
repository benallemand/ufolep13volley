<?php
/**
 * E2E test helper — removes test 1/8-finale matches created by finals_setup.php.
 *
 * SECURITY: this file must never be deployed to production.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/SqlManager.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();

    foreach (['KF', 'CF'] as $code_upper) {
        $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2E_{$code_upper}_%'");
        $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2E_{$code_upper}_%'");
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
