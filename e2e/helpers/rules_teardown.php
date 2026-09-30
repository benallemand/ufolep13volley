<?php
/**
 * E2E test helper (#342) — défait `rules_setup.php` : vide le cache fictif et
 * rétablit la valeur du registre mise de côté.
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
    $key = ['type' => 's', 'value' => RulesDocument::REGISTRY_KEY];
    $backup = ['type' => 's', 'value' => 'e2e.rules.folder.backup'];

    $sql->execute("DELETE FROM document_cache WHERE source_id LIKE 'E2eFixture%'");
    $saved = $sql->execute("SELECT registry_value FROM registry WHERE registry_key = ? ORDER BY id DESC LIMIT 1", [$backup]);
    if (!empty($saved)) {
        $sql->execute("DELETE FROM registry WHERE registry_key = ?", [$key]);
        if ($saved[0]['registry_value'] !== '') {
            $sql->execute("INSERT INTO registry (registry_key, registry_value) VALUES (?, ?)",
                [$key, ['type' => 's', 'value' => $saved[0]['registry_value']]]);
        }
        $sql->execute("DELETE FROM registry WHERE registry_key = ?", [$backup]);
    }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
