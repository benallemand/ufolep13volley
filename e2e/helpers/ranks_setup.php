<?php
/**
 * Issue #377 — helper E2E : une équipe engagée dans une division de test
 * (championnat masculin, division « 77 »), que l'écran Divisions / poules
 * éditera. `?teardown=1` la retire.
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
    $sql->execute("DELETE FROM classements WHERE division = '77' AND code_competition = 'm'");
    $sql->execute("DELETE FROM equipes WHERE nom_equipe = 'E2E Poule Team'");
    $sql->execute("DELETE FROM clubs WHERE nom = 'E2E Poule Club'");
    if (!empty($_GET['teardown'])) {
        echo json_encode(['success' => true]);
        exit(0);
    }
    $id_club = $sql->execute("INSERT INTO clubs SET nom = 'E2E Poule Club'");
    $id_team = $sql->execute("INSERT INTO equipes SET code_competition = 'm', nom_equipe = 'E2E Poule Team', id_club = ?",
        [['type' => 'i', 'value' => (int)$id_club]]);
    $id = $sql->execute(
        "INSERT INTO classements SET code_competition = 'm', division = '77', id_equipe = ?, rank_start = 5, penalite = 0",
        [['type' => 'i', 'value' => (int)$id_team]]);
    echo json_encode(['id' => (int)$id, 'id_team' => (int)$id_team]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
