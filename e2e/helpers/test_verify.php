<?php
/**
 * E2E test helper — reads set scores from the matches table for a given code_match.
 * Returns JSON: { set_1_dom, set_1_ext, set_2_dom, set_2_ext, ... }
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
if (!$code_match) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing code_match parameter']);
    exit;
}

try {
    $sql = new SqlManager();
    $rows = $sql->execute(
        "SELECT set_1_dom, set_1_ext, set_2_dom, set_2_ext,
                set_3_dom, set_3_ext, set_4_dom, set_4_ext, set_5_dom, set_5_ext
         FROM matches WHERE code_match = ?",
        [['type' => 's', 'value' => $code_match]]
    );

    if (empty($rows)) {
        http_response_code(404);
        echo json_encode(['error' => "Match '$code_match' not found"]);
        exit;
    }

    echo json_encode($rows[0]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
