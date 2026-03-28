<?php
/**
 * E2E test helper — removes test match and associated live score.
 * Also removes shared test fixtures (journée e2e_test, gymnase E2E Test) if they exist.
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

$code_match = filter_input(INPUT_GET, 'code_match', FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $sql = new SqlManager();

    if ($code_match) {
        $sql->execute(
            "DELETE FROM live_scores WHERE id_match = ?",
            [['type' => 's', 'value' => $code_match]]
        );
        $sql->execute(
            "DELETE FROM matches WHERE code_match = ?",
            [['type' => 's', 'value' => $code_match]]
        );
    }

    // Nettoyage des fixtures partagées créées par test_setup.php
    $sql->execute("DELETE FROM matches WHERE code_match LIKE 'E2E_%'");
    $sql->execute("DELETE FROM live_scores WHERE id_match LIKE 'E2E_%'");
    $sql->execute("DELETE FROM journees WHERE code_competition = 'e2'");
    $sql->execute("DELETE FROM gymnase WHERE nom = 'Gymnase E2E Test'");
    $sql->execute("DELETE FROM competitions WHERE code_competition = 'e2'");

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
