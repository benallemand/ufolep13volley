<?php
/**
 * E2E test helper (#342) — défait `rules_setup.php` : vide le cache du
 * règlement et retire l'identifiant fictif du registre s'il l'y a posé.
 *
 * SECURITY: ne doit jamais être déployé en production.
 */
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$isTestEnv   = getenv('APP_ENV') === 'test';
if (!$isLocalhost && !$isTestEnv) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/SqlManager.php';
require_once __DIR__ . '/../../classes/RulesDocument.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();
    $sql->execute("DELETE FROM document_cache WHERE cache_key LIKE ?",
        [['type' => 's', 'value' => RulesDocument::REGISTRY_KEY . '.%']]);
    $sql->execute("DELETE FROM registry WHERE registry_key = ? AND registry_value = ?",
        [
            ['type' => 's', 'value' => RulesDocument::REGISTRY_KEY],
            ['type' => 's', 'value' => 'E2eFixtureDocument00000000000000'],
        ]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
