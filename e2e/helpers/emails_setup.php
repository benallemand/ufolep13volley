<?php
/**
 * Issue #314 — helper E2E : un email en erreur, à renvoyer depuis l'écran
 * Emails. `?teardown=1` le retire.
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

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $sql = new SqlManager();
    $sql->execute("DELETE FROM emails WHERE subject = 'E2E issue314 renvoi'");
    $sql->execute("DELETE FROM activity WHERE comment LIKE 'Email renvoyé à e2e.issue314%'");
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $id = $sql->execute(
        "INSERT INTO emails SET from_email = 'noreply@ufolep13volley.test', to_email = 'e2e.issue314@ufolep.test',
             cc = '', bcc = '', subject = 'E2E issue314 renvoi', body = '<h1>E2E</h1><p>Message à renvoyer</p>',
             sending_status = 'ERROR', creation_date = NOW()");
    echo json_encode(['id' => (int)$id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
