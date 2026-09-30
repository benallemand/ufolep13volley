<?php
/**
 * Issue #331 — helper E2E : un compte dont l'email est porté par deux
 * personnes (le cas ambigu que l'automatisme laisse de côté).
 * `?teardown=1` retire tout.
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
    $sql->execute("DELETE FROM joueurs WHERE nom = 'E2ELINK'");
    $sql->execute("DELETE FROM comptes_acces WHERE login = 'e2e_link_account'");
    $sql->execute("DELETE FROM activity WHERE comment LIKE 'Compte e2e_link_account%'");
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $id = $sql->execute(
        "INSERT INTO comptes_acces SET login = 'e2e_link_account', email = 'e2e.link@ufolep.test', password_hash = 'x'");
    foreach (['Alice', 'Bruno'] as $prenom) {
        $sql->execute("INSERT INTO joueurs SET nom = 'E2ELINK', prenom = ?, sexe = 'M', email = 'e2e.link@ufolep.test'",
            [['type' => 's', 'value' => $prenom]]);
    }
    echo json_encode(['user_id' => (int)$id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
